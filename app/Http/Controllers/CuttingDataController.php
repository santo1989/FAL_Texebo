<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\CuttingData;
use App\Models\OrderData;
use App\Models\ProductCombination;
use App\Models\Size;
use App\Models\Style;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CuttingDataController extends Controller
{

    public function index(Request $request)
    {
        $query = CuttingData::with('orderData', 'productCombination.style', 'productCombination.color');

        // Search logic
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                    ->orWhereHas('productCombination.style', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('productCombination.color', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by multiple styles
        if ($request->filled('style_id')) {
            $query->whereHas('productCombination.style', function ($q) use ($request) {
                $q->whereIn('id', $request->input('style_id'));
            });
        }

        // Filter by multiple colors
        if ($request->filled('color_id')) {
            $query->whereHas('productCombination.color', function ($q) use ($request) {
                $q->whereIn('id', $request->input('color_id'));
            });
        }

        // Filter by multiple PO numbers
        if ($request->filled('po_number')) {
            $query->whereIn('po_number', $request->input('po_number'));
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->input('end_date'));
        }

        $cuttingData = $query->orderBy('date', 'desc')->paginate(10);

        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $alldata = OrderData::with('style', 'color')->distinct()->get(['po_number', 'style_id', 'color_id']);
        $allStyles = $alldata->pluck('style')->unique('id')->values();
        $allColors = $alldata->pluck('color')->unique('id')->values();
        $distinctPoNumbers = $alldata->pluck('po_number')->unique()->values();

        return view('backend.library.cutting_data.index', compact('cuttingData', 'allSizes', 'allStyles', 'allColors', 'distinctPoNumbers'));
    }
    public function create()
    {
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $distinctPoNumbers = OrderData::where('po_status', 'running')->distinct()->pluck('po_number');

        return view('backend.library.cutting_data.create', compact('allSizes', 'distinctPoNumbers'));
    }

    public function store(Request $request)
    {
        Log::info('Store Request Data:', $request->all());

        $request->validate([
            'date' => 'required|date',
            'po_number' => 'required|array',
            'po_number.*' => 'string',
            'old_order' => 'required|in:yes,no',
            'rows' => 'required|array',
            'rows.*.po_number' => 'required|string|in:' . implode(',', $request->input('po_number', [])),
            'rows.*.product_combination_id' => 'required|integer|exists:product_combinations,id',
            'rows.*.cut_quantities' => 'nullable|array',
            'rows.*.cut_quantities.*' => 'nullable|integer|min:0',
        ]);

        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $sizeIdToNameMap = $allSizes->keyBy('id')->map(fn($size) => $size->name);

        foreach ($request->rows as $index => $row) {
            $poNumber = $row['po_number'];
            $productCombinationId = $row['product_combination_id'];

            $cutQuantities = array_filter($row['cut_quantities'] ?? [], 'is_numeric');

            if (empty($cutQuantities)) {
                Log::info("Skipping row {$index} for PO {$poNumber}: No quantities provided.");
                continue;
            }

            // Fetch order data for validation
            $orderData = OrderData::where('po_number', $poNumber)
                ->where('product_combination_id', $productCombinationId)
                ->first();

            if (!$orderData) {
                throw ValidationException::withMessages([
                    "rows.{$index}.po_number" => "Order data not found for PO: {$poNumber} and combination ID: {$productCombinationId}"
                ]);
            }

            // Get total existing cut quantities
            $existingCuttingData = CuttingData::where('po_number', $poNumber)
                ->where('product_combination_id', $productCombinationId)
                ->get();

            // Validate each size with 5% extra allowance
            foreach ($cutQuantities as $sizeId => $newCutQty) {
                $orderQty = $orderData->order_quantities[$sizeId] ?? 0;

                // Calculate 5% extra allowance
                $maxAllowed = ceil($orderQty * 1.05);

                // Calculate already cut quantity for this size
                $alreadyCut = 0;
                foreach ($existingCuttingData as $cut) {
                    $alreadyCut += $cut->cut_quantities[$sizeId] ?? 0;
                }

                // Check if new cut exceeds allowed quantity
                if (($alreadyCut + $newCutQty) > $maxAllowed) {
                    $sizeName = $sizeIdToNameMap[$sizeId] ?? $sizeId;
                    throw ValidationException::withMessages([
                        "rows.{$index}.cut_quantities.{$sizeId}" => "Cut quantity for size {$sizeName} exceeds the allowed maximum of {$maxAllowed} (order quantity: {$orderQty} + 5%)"
                    ]);
                }
            }

            $totalCutQty = array_sum($cutQuantities);

            // Create the new CuttingData record
            CuttingData::create([
                'date' => $request->date,
                'po_number' => $poNumber,
                'old_order' => $request->old_order,
                'product_combination_id' => $productCombinationId,
                'cut_quantities' => $cutQuantities,
                'total_cut_quantity' => $totalCutQty,
            ]);

            Log::info("Created cutting data for PO: {$poNumber}, Combination ID: {$productCombinationId}", [
                'cut_quantities' => $cutQuantities,
            ]);
        }

        return redirect()->route('cutting_data.index')->withMessage('Cutting data saved successfully.');
    }

    public function find(Request $request)
    {
        $poNumbers = $request->input('po_numbers', []);
        Log::info('PO Numbers:', $poNumbers);

        if (empty($poNumbers)) {
            return response()->json([]);
        }

        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        Log::info('Sizes:', $allSizes->toArray());
        $sizeIdToNameMap = $allSizes->keyBy('id')->map(fn($size) => $size->name);

        $orderData = OrderData::whereIn('po_number', $poNumbers)
            ->with(['productCombination.style', 'productCombination.color'])
            ->get();
        Log::info('Order Data:', $orderData->toArray());

        $existingCuttingData = CuttingData::whereIn('po_number', $poNumbers)->get();
        Log::info('Cutting Data:', $existingCuttingData->toArray());

        $response = [];
        $aggregatedData = [];

        foreach ($orderData as $order) {
            $poNumber = $order->po_number;
            $combinationId = $order->product_combination_id;
            $key = $poNumber . '-' . $combinationId;

            if (!isset($aggregatedData[$key])) {
                $sizeIds = $order->productCombination ? $order->productCombination->size_ids : [];
                $aggregatedData[$key] = [
                    'po_number' => $poNumber,
                    'combination_id' => $combinationId,
                    'style' => $order->productCombination->style->name ?? 'Unknown',
                    'color' => $order->productCombination->color->name ?? 'Unknown',
                    'size_ids' => $sizeIds,
                    'order_quantities' => [],
                    'cut_quantities' => [],
                ];
            }

            foreach ($order->order_quantities as $sizeId => $quantity) {
                $sizeName = $sizeIdToNameMap[(string)$sizeId] ?? 'unknown';
                $aggregatedData[$key]['order_quantities'][$sizeName] =
                    ($aggregatedData[$key]['order_quantities'][$sizeName] ?? 0) + (int)$quantity;
            }
        }

        foreach ($existingCuttingData as $cut) {
            $poNumber = $cut->po_number;
            $combinationId = $cut->product_combination_id;
            $key = $poNumber . '-' . $combinationId;

            if (isset($aggregatedData[$key])) {
                foreach ($cut->cut_quantities as $sizeIdOrName => $quantity) {
                    // Normalize size name (handle both IDs and names)
                    $sizeName = is_numeric($sizeIdOrName) ?
                        ($sizeIdToNameMap[(string)$sizeIdOrName] ?? 'unknown') :
                        $sizeIdOrName;

                    $aggregatedData[$key]['cut_quantities'][$sizeName] =
                        ($aggregatedData[$key]['cut_quantities'][$sizeName] ?? 0) + (int)$quantity;
                }
            }
        }

        foreach ($aggregatedData as $data) {
            $availableQuantities = [];
            foreach ($data['order_quantities'] as $sizeName => $orderQty) {
                $cutQty = $data['cut_quantities'][$sizeName] ?? 0;

                // Calculate maximum allowed (order quantity + 3%)
                $maxAllowed = ceil($orderQty * 1.03);

                // Calculate available quantity (max allowed minus already cut)
                $available = max(0, $maxAllowed - $cutQty);

                $availableQuantities[$sizeName] = $available;
            }

            $response[$data['po_number']][] = [
                'combination_id' => $data['combination_id'],
                'style' => $data['style'],
                'color' => $data['color'],
                'size_ids' => $data['size_ids'],
                'available_quantities' => $availableQuantities,
            ];
        }

        Log::info('Response Data:', $response);
        return response()->json($response);
    }

    public function edit(CuttingData $cuttingDatum)
    {
        $cuttingDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');

        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $sizeIdToNameMap = $allSizes->keyBy('id')->map(fn($size) => $size->name);

        $poOrderData = OrderData::where('po_number', $cuttingDatum->po_number)
            ->where('product_combination_id', $cuttingDatum->product_combination_id)
            ->first();

        if (!$poOrderData) {
            return redirect()->route('cutting_data.index')->withErrors('Order data for this PO not found.');
        }

        // Fetch existing quantities from all other cutting data records for this PO
        $totalExistingCutQuantities = CuttingData::where('po_number', $cuttingDatum->po_number)
            ->where('product_combination_id', $cuttingDatum->product_combination_id)
            ->where('id', '!=', $cuttingDatum->id)
            ->get()
            ->flatMap(fn($data) => $data->cut_quantities)
            ->reduce(function ($carry, $quantity, $sizeId) {
                $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $quantity;
                return $carry;
            }, []);

        $orderQuantities = $poOrderData->order_quantities;
        $availableQuantities = [];

        foreach ($orderQuantities as $sizeId => $orderQty) {
            $existingCutQty = $totalExistingCutQuantities[$sizeId] ?? 0;

            // Calculate maximum allowed (order quantity + 3%)
            $maxAllowed = ceil($orderQty * 1.03);

            // Calculate available quantity (max allowed minus already cut from other records)
            $availableQuantities[$sizeId] = max(0, $maxAllowed - $existingCutQty);
        }

        return view('backend.library.cutting_data.edit', compact(
            'cuttingDatum',
            'allSizes',
            'availableQuantities',
            'orderQuantities',
            'sizeIdToNameMap'
        ));
    }

    public function update(Request $request, CuttingData $cuttingDatum)
    {
        $request->validate([
            'date' => 'required|date',
            'cut_quantities' => 'nullable|array',
            'cut_quantities.*' => 'nullable|integer|min:0',
            'waste_quantities' => 'nullable|array',
            'waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        $poNumber = $cuttingDatum->po_number;
        $productCombinationId = $cuttingDatum->product_combination_id;

        $poOrderData = OrderData::where('po_number', $poNumber)->where('product_combination_id', $productCombinationId)->first();
        if (!$poOrderData) {
            return redirect()->back()->withErrors('Order data for this PO not found.');
        }

        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $sizeIdToNameMap = $allSizes->keyBy('id')->map(fn($size) => $size->name);

        $orderQuantities = $poOrderData->order_quantities;

        // Sum existing quantities from all records EXCEPT the current one
        $existingCuttingData = CuttingData::where('po_number', $poNumber)
            ->where('product_combination_id', $productCombinationId)
            ->where('id', '!=', $cuttingDatum->id)
            ->get();

        $existingCutQuantities = [];
        foreach ($existingCuttingData as $cut) {
            foreach ($cut->cut_quantities as $sizeId => $quantity) {
                $existingCutQuantities[$sizeId] = ($existingCutQuantities[$sizeId] ?? 0) + $quantity;
            }
        }

        // Sum new quantities from the request
        $newCutQuantities = array_filter($request->input('cut_quantities', []), 'is_numeric');
        $newWasteQuantities = array_filter($request->input('waste_quantities', []), 'is_numeric');

        // Validate each size with 5% extra allowance
        foreach ($newCutQuantities as $sizeId => $newCutQty) {
            $orderQty = $orderQuantities[$sizeId] ?? 0;

            // Calculate 5% extra allowance
            $maxAllowed = ceil($orderQty * 1.05);

            // Calculate already cut quantity for this size (excluding current record)
            $alreadyCut = $existingCutQuantities[$sizeId] ?? 0;

            // Check if new cut exceeds allowed quantity
            if (($alreadyCut + $newCutQty) > $maxAllowed) {
                $sizeName = $sizeIdToNameMap[$sizeId] ?? $sizeId;
                throw ValidationException::withMessages([
                    "cut_quantities.{$sizeId}" => "Cut quantity for size {$sizeName} exceeds the allowed maximum of {$maxAllowed} (order quantity: {$orderQty} + 5%)"
                ]);
            }
        }

        $totalNewCut = array_sum($newCutQuantities);
        $totalNewWaste = array_sum($newWasteQuantities);

        // Update the model with the new quantities
        $cuttingDatum->update([
            'date' => $request->date,
            'cut_quantities' => $newCutQuantities,
            'total_cut_quantity' => $totalNewCut,
            'cut_waste_quantities' => $newWasteQuantities,
            'total_cut_waste_quantity' => $totalNewWaste,
        ]);

        return redirect()->route('cutting_data.index')->withMessage('Cutting data updated successfully.');
    }

    public function show(CuttingData $cuttingDatum)
    {
        $cuttingDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        return view('backend.library.cutting_data.show', compact('cuttingDatum', 'allSizes'));
    }

    public function destroy(CuttingData $cuttingDatum)
    {
        $cuttingDatum->delete();
        return redirect()->route('cutting_data.index')->withMessage('Cutting data deleted successfully.');
    }

    // Other methods remain the same...

    public function createWaste()
    {
        $distinctPoNumbers = CuttingData::distinct()->pluck('po_number');
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        return view('backend.library.cutting_data.waste_create', compact('allSizes', 'distinctPoNumbers'));
    }

    public function storeWaste(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'po_number' => 'required|array',
            'po_number.*' => 'string',
            'rows' => 'required|array',
            'rows.*.po_number' => 'required|string',
            'rows.*.product_combination_id' => 'required|integer',
            'rows.*.waste_quantities' => 'nullable|array',
            'rows.*.waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $sizeIdToNameMap = $allSizes->keyBy('id')->map(fn($size) => $size->name);

        foreach ($request->rows as $index => $row) {
            $poNumber = $row['po_number'];
            $productCombinationId = $row['product_combination_id'];
            $wasteQuantities = array_filter($row['waste_quantities'] ?? [], 'is_numeric');

            if (empty($wasteQuantities)) {
                continue;
            }

            // Fetch order data for validation
            $orderData = OrderData::where('po_number', $poNumber)
                ->where('product_combination_id', $productCombinationId)
                ->first();

            if (!$orderData) {
                throw ValidationException::withMessages([
                    "rows.{$index}.po_number" => "Order data not found for PO: {$poNumber} and combination ID: {$productCombinationId}"
                ]);
            }

            // Get total existing waste quantities
            $existingCuttingData = CuttingData::where('po_number', $poNumber)
                ->where('product_combination_id', $productCombinationId)
                ->get();

            // Validate each size with 5% extra allowance for waste
            foreach ($wasteQuantities as $sizeId => $newWasteQty) {
                $orderQty = $orderData->order_quantities[$sizeId] ?? 0;

                // Calculate 5% extra allowance
                $maxAllowed = ceil($orderQty * 1.05);

                // Calculate already waste quantity for this size
                $alreadyWaste = 0;
                foreach ($existingCuttingData as $cut) {
                    $alreadyWaste += $cut->cut_waste_quantities[$sizeId] ?? 0;
                }

                // Check if new waste exceeds allowed quantity
                if (($alreadyWaste + $newWasteQty) > $maxAllowed) {
                    $sizeName = $sizeIdToNameMap[$sizeId] ?? $sizeId;
                    throw ValidationException::withMessages([
                        "rows.{$index}.waste_quantities.{$sizeId}" => "Waste quantity for size {$sizeName} exceeds the allowed maximum of {$maxAllowed} (order quantity: {$orderQty} + 5%)"
                    ]);
                }
            }

            $totalWasteQty = array_sum($wasteQuantities);

            // Find existing record to update
            $cuttingData = CuttingData::where('po_number', $poNumber)
                ->where('product_combination_id', $productCombinationId)
                ->first();

            if ($cuttingData) {
                // Merge and sum new waste with existing waste quantities
                $existingWasteQuantities = $cuttingData->cut_waste_quantities ?? [];
                $newWasteQuantities = $existingWasteQuantities;

                foreach ($wasteQuantities as $sizeId => $qty) {
                    $newWasteQuantities[$sizeId] = ($newWasteQuantities[$sizeId] ?? 0) + $qty;
                }

                $cuttingData->update([
                    'cut_waste_quantities' => $newWasteQuantities,
                    'total_cut_waste_quantity' => $cuttingData->total_cut_waste_quantity + $totalWasteQty,
                ]);
            } else {
                // If no cutting data exists yet, create a new entry for waste
                CuttingData::create([
                    'date' => $request->date,
                    'po_number' => $poNumber,
                    'old_order' => 'no',
                    'product_combination_id' => $productCombinationId,
                    'cut_quantities' => [],
                    'total_cut_quantity' => 0,
                    'cut_waste_quantities' => $wasteQuantities,
                    'total_cut_waste_quantity' => $totalWasteQty,
                ]);
            }
        }

        return redirect()->route('cutting_data.index')->withMessage('Waste data saved successfully.');
    }
    //     public function index(Request $request)
    //     {
    //         $query = CuttingData::with('orderData', 'productCombination.style', 'productCombination.color');

    //         if ($request->filled('search')) {
    //             $search = $request->input('search');
    //             $query->where('po_number', 'like', '%' . $search . '%')
    //                 ->orWhereHas('productCombination.style', function ($q) use ($search) {
    //                     $q->where('name', 'like', '%' . $search . '%');
    //                 })
    //                 ->orWhereHas('productCombination.color', function ($q) use ($search) {
    //                     $q->where('name', 'like', '%' . $search . '%');
    //                 });
    //         }
    //         if ($request->filled('date')) {
    //             $query->whereDate('date', $request->input('date'));
    //         }

    //         $cuttingData = $query->orderBy('date', 'desc')->paginate(perPage: 10);
    //         $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //         return view('backend.library.cutting_data.index', compact('cuttingData', 'allSizes'));
    //     }

    //    public function create()
    //     {
    //         $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //         $distinctPoNumbers = OrderData::where('po_status', 'running')->distinct()->pluck('po_number');

    //         return view('backend.library.cutting_data.create', compact('allSizes', 'distinctPoNumbers'));
    //     }

    //     public function store(Request $request)
    //     {
    //         Log::info('Store Request Data:', $request->all());

    //         $request->validate([
    //             'date' => 'required|date',
    //             'po_number' => 'required|array',
    //             'po_number.*' => 'string',
    //             'old_order' => 'required|in:yes,no',
    //             'rows' => 'required|array',
    //             'rows.*.po_number' => 'required|string|in:' . implode(',', $request->input('po_number', [])),
    //             'rows.*.product_combination_id' => 'required|integer|exists:product_combinations,id',
    //             'rows.*.cut_quantities' => 'nullable|array',
    //             'rows.*.cut_quantities.*' => 'nullable|integer|min:0',
    //         ]);

    //         foreach ($request->rows as $index => $row) {
    //             $poNumber = $row['po_number'];
    //             $productCombinationId = $row['product_combination_id'];

    //             $cutQuantities = array_filter($row['cut_quantities'] ?? [], 'is_numeric');

    //             if (empty($cutQuantities)) {
    //                 Log::info("Skipping row {$index} for PO {$poNumber}: No quantities provided.");
    //                 continue;
    //             }

    //             $totalCutQty = array_sum($cutQuantities);

    //             // Fetch order data for validation
    //             $orderData = OrderData::where('po_number', $poNumber)
    //                 ->where('product_combination_id', $productCombinationId)
    //                 ->first();

    //             if (!$orderData) {
    //                 throw ValidationException::withMessages([
    //                     "rows.{$index}.po_number" => "Order data not found for PO: {$poNumber} and combination ID: {$productCombinationId}"
    //                 ]);
    //             }

    //             // Get total existing cut quantities
    //             $totalExistingCut = CuttingData::where('po_number', $poNumber)
    //                 ->where('product_combination_id', $productCombinationId)
    //                 ->sum('total_cut_quantity');

    //             $totalOrderQty = array_sum($orderData->order_quantities);

    //             // Validate that new quantities don't exceed remaining order quantity
    //             if (($totalCutQty + $totalExistingCut) > $totalOrderQty) {
    //                 throw ValidationException::withMessages([
    //                     "rows.{$index}" => "Total cut quantities for PO: {$poNumber} exceed the total order quantity."
    //                 ]);
    //             }

    //             // Create the new CuttingData record
    //             CuttingData::create([
    //                 'date' => $request->date,
    //                 'po_number' => $poNumber,
    //                 'old_order' => $request->old_order,
    //                 'product_combination_id' => $productCombinationId,
    //                 'cut_quantities' => $cutQuantities,
    //                 'total_cut_quantity' => $totalCutQty,
    //             ]);

    //             Log::info("Created cutting data for PO: {$poNumber}, Combination ID: {$productCombinationId}", [
    //                 'cut_quantities' => $cutQuantities,
    //             ]);
    //         }

    //         return redirect()->route('cutting_data.index')->withMessage( 'Cutting data saved successfully.');
    //     }

    //     public function find(Request $request)
    //     {
    //         $poNumbers = $request->input('po_numbers', []);
    //         Log::info('PO Numbers:', $poNumbers);

    //         if (empty($poNumbers)) {
    //             return response()->json([]);
    //         }

    //         $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //         Log::info('Sizes:', $allSizes->toArray());
    //         $sizeIdToNameMap = $allSizes->keyBy('id')->map(fn($size) => $size->name);

    //         $orderData = OrderData::whereIn('po_number', $poNumbers)
    //             ->with(['productCombination.style', 'productCombination.color'])
    //             ->get();
    //         Log::info('Order Data:', $orderData->toArray());

    //         $existingCuttingData = CuttingData::whereIn('po_number', $poNumbers)->get();
    //         Log::info('Cutting Data:', $existingCuttingData->toArray());

    //         $response = [];
    //         $aggregatedData = [];

    //         foreach ($orderData as $order) {
    //             $poNumber = $order->po_number;
    //             $combinationId = $order->product_combination_id;
    //             $key = $poNumber . '-' . $combinationId;

    //             if (!isset($aggregatedData[$key])) {
    //                 $sizeIds = $order->productCombination ? $order->productCombination->size_ids : [];
    //                 $aggregatedData[$key] = [
    //                     'po_number' => $poNumber,
    //                     'combination_id' => $combinationId,
    //                     'style' => $order->productCombination->style->name ?? 'Unknown',
    //                     'color' => $order->productCombination->color->name ?? 'Unknown',
    //                     'size_ids' => $sizeIds,
    //                     'order_quantities' => [],
    //                     'cut_quantities' => [],
    //                 ];
    //             }

    //             foreach ($order->order_quantities as $sizeId => $quantity) {
    //                 $sizeName = $sizeIdToNameMap[(string)$sizeId] ?? 'unknown';
    //                 $aggregatedData[$key]['order_quantities'][$sizeName] =
    //                     ($aggregatedData[$key]['order_quantities'][$sizeName] ?? 0) + (int)$quantity;
    //             }
    //         }

    //         foreach ($existingCuttingData as $cut) {
    //             $poNumber = $cut->po_number;
    //             $combinationId = $cut->product_combination_id;
    //             $key = $poNumber . '-' . $combinationId;

    //             if (isset($aggregatedData[$key])) {
    //                 foreach ($cut->cut_quantities as $sizeIdOrName => $quantity) {
    //                     // Normalize size name (handle both IDs and names)
    //                     $sizeName = is_numeric($sizeIdOrName) ?
    //                         ($sizeIdToNameMap[(string)$sizeIdOrName] ?? 'unknown') :
    //                         $sizeIdOrName;

    //                     $aggregatedData[$key]['cut_quantities'][$sizeName] =
    //                         ($aggregatedData[$key]['cut_quantities'][$sizeName] ?? 0) + (int)$quantity;
    //                 }
    //             }
    //         }

    //         foreach ($aggregatedData as $data) {
    //             $availableQuantities = [];
    //             foreach ($data['order_quantities'] as $sizeName => $orderQty) {
    //                 $cutQty = $data['cut_quantities'][$sizeName] ?? 0;

    //                 // Calculate maximum allowed (order quantity + 5%)
    //                 $maxAllowed = ceil($orderQty * 1.05);

    //                 // Calculate available quantity (max allowed minus already cut)
    //                 $available = max(0, $maxAllowed - $cutQty);

    //                 $availableQuantities[$sizeName] = $available;
    //             }

    //             $response[$data['po_number']][] = [
    //                 'combination_id' => $data['combination_id'],
    //                 'style' => $data['style'],
    //                 'color' => $data['color'],
    //                 'size_ids' => $data['size_ids'],
    //                 'available_quantities' => $availableQuantities,
    //             ];
    //         }

    //         Log::info('Response Data:', $response);
    //         return response()->json($response);
    //     }
    //     public function edit(CuttingData $cuttingDatum)
    //     {
    //         $cuttingDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');

    //         $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //         $poOrderData = OrderData::where('po_number', $cuttingDatum->po_number)
    //             ->where('product_combination_id', $cuttingDatum->product_combination_id)
    //             ->first();

    //         if (!$poOrderData) {
    //             return redirect()->route('cutting_data.index')->withErrors('Order data for this PO not found.');
    //         }

    //         // Fetch existing quantities from all other cutting data records for this PO
    //         $totalExistingCutQuantities = CuttingData::where('po_number', $cuttingDatum->po_number)
    //             ->where('product_combination_id', $cuttingDatum->product_combination_id)
    //             ->where('id', '!=', $cuttingDatum->id)
    //             ->get()
    //             ->flatMap(fn($data) => $data->cut_quantities)
    //             ->reduce(function ($carry, $quantity, $sizeId) {
    //                 $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $quantity;
    //                 return $carry;
    //             }, []);

    //         $totalExistingWasteQuantities = CuttingData::where('po_number', $cuttingDatum->po_number)
    //             ->where('product_combination_id', $cuttingDatum->product_combination_id)
    //             ->where('id', '!=', $cuttingDatum->id)
    //             ->get()
    //             ->flatMap(fn($data) => $data->cut_waste_quantities)
    //             ->reduce(function ($carry, $quantity, $sizeId) {
    //                 $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $quantity;
    //                 return $carry;
    //             }, []);

    //         $orderQuantities = $poOrderData->order_quantities;
    //         $availableQuantities = [];

    //         foreach ($orderQuantities as $sizeId => $orderQty) {
    //             $existingCutQty = $totalExistingCutQuantities[$sizeId] ?? 0;
    //             $existingWasteQty = $totalExistingWasteQuantities[$sizeId] ?? 0;
    //             $availableQuantities[$sizeId] = $orderQty - ($existingCutQty + $existingWasteQty);
    //         }

    //         return view('backend.library.cutting_data.edit', compact(
    //             'cuttingDatum',
    //             'allSizes',
    //             'availableQuantities',
    //             'orderQuantities'
    //         ));
    //     }

    //     public function update(Request $request, CuttingData $cuttingDatum)
    //     {
    //         $request->validate([
    //             'date' => 'required|date',
    //             'cut_quantities' => 'nullable|array',
    //             'cut_quantities.*' => 'nullable|integer|min:0',
    //             'waste_quantities' => 'nullable|array',
    //             'waste_quantities.*' => 'nullable|integer|min:0',
    //         ]);

    //         $poNumber = $cuttingDatum->po_number;
    //         $productCombinationId = $cuttingDatum->product_combination_id;

    //         $poOrderData = OrderData::where('po_number', $poNumber)->where('product_combination_id', $productCombinationId)->first();
    //         if (!$poOrderData) {
    //             return redirect()->back()->withErrors('Order data for this PO not found.');
    //         }
    //         $orderQuantities = $poOrderData->order_quantities;

    //         // Sum existing quantities from all records EXCEPT the current one
    //         $existingCuttingData = CuttingData::where('po_number', $poNumber)
    //             ->where('product_combination_id', $productCombinationId)
    //             ->where('id', '!=', $cuttingDatum->id)
    //             ->get();
    //         $totalExistingCut = $existingCuttingData->sum('total_cut_quantity');
    //         $totalExistingWaste = $existingCuttingData->sum('total_cut_waste_quantity');

    //         // Sum new quantities from the request
    //         $newCutQuantities = array_filter($request->input('cut_quantities', []), 'is_numeric');
    //         $newWasteQuantities = array_filter($request->input('waste_quantities', []), 'is_numeric');
    //         $totalNewCut = array_sum($newCutQuantities);
    //         $totalNewWaste = array_sum($newWasteQuantities);

    //         // Check if total new + total existing quantities exceed total order quantity
    //         $totalOrderQty = array_sum($orderQuantities);
    //         if (($totalNewCut + $totalNewWaste + $totalExistingCut + $totalExistingWaste) > $totalOrderQty) {
    //             throw ValidationException::withMessages([
    //                 'cut_quantities' => "The updated quantities exceed the total order quantity for this combination."
    //             ]);
    //         }

    //         // Update the model with the new quantities
    //         $cuttingDatum->update([
    //             'date' => $request->date,
    //             'cut_quantities' => $newCutQuantities,
    //             'total_cut_quantity' => $totalNewCut,
    //             'cut_waste_quantities' => $newWasteQuantities,
    //             'total_cut_waste_quantity' => $totalNewWaste,
    //         ]);

    //         return redirect()->route('cutting_data.index')->withMessage( 'Cutting data updated successfully.');
    //     }

    //     public function show(CuttingData $cuttingDatum)
    //     {
    //         $cuttingDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
    //         $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //         return view('backend.library.cutting_data.show', compact('cuttingDatum', 'allSizes'));
    //     }

    //     public function destroy(CuttingData $cuttingDatum)
    //     {
    //         $cuttingDatum->delete();
    //         return redirect()->route('cutting_data.index')->withMessage( 'Cutting data deleted successfully.');
    //     }


    //     public function createWaste()
    //     {
    //         $distinctPoNumbers = CuttingData::distinct()->pluck('po_number');
    //         $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //         return view('backend.library.cutting_data.waste_create', compact('allSizes', 'distinctPoNumbers'));
    //     }

    //     public function storeWaste(Request $request)
    //     {
    //         $request->validate([
    //             'date' => 'required|date',
    //             'po_number' => 'required|array',
    //             'po_number.*' => 'string',
    //             'rows' => 'required|array',
    //             'rows.*.po_number' => 'required|string',
    //             'rows.*.product_combination_id' => 'required|integer',
    //             'rows.*.waste_quantities' => 'nullable|array',
    //             'rows.*.waste_quantities.*' => 'nullable|integer|min:0',
    //         ]);

    //         foreach ($request->rows as $index => $row) {
    //             $poNumber = $row['po_number'];
    //             $productCombinationId = $row['product_combination_id'];
    //             $wasteQuantities = array_filter($row['waste_quantities'] ?? [], 'is_numeric');

    //             if (empty($wasteQuantities)) {
    //                 continue;
    //             }

    //             $totalWasteQty = array_sum($wasteQuantities);

    //             // Find existing record to update
    //             $cuttingData = CuttingData::where('po_number', $poNumber)
    //                 ->where('product_combination_id', $productCombinationId)
    //                 ->first();

    //             if ($cuttingData) {
    //                 // Merge and sum new waste with existing waste quantities
    //                 $existingWasteQuantities = $cuttingData->cut_waste_quantities ?? [];
    //                 $newWasteQuantities = $existingWasteQuantities;

    //                 foreach ($wasteQuantities as $sizeId => $qty) {
    //                     $sizeName = Size::find($sizeId)->name ?? $sizeId; // Assuming size is saved by name
    //                     $newWasteQuantities[$sizeName] = ($newWasteQuantities[$sizeName] ?? 0) + $qty;
    //                 }

    //                 $cuttingData->update([
    //                     'cut_waste_quantities' => $newWasteQuantities,
    //                     'total_cut_waste_quantity' => $cuttingData->total_cut_waste_quantity + $totalWasteQty,
    //                 ]);
    //             } else {
    //                 // If no cutting data exists yet, create a new entry for waste
    //                 CuttingData::create([
    //                     'date' => $request->date,
    //                     'po_number' => $poNumber,
    //                     'old_order' => 'no', // or a default value
    //                     'product_combination_id' => $productCombinationId,
    //                     'cut_quantities' => [], // No cut quantities
    //                     'total_cut_quantity' => 0,
    //                     'cut_waste_quantities' => $wasteQuantities,
    //                     'total_cut_waste_quantity' => $totalWasteQty,
    //                 ]);
    //             }
    //         }

    //         return redirect()->route('cutting_data.index')->withMessage( 'Waste data saved successfully.');
    //     }

    public function findWaste(Request $request)
    {
        $poNumbers = $request->input('po_numbers', []);
        if (empty($poNumbers)) {
            return response()->json([]);
        }

        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $sizeIdToNameMap = $allSizes->keyBy('id')->map(fn($size) => $size->name);

        $cuttingData = CuttingData::whereIn('po_number', $poNumbers)
            ->with(['productCombination.style', 'productCombination.color'])
            ->get();

        $response = [];
        foreach ($cuttingData as $cut) {
            $poNumber = $cut->po_number;
            $combinationId = $cut->product_combination_id;

            // Fetch order data to get all sizes for this combination
            $orderData = OrderData::where('po_number', $poNumber)
                ->where('product_combination_id', $combinationId)
                ->first();

            if (!$orderData) continue;

            $sizeIds = $orderData->productCombination->size_ids ?? [];

            $response[$poNumber][] = [
                'combination_id' => $combinationId,
                'style' => $cut->productCombination->style->name ?? 'Unknown',
                'color' => $cut->productCombination->color->name ?? 'Unknown',
                'size_ids' => $sizeIds,
                'cut_quantities' => $cut->cut_quantities,
                'cut_waste_quantities' => $cut->cut_waste_quantities,
            ];
        }

        return response()->json($response);
    }

    public function cutting_data_report(Request $request)
    {
        $distinctPoNumbers = CuttingData::distinct()->pluck('po_number');
        $query = CuttingData::with('productCombination.style', 'productCombination.color');


        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }
        if ($request->filled('style_id')) {
            $query->whereHas('productCombination', function ($q) use ($request) {
                $q->where('style_id', $request->style_id);
            });
        }
        if ($request->filled('color_id')) {
            $query->whereHas('productCombination', function ($q) use ($request) {
                $q->where('color_id', $request->color_id);
            });
        }
        if ($request->filled('po_number')) {
            $query->where('po_number', $request->po_number);
        }

        $cuttingData = $query->get();

        // Ensure cut_quantities and cut_waste_quantities are treated as arrays
        $cuttingData->each(function ($data) {
            $data->cut_quantities = is_array($data->cut_quantities)
                ? array_map(fn($qty) => $qty ?? 0, $data->cut_quantities)
                : [];

            $data->cut_waste_quantities = is_array($data->cut_waste_quantities)
                ? array_map(fn($qty) => $qty ?? 0, $data->cut_waste_quantities)
                : [];
        });

        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $reportData = [];

        // Get order data for comparison
        $poNumbers = $cuttingData->pluck('po_number')->unique();
        $productCombinationIds = $cuttingData->pluck('product_combination_id')->unique();

        $orderData = OrderData::whereIn('po_number', $poNumbers)
            ->whereIn('product_combination_id', $productCombinationIds)
            ->get()
            ->groupBy(['po_number', 'product_combination_id']);

        $sizeIdToNameMap = $allSizes->keyBy('id')->map(function ($size) {
            return $size->name;
        });

        foreach ($cuttingData as $data) {
            $styleName = $data->productCombination->style->name;
            $colorName = $data->productCombination->color->name;
            $key = $styleName . '-' . $colorName . '-' . $data->po_number;

            if (!isset($reportData[$key])) {
                $reportData[$key] = [
                    'style' => $styleName,
                    'color' => $colorName,
                    'po_number' => $data->po_number,
                    'cut_sizes' => [],
                    'waste_sizes' => [],
                    'order_sizes' => [],
                    'total_cut' => 0,
                    'total_waste' => 0,
                    'total_order' => 0,
                ];

                foreach ($allSizes as $size) {
                    $reportData[$key]['cut_sizes'][$size->id] = 0;
                    $reportData[$key]['waste_sizes'][$size->id] = 0;
                    $reportData[$key]['order_sizes'][$size->id] = 0;
                }

                // Add order quantities if available
                if (isset($orderData[$data->po_number][$data->product_combination_id])) {
                    $order = $orderData[$data->po_number][$data->product_combination_id]->first();
                    foreach ($order->order_quantities as $sizeId => $quantity) {
                        $reportData[$key]['order_sizes'][$sizeId] = $quantity;
                        $reportData[$key]['total_order'] += $quantity;
                    }
                }
            }

            // Add cutting quantities
            foreach ($data->cut_quantities as $sizeId => $quantity) {
                if (array_key_exists($sizeId, $reportData[$key]['cut_sizes'])) {
                    $reportData[$key]['cut_sizes'][$sizeId] += $quantity;
                    $reportData[$key]['total_cut'] += $quantity;
                }
            }

            // Add waste quantities
            foreach ($data->cut_waste_quantities as $sizeId => $quantity) {
                if (array_key_exists($sizeId, $reportData[$key]['waste_sizes'])) {
                    $reportData[$key]['waste_sizes'][$sizeId] += $quantity;
                    $reportData[$key]['total_waste'] += $quantity;
                }
            }
        }

        $reportData = array_values($reportData);

        $styles = Style::all();
        $colors = Color::all();

        return view('backend.library.cutting_data.report', compact(
            'reportData',
            'allSizes',
            'styles',
            'colors',
            'distinctPoNumbers'
        ));
    }

    public function cutting_requisition()
    {
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $distinctPoNumbers = OrderData::where('po_status', 'running')->distinct()->pluck('po_number');

        return view('backend.library.cutting_data.cutting_requisition', compact('allSizes', 'distinctPoNumbers'));
    }
    public function cutting_requisition_find(Request $request)
    {
        $poNumbers = $request->input('po_numbers', []);
        // Log::info('PO Numbers for Requisition Find:', $poNumbers); // For debugging

        if (empty($poNumbers)) {
            return response()->json([]);
        }

        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $sizeIdToNameMap = $allSizes->keyBy('id')->map(fn($size) => $size->name);
        $sizeNameToIdMap = $allSizes->keyBy('name')->map(fn($size) => $size->id); // Added for reverse lookup if needed

        $orderData = OrderData::whereIn('po_number', $poNumbers)
            ->with(['productCombination.style', 'productCombination.color'])
            ->get();
        // Log::info('Order Data for Requisition Find:', $orderData->toArray()); // For debugging

        $existingCuttingData = CuttingData::whereIn('po_number', $poNumbers)->get();
        // Log::info('Cutting Data for Requisition Find:', $existingCuttingData->toArray()); // For debugging

        $response = []; // Final structured response
        $aggregatedData = []; // Intermediate aggregation

        foreach ($orderData as $order) {
            $poNumber = $order->po_number;
            $combinationId = $order->product_combination_id;
            $key = $poNumber . '-' . $combinationId;

            if (!isset($aggregatedData[$key])) {
                $sizeIds = $order->productCombination ? $order->productCombination->size_ids : [];
                $aggregatedData[$key] = [
                    'po_number' => $poNumber,
                    'combination_id' => $combinationId,
                    'style' => $order->productCombination->style->name ?? 'Unknown',
                    'color' => $order->productCombination->color->name ?? 'Unknown',
                    'size_ids' => $sizeIds, // Original size IDs for this combination
                    'order_quantities' => [], // Actual order quantities per size name
                    'cut_quantities' => [],    // Already cut quantities per size name
                ];
            }

            foreach ($order->order_quantities as $sizeId => $quantity) {
                $sizeName = $sizeIdToNameMap[(string)$sizeId] ?? 'unknown';
                $aggregatedData[$key]['order_quantities'][$sizeName] =
                    ($aggregatedData[$key]['order_quantities'][$sizeName] ?? 0) + (int)$quantity;
            }
        }

        foreach ($existingCuttingData as $cut) {
            $poNumber = $cut->po_number;
            $combinationId = $cut->product_combination_id;
            $key = $poNumber . '-' . $combinationId;

            if (isset($aggregatedData[$key])) {
                foreach ($cut->cut_quantities as $sizeIdOrName => $quantity) {
                    // Normalize size name (handle both IDs and names from existing data)
                    $sizeName = is_numeric($sizeIdOrName) ?
                        ($sizeIdToNameMap[(string)$sizeIdOrName] ?? 'unknown') :
                        $sizeIdOrName;

                    $aggregatedData[$key]['cut_quantities'][$sizeName] =
                        ($aggregatedData[$key]['cut_quantities'][$sizeName] ?? 0) + (int)$quantity;
                }
            }
        }

        $poTotals = []; // To store the total max allowed for each PO

        foreach ($aggregatedData as $data) {
            $requisitionQuantities = []; // These will be our 'available' or 'requisition' quantities
            $combinationTotalRequisitionQty = 0; // Total requisition for this style/color combination

            foreach ($data['order_quantities'] as $sizeName => $orderQty) {
                $cutQty = $data['cut_quantities'][$sizeName] ?? 0;

                // Calculate maximum allowed (order quantity + 3%)
                $maxAllowed = ceil($orderQty * 1.03);

                // Calculate requisition quantity (max allowed minus already cut)
                $requisitionQty = max(0, $maxAllowed - $cutQty);

                $requisitionQuantities[$sizeName] = $requisitionQty;
                $combinationTotalRequisitionQty += $requisitionQty;

                // Sum up for PO Total Max Value (this represents the max potential for each size for the PO)
                // Initialize if not set
                if (!isset($poTotals[$data['po_number']])) {
                    $poTotals[$data['po_number']] = 0;
                }
                // Add the *maxAllowed* for this size to the PO's total
                $poTotals[$data['po_number']] += $maxAllowed;
            }

            $response[$data['po_number']][] = [
                'combination_id' => $data['combination_id'],
                'style' => $data['style'],
                'color' => $data['color'],
                'size_ids' => $data['size_ids'], // Still useful to know which sizes are relevant
                'requisition_quantities' => $requisitionQuantities, // Renamed from available_quantities
                'combination_total_requisition_qty' => $combinationTotalRequisitionQty, // New field for row total
            ];
        }

        // Add PO Total Max Value to the overall response
        $finalResponse = [
            'data' => $response,
            'po_totals_max_allowed' => $poTotals, // This will be the sum of all (order + 3%) for each PO
        ];

        // Log::info('Final Requisition Report Data:', $finalResponse); // For debugging
        return response()->json($finalResponse);
    }

    // public function cutting_requisition_find(Request $request)
    // {
    //     $poNumbers = $request->input('po_numbers', []);
    //     Log::info('PO Numbers:', $poNumbers);

    //     if (empty($poNumbers)) {
    //         return response()->json([]);
    //     }

    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     Log::info('Sizes:', $allSizes->toArray());
    //     $sizeIdToNameMap = $allSizes->keyBy('id')->map(fn($size) => $size->name);

    //     $orderData = OrderData::whereIn('po_number', $poNumbers)
    //         ->with(['productCombination.style', 'productCombination.color'])
    //         ->get();
    //     Log::info('Order Data:', $orderData->toArray());

    //     $existingCuttingData = CuttingData::whereIn('po_number', $poNumbers)->get();
    //     Log::info('Cutting Data:', $existingCuttingData->toArray());

    //     $response = [];
    //     $aggregatedData = [];

    //     foreach ($orderData as $order) {
    //         $poNumber = $order->po_number;
    //         $combinationId = $order->product_combination_id;
    //         $key = $poNumber . '-' . $combinationId;

    //         if (!isset($aggregatedData[$key])) {
    //             $sizeIds = $order->productCombination ? $order->productCombination->size_ids : [];
    //             $aggregatedData[$key] = [
    //                 'po_number' => $poNumber,
    //                 'combination_id' => $combinationId,
    //                 'style' => $order->productCombination->style->name ?? 'Unknown',
    //                 'color' => $order->productCombination->color->name ?? 'Unknown',
    //                 'size_ids' => $sizeIds,
    //                 'order_quantities' => [],
    //                 'cut_quantities' => [],
    //             ];
    //         }

    //         foreach ($order->order_quantities as $sizeId => $quantity) {
    //             $sizeName = $sizeIdToNameMap[(string)$sizeId] ?? 'unknown';
    //             $aggregatedData[$key]['order_quantities'][$sizeName] =
    //                 ($aggregatedData[$key]['order_quantities'][$sizeName] ?? 0) + (int)$quantity;
    //         }
    //     }

    //     foreach ($existingCuttingData as $cut) {
    //         $poNumber = $cut->po_number;
    //         $combinationId = $cut->product_combination_id;
    //         $key = $poNumber . '-' . $combinationId;

    //         if (isset($aggregatedData[$key])) {
    //             foreach ($cut->cut_quantities as $sizeIdOrName => $quantity) {
    //                 // Normalize size name (handle both IDs and names)
    //                 $sizeName = is_numeric($sizeIdOrName) ?
    //                     ($sizeIdToNameMap[(string)$sizeIdOrName] ?? 'unknown') :
    //                     $sizeIdOrName;

    //                 $aggregatedData[$key]['cut_quantities'][$sizeName] =
    //                     ($aggregatedData[$key]['cut_quantities'][$sizeName] ?? 0) + (int)$quantity;
    //             }
    //         }
    //     }

    //     foreach ($aggregatedData as $data) {
    //         $availableQuantities = [];
    //         foreach ($data['order_quantities'] as $sizeName => $orderQty) {
    //             $cutQty = $data['cut_quantities'][$sizeName] ?? 0;

    //             // Calculate maximum allowed (order quantity + 3%)
    //             $maxAllowed = ceil($orderQty * 1.03);

    //             // Calculate available quantity (max allowed minus already cut)
    //             $available = max(0, $maxAllowed - $cutQty);

    //             $availableQuantities[$sizeName] = $available;
    //         }

    //         $response[$data['po_number']][] = [
    //             'combination_id' => $data['combination_id'],
    //             'style' => $data['style'],
    //             'color' => $data['color'],
    //             'size_ids' => $data['size_ids'],
    //             'available_quantities' => $availableQuantities,
    //         ];
    //     }

    //     Log::info('Response Data:', $response);
    //     return response()->json($response);
    // }
}
