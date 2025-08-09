<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\CuttingData;
use App\Models\OrderData;
use App\Models\ProductCombination;
use App\Models\Size; // We'll need this to display size names
use App\Models\Style;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CuttingDataController extends Controller
{
    public function index(Request $request)
    {
        $query = CuttingData::with('po_data', 'productCombination.style', 'productCombination.color');

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
        // Fetch distinct PO numbers that are "running" and have not been fully cut
        $distinctPoNumbers = OrderData::where('po_status', 'running')->distinct()->pluck('po_number');

        return view('backend.library.cutting_data.create', compact('allSizes', 'distinctPoNumbers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'rows' => 'required|array',
        ]);

        $date = $request->date;
        $rows = $request->rows;

        foreach ($rows as $row) {
            $poNumber = $row['po_number'];
            $productCombinationId = $row['product_combination_id'];
            $cutQuantities = $row['cut_quantities'] ?? [];
            $wasteQuantities = $row['waste_quantities'] ?? [];

            // Fetch Order Data for this PO and combination
            $orderData = OrderData::where('po_number', $poNumber)
                ->where('product_combination_id', $productCombinationId)
                ->get();

            if ($orderData->isEmpty()) {
                throw ValidationException::withMessages([
                    'po_number' => "Order data not found for PO: $poNumber and combination ID: $productCombinationId"
                ]);
            }

            // Aggregate total order quantities
            $totalOrderQuantities = [];
            foreach ($orderData as $order) {
                $orderQuantities = $order->order_quantities;
                foreach ($orderQuantities as $sizeName => $quantity) {
                    $sizeNameLower = strtolower($sizeName);
                    $totalOrderQuantities[$sizeNameLower] =
                        ($totalOrderQuantities[$sizeNameLower] ?? 0) + $quantity;
                }
            }

            // Fetch existing cutting data for this PO and combination
            $existingCuttingData = CuttingData::where('po_number', $poNumber)
                ->where('product_combination_id', $productCombinationId)
                ->get();

            // Aggregate existing cutting quantities
            $totalExistingCutQuantities = [];
            foreach ($existingCuttingData as $cutting) {
                $cuttingQuantities = $cutting->cut_quantities;
                foreach ($cuttingQuantities as $sizeName => $quantity) {
                    $sizeNameLower = strtolower($sizeName);
                    $totalExistingCutQuantities[$sizeNameLower] =
                        ($totalExistingCutQuantities[$sizeNameLower] ?? 0) + $quantity;
                }
            }

            $newCutQuantities = [];
            $newWasteQuantities = [];
            $totalCutQty = 0;
            $totalWasteQty = 0;

            $allSizes = Size::where('is_active', 1)->get();
            foreach ($allSizes as $size) {
                $sizeName = $size->name;
                $sizeNameLower = strtolower($sizeName);
                $sizeId = $size->id;

                $newCutQty = (int)($cutQuantities[$sizeId] ?? 0);
                $newWasteQty = (int)($wasteQuantities[$sizeId] ?? 0);

                $existingCutQty = $totalExistingCutQuantities[$sizeNameLower] ?? 0;
                $orderQty = $totalOrderQuantities[$sizeNameLower] ?? 0;
                $availableQty = $orderQty - $existingCutQty;

                if ($newCutQty > $availableQty) {
                    throw ValidationException::withMessages([
                        'cut_quantities' => "Cut quantity for size '$sizeName' exceeds available quantity ($availableQty) for PO: $poNumber"
                    ]);
                }

                if ($newCutQty > 0) {
                    $newCutQuantities[$sizeName] = $newCutQty;
                    $totalCutQty += $newCutQty;
                }
                if ($newWasteQty > 0) {
                    $newWasteQuantities[$sizeName] = $newWasteQty;
                    $totalWasteQty += $newWasteQty;
                }
            }

            if ($totalCutQty > 0) {
                CuttingData::create([
                    'date' => $date,
                    'po_number' => $poNumber,
                    'product_combination_id' => $productCombinationId,
                    'cut_quantities' => $newCutQuantities,
                    'total_cut_quantity' => $totalCutQty,
                    'cut_waste_quantities' => $newWasteQuantities,
                    'total_cut_waste_quantity' => $totalWasteQty,
                ]);
            }
        }

        return redirect()->route('cutting_data.index')->with('success', 'Cutting data added successfully.');
    }

    public function cutting_data_find(Request $request)
    {
        $poNumbers = $request->input('po_numbers', []);

        // Create size ID to name mapping
        $sizes = Size::all();
        $sizeMap = [];
        foreach ($sizes as $size) {
            $sizeMap[$size->id] = strtolower($size->name);
        }

        // Fetch all related order data and existing cutting data
        $orderData = OrderData::whereIn('po_number', $poNumbers)
            ->with('productCombination.style', 'productCombination.color')
            ->get();

        $existingCuttingData = CuttingData::whereIn('po_number', $poNumbers)->get();

        $response = [];
        $groupedData = $orderData->groupBy('po_number');

        foreach ($groupedData as $poNumber => $orders) {
            $poData = [];
            foreach ($orders as $order) {
                $combinationId = $order->product_combination_id;

                if (!isset($poData[$combinationId])) {
                    $poData[$combinationId] = [
                        'combination_id' => $combinationId,
                        'style' => $order->productCombination->style->name,
                        'color' => $order->productCombination->color->name,
                        'order_quantities' => [],
                        'cut_quantities' => [],
                    ];
                }

                // Process order quantities using size map
                $orderQuantities = $order->order_quantities;
                foreach ($orderQuantities as $sizeId => $quantity) {
                    // Convert size ID to integer for mapping
                    $sizeId = (int)$sizeId;
                    $sizeName = $sizeMap[$sizeId] ?? 'unknown';
                    $poData[$combinationId]['order_quantities'][$sizeName] =
                        ($poData[$combinationId]['order_quantities'][$sizeName] ?? 0) + $quantity;
                }
            }

            // Process existing cutting quantities
            foreach ($existingCuttingData as $cut) {
                if ($cut->po_number === $poNumber) {
                    $combinationId = $cut->product_combination_id;
                    if (isset($poData[$combinationId])) {
                        $cutQuantities = $cut->cut_quantities;
                        foreach ($cutQuantities as $sizeName => $quantity) {
                            $sizeNameLower = strtolower($sizeName);
                            $poData[$combinationId]['cut_quantities'][$sizeNameLower] =
                                ($poData[$combinationId]['cut_quantities'][$sizeNameLower] ?? 0) + $quantity;
                        }
                    }
                }
            }

            // Prepare final response
            $finalData = [];
            foreach ($poData as $combinationId => $data) {
                $availableQuantities = [];
                foreach ($data['order_quantities'] as $sizeName => $orderQty) {
                    $cutQty = $data['cut_quantities'][$sizeName] ?? 0;
                    $availableQuantities[$sizeName] = $orderQty - $cutQty;
                }

                $finalData[] = [
                    'combination_id' => $combinationId,
                    'style' => $data['style'],
                    'color' => $data['color'],
                    'available_quantities' => $availableQuantities,
                ];
            }
            $response[$poNumber] = $finalData;
        }

        return response()->json($response);
    }

   
    public function edit(CuttingData $cuttingDatum)
    {
        // Load the cutting datum and its related PO and product combination data
        $cuttingDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');

        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();

        // Fetch all order data for the cuttingDatum's PO number
        $poOrderData = OrderData::where('po_number', $cuttingDatum->po_number)
            ->where('product_combination_id', $cuttingDatum->product_combination_id)
            ->first();

        if (!$poOrderData) {
            return redirect()->route('cutting_data.index')->withErrors('Order data for this PO not found.');
        }

        // Calculate total cut quantities for this PO number and product combination
        $totalCutQuantities = CuttingData::where('po_number', $cuttingDatum->po_number)
            ->where('product_combination_id', $cuttingDatum->product_combination_id)
            ->where('id', '!=', $cuttingDatum->id) // Exclude the current record being edited
            ->get()
            ->flatMap(fn($data) => $data->cut_quantities)
            ->reduce(function ($carry, $quantity, $sizeName) {
                $carry[strtolower($sizeName)] = ($carry[strtolower($sizeName)] ?? 0) + $quantity;
                return $carry;
            }, []);

        // Prepare available quantities with a max limit for each size
        $availableQuantities = [];
        $orderQuantities = json_decode($poOrderData->order_quantities, true);
        foreach ($orderQuantities as $sizeName => $orderQty) {
            $cutQty = $totalCutQuantities[strtolower($sizeName)] ?? 0;
            $availableQuantities[strtolower($sizeName)] = $orderQty - $cutQty;
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
            'cut_quantities.*' => 'nullable|integer|min:0',
            'waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        // Recalculate total cut quantities for validation
        $poNumber = $cuttingDatum->po_number;
        $productCombinationId = $cuttingDatum->product_combination_id;

        $poOrderData = OrderData::where('po_number', $poNumber)->where('product_combination_id', $productCombinationId)->first();
        if (!$poOrderData) {
            return redirect()->back()->withErrors('Order data for this PO not found.');
        }

        $orderQuantities = json_decode($poOrderData->order_quantities, true);

        $totalCutQuantities = CuttingData::where('po_number', $poNumber)
            ->where('product_combination_id', $productCombinationId)
            ->where('id', '!=', $cuttingDatum->id)
            ->get()
            ->flatMap(fn($data) => $data->cut_quantities)
            ->reduce(function ($carry, $quantity, $sizeName) {
                $carry[strtolower($sizeName)] = ($carry[strtolower($sizeName)] ?? 0) + $quantity;
                return $carry;
            }, []);

        $newCutQuantities = [];
        $newWasteQuantities = [];
        $totalCutQty = 0;
        $totalWasteQty = 0;

        foreach ($request->input('cut_quantities', []) as $sizeId => $quantity) {
            $size = Size::find($sizeId);
            if ($size) {
                $sizeName = $size->name;
                $sizeNameLower = strtolower($sizeName);
                $updatedCutQty = (int) $quantity;
                $existingCutQty = $totalCutQuantities[$sizeNameLower] ?? 0;
                $orderQty = $orderQuantities[$sizeName] ?? 0;

                if (($existingCutQty + $updatedCutQty) > $orderQty) {
                    throw ValidationException::withMessages([
                        "cut_quantities.{$sizeId}" => "The updated cut quantity for size '{$sizeName}' exceeds the available order quantity."
                    ]);
                }

                if ($updatedCutQty > 0) {
                    $newCutQuantities[$sizeName] = $updatedCutQty;
                    $totalCutQty += $updatedCutQty;
                }
            }
        }

        foreach ($request->input('waste_quantities', []) as $sizeId => $quantity) {
            $size = Size::find($sizeId);
            if ($size && $quantity > 0) {
                $newWasteQuantities[$size->name] = (int) $quantity;
                $totalWasteQty += (int) $quantity;
            }
        }

        $cuttingDatum->update([
            'date' => $request->date,
            'cut_quantities' => $newCutQuantities,
            'total_cut_quantity' => $totalCutQty,
            'cut_waste_quantities' => $newWasteQuantities,
            'total_cut_waste_quantity' => $totalWasteQty,
        ]);

        return redirect()->route('cutting_data.index')->with('success', 'Cutting data updated successfully.');
    }
    public function show(CuttingData $cuttingDatum) // Laravel automatically injects based on route model binding
    {
        $cuttingDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get(); // Get all possible sizes to display in a structured way

        return view('backend.library.cutting_data.show', compact('cuttingDatum', 'allSizes'));
    }

    


    public function destroy(CuttingData $cuttingDatum) // Using $cuttingDatum
    {
        $cuttingDatum->delete();
        return redirect()->route('cutting_data.index')->with('success', 'Cutting data deleted successfully.');
    }

    public function cutting_data_report(Request $request)
    {
        $query = CuttingData::with('productCombination.style', 'productCombination.color');

        // Apply filters
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
                // Initialize all sizes with zero
                foreach ($allSizes as $size) {
                    $reportData[$key]['sizes'][strtolower($size->name)] = 0;
                }
            }

            // Add quantities
            foreach ($data->cut_quantities as $sizeName => $quantity) {
                $normalizedSize = strtolower($sizeName);
                if (array_key_exists($normalizedSize, $reportData[$key]['sizes'])) {
                    $reportData[$key]['sizes'][$normalizedSize] += $quantity;
                }
            }
            $reportData[$key]['total'] += $data->total_cut_quantity;
        }

        // Convert to indexed array
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

    public function getOrderAndCuttingQuantities($productCombinationId)
    {
        try {
            // Fetch total order quantities
            $orderData = OrderData::where('product_combination_id', $productCombinationId)->get();
            $totalOrderQuantities = [];
            foreach ($orderData as $data) {
                foreach ($data->order_quantities as $size => $quantity) {
                    $normalizedSize = strtolower($size);
                    if (!isset($totalOrderQuantities[$normalizedSize])) {
                        $totalOrderQuantities[$normalizedSize] = 0;
                    }
                    $totalOrderQuantities[$normalizedSize] += (int)$quantity;
                }
            }

            // Fetch total cutting quantities
            $cuttingData = CuttingData::where('product_combination_id', $productCombinationId)->get();
            $totalCuttingQuantities = [];
            foreach ($cuttingData as $data) {
                foreach ($data->cut_quantities as $size => $quantity) {
                    $normalizedSize = strtolower($size);
                    if (!isset($totalCuttingQuantities[$normalizedSize])) {
                        $totalCuttingQuantities[$normalizedSize] = 0;
                    }
                    $totalCuttingQuantities[$normalizedSize] += (int)$quantity;
                }
            }

            return response()->json([
                'success' => true,
                'order_quantities' => $totalOrderQuantities,
                'cutting_quantities' => $totalCuttingQuantities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching quantities',
            ], 500);
        }
    }
}
