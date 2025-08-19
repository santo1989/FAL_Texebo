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

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('po_number', 'like', '%' . $search . '%')
                ->orWhereHas('productCombination.style', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('productCombination.color', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                });
        }
        if ($request->filled('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        $cuttingData = $query->orderBy('date', 'desc')->paginate(10);
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();

        return view('backend.library.cutting_data.index', compact('cuttingData', 'allSizes'));
    }

    public function create()
    {
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $distinctPoNumbers = OrderData::where('po_status', 'running')->distinct()->pluck('po_number');

        return view('backend.library.cutting_data.create', compact('allSizes', 'distinctPoNumbers'));
    }

    public function store(Request $request)
    {
        Log::info('Store Request Data:', $request->all());

        // Use the raw request data for validation, as it's correctly structured.
        $request->validate([
            'date' => 'required|date',
            'po_number' => 'required|array',
            'po_number.*' => 'string',
            'old_order' => 'required|in:yes,no',
            'rows' => 'required|array',
            'rows.*.po_number' => 'required|string|in:' . implode(',', $request->input('po_number', [])),
            'rows.*.product_combination_id' => 'required|integer|exists:product_combinations,id',
            'rows.*.cut_quantities' => 'nullable|array',
            'rows.*.waste_quantities' => 'nullable|array',
            'rows.*.cut_quantities.*' => 'nullable|integer|min:0',
            'rows.*.waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        foreach ($request->rows as $index => $row) {
            $poNumber = $row['po_number'];
            $productCombinationId = $row['product_combination_id'];

            $cutQuantities = array_filter($row['cut_quantities'] ?? [], 'is_numeric');
            $wasteQuantities = array_filter($row['waste_quantities'] ?? [], 'is_numeric');

            if (empty($cutQuantities) && empty($wasteQuantities)) {
                Log::info("Skipping row {$index} for PO {$poNumber}: No quantities provided.");
                continue;
            }

            // Aggregate total quantities directly from the input
            $totalCutQty = array_sum($cutQuantities);
            $totalWasteQty = array_sum($wasteQuantities);

            // Fetch order data for validation
            $orderData = OrderData::where('po_number', $poNumber)
                ->where('product_combination_id', $productCombinationId)
                ->first();

            if (!$orderData) {
                throw ValidationException::withMessages([
                    "rows.{$index}.po_number" => "Order data not found for PO: {$poNumber} and combination ID: {$productCombinationId}"
                ]);
            }

            // Get total existing quantities
            $existingCuttingData = CuttingData::where('po_number', $poNumber)
                ->where('product_combination_id', $productCombinationId)
                ->get();

            $totalExistingCut = $existingCuttingData->sum('total_cut_quantity');
            $totalExistingWaste = $existingCuttingData->sum('total_cut_waste_quantity');
            $totalOrderQty = array_sum($orderData->order_quantities);

            // Validate that new quantities don't exceed remaining order quantity
            if (($totalCutQty + $totalWasteQty + $totalExistingCut + $totalExistingWaste) > $totalOrderQty) {
                throw ValidationException::withMessages([
                    "rows.{$index}" => "Total quantities for PO: {$poNumber} exceed the total order quantity."
                ]);
            }

            // Create the new CuttingData record
            CuttingData::create([
                'date' => $request->date,
                'po_number' => $poNumber,
                'old_order' => $request->old_order,
                'product_combination_id' => $productCombinationId,
                'cut_quantities' => $cutQuantities,
                'total_cut_quantity' => $totalCutQty,
                'cut_waste_quantities' => $wasteQuantities,
                'total_cut_waste_quantity' => $totalWasteQty,
            ]);

            Log::info("Created cutting data for PO: {$poNumber}, Combination ID: {$productCombinationId}", [
                'cut_quantities' => $cutQuantities,
                'waste_quantities' => $wasteQuantities,
            ]);
        }

        return redirect()->route('cutting_data.index')->with('success', 'Cutting data saved successfully.');
    }

    public function find(Request $request)
    {
        $poNumbers = $request->input('po_numbers', []);
        Log::info('PO Numbers:', $poNumbers);

        if (empty($poNumbers)) {
            return response()->json([]);
        }

        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
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
                    'cut_waste_quantities' => [],
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
                foreach ($cut->cut_quantities as $sizeName => $quantity) {
                    $aggregatedData[$key]['cut_quantities'][$sizeName] =
                        ($aggregatedData[$key]['cut_quantities'][$sizeName] ?? 0) + (int)$quantity;
                }
                foreach ($cut->cut_waste_quantities as $sizeName => $quantity) {
                    $aggregatedData[$key]['cut_waste_quantities'][$sizeName] =
                        ($aggregatedData[$key]['cut_waste_quantities'][$sizeName] ?? 0) + (int)$quantity;
                }
            }
        }

        foreach ($aggregatedData as $data) {
            $availableQuantities = [];
            foreach ($data['order_quantities'] as $sizeName => $orderQty) {
                $cutQty = $data['cut_quantities'][$sizeName] ?? 0;
                $wasteQty = $data['cut_waste_quantities'][$sizeName] ?? 0;
                $availableQuantities[$sizeName] = max(0, $orderQty - ($cutQty + $wasteQty));
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

        $totalExistingWasteQuantities = CuttingData::where('po_number', $cuttingDatum->po_number)
            ->where('product_combination_id', $cuttingDatum->product_combination_id)
            ->where('id', '!=', $cuttingDatum->id)
            ->get()
            ->flatMap(fn($data) => $data->cut_waste_quantities)
            ->reduce(function ($carry, $quantity, $sizeId) {
                $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $quantity;
                return $carry;
            }, []);

        $orderQuantities = $poOrderData->order_quantities;
        $availableQuantities = [];

        foreach ($orderQuantities as $sizeId => $orderQty) {
            $existingCutQty = $totalExistingCutQuantities[$sizeId] ?? 0;
            $existingWasteQty = $totalExistingWasteQuantities[$sizeId] ?? 0;
            $availableQuantities[$sizeId] = $orderQty - ($existingCutQty + $existingWasteQty);
        }

        return view('backend.library.cutting_data.edit', compact(
            'cuttingDatum',
            'allSizes',
            'availableQuantities',
            'orderQuantities'
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
        $orderQuantities = $poOrderData->order_quantities;

        // Sum existing quantities from all records EXCEPT the current one
        $existingCuttingData = CuttingData::where('po_number', $poNumber)
            ->where('product_combination_id', $productCombinationId)
            ->where('id', '!=', $cuttingDatum->id)
            ->get();
        $totalExistingCut = $existingCuttingData->sum('total_cut_quantity');
        $totalExistingWaste = $existingCuttingData->sum('total_cut_waste_quantity');

        // Sum new quantities from the request
        $newCutQuantities = array_filter($request->input('cut_quantities', []), 'is_numeric');
        $newWasteQuantities = array_filter($request->input('waste_quantities', []), 'is_numeric');
        $totalNewCut = array_sum($newCutQuantities);
        $totalNewWaste = array_sum($newWasteQuantities);

        // Check if total new + total existing quantities exceed total order quantity
        $totalOrderQty = array_sum($orderQuantities);
        if (($totalNewCut + $totalNewWaste + $totalExistingCut + $totalExistingWaste) > $totalOrderQty) {
            throw ValidationException::withMessages([
                'cut_quantities' => "The updated quantities exceed the total order quantity for this combination."
            ]);
        }

        // Update the model with the new quantities
        $cuttingDatum->update([
            'date' => $request->date,
            'cut_quantities' => $newCutQuantities,
            'total_cut_quantity' => $totalNewCut,
            'cut_waste_quantities' => $newWasteQuantities,
            'total_cut_waste_quantity' => $totalNewWaste,
        ]);

        return redirect()->route('cutting_data.index')->with('success', 'Cutting data updated successfully.');
    }

    public function show(CuttingData $cuttingDatum)
    {
        $cuttingDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();

        return view('backend.library.cutting_data.show', compact('cuttingDatum', 'allSizes'));
    }

    public function destroy(CuttingData $cuttingDatum)
    {
        $cuttingDatum->delete();
        return redirect()->route('cutting_data.index')->with('success', 'Cutting data deleted successfully.');
    }

    public function cutting_data_report(Request $request)
    {
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

        $cuttingData = $query->get();
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $reportData = [];

        foreach ($cuttingData as $data) {
            $styleName = $data->productCombination->style->name;
            $colorName = $data->productCombination->color->name;
            $key = $styleName . '-' . $colorName;

            if (!isset($reportData[$key])) {
                $reportData[$key] = [
                    'style' => $styleName,
                    'color' => $colorName,
                    'sizes' => [],
                    'total' => 0,
                ];
                foreach ($allSizes as $size) {
                    $reportData[$key]['sizes'][$size->name] = 0;
                }
            }

            foreach ($data->cut_quantities as $sizeName => $quantity) {
                if (array_key_exists($sizeName, $reportData[$key]['sizes'])) {
                    $reportData[$key]['sizes'][$sizeName] += $quantity;
                }
            }
            $reportData[$key]['total'] += $data->total_cut_quantity;
        }

        $reportData = array_values($reportData);

        $styles = Style::all();
        $colors = Color::all();

        return view('backend.library.cutting_data.report', compact(
            'reportData',
            'allSizes',
            'styles',
            'colors'
        ));
    }

    // public function getOrderAndCuttingQuantities($productCombinationId)
    // {
    //     try {
    //         $orderData = OrderData::where('product_combination_id', $productCombinationId)->get();
    //         $totalOrderQuantities = [];
    //         foreach ($orderData as $data) {
    //             foreach ($data->order_quantities as $size => $quantity) {
    //                 $totalOrderQuantities[$size] = ($totalOrderQuantities[$size] ?? 0) + (int)$quantity;
    //             }
    //         }

    //         $cuttingData = CuttingData::where('product_combination_id', $productCombinationId)->get();
    //         $totalCuttingQuantities = [];
    //         foreach ($cuttingData as $data) {
    //             foreach ($data->cut_quantities as $size => $quantity) {
    //                 $totalCuttingQuantities[$size] = ($totalCuttingQuantities[$size] ?? 0) + (int)$quantity;
    //             }
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'order_quantities' => $totalOrderQuantities,
    //             'cutting_quantities' => $totalCuttingQuantities,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error fetching quantities',
    //         ], 500);
    //     }
    // }
}
