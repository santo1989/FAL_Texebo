<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\CuttingData;
use App\Models\LineInputData;
use App\Models\OutputFinishingData;
use App\Models\PrintReceiveData;
use App\Models\PrintSendData;
use App\Models\ShipmentData;
use App\Models\ProductCombination;
use App\Models\Size;
use App\Models\FinishPackingData;
use App\Models\OrderData;
use App\Models\Style;
use App\Models\sublimationPrintReceive;
use App\Models\sublimationPrintSend;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ShipmentDataController extends Controller
{

    public function index(Request $request)
    {
        $query = ShipmentData::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

        // Get filter parameters
        $styleIds = $request->input('style_id', []);
        $colorIds = $request->input('color_id', []);
        $poNumbers = $request->input('po_number', []);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');

        // Style filter
        if (!empty($styleIds)) {
            $query->whereHas('productCombination', function ($q) use ($styleIds) {
                $q->whereIn('style_id', $styleIds);
            });
        }

        // Color filter
        if (!empty($colorIds)) {
            $query->whereHas('productCombination', function ($q) use ($colorIds) {
                $q->whereIn('color_id', $colorIds);
            });
        }

        // PO Number filter
        if (!empty($poNumbers)) {
            $query->whereIn('po_number', $poNumbers);
        }

        // Date range filter
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        } elseif ($request->filled('date')) {
            // Single date filter (for backward compatibility)
            $query->whereDate('date', $request->input('date'));
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', '%' . $search . '%')
                    ->orWhere('shipment_number', 'like', '%' . $search . '%')
                    ->orWhereHas('productCombination.style', function ($q2) use ($search) {
                        $q2->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('productCombination.color', function ($q2) use ($search) {
                        $q2->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('productCombination.buyer', function ($q2) use ($search) {
                        $q2->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $shipmentData = $query->orderBy('date', 'desc')->paginate(10);
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Get filter options
        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = ShipmentData::distinct()->pluck('po_number')->filter()->values();

        return view('backend.library.shipment_data.index', compact(
            'shipmentData',
            'allSizes',
            'allStyles',
            'allColors',
            'distinctPoNumbers'
        ));
    }
    public function create()
    {
        // Get distinct PO numbers from FinishPackingData
        $distinctPoNumbers = FinishPackingData::distinct()->pluck('po_number')->filter()->values();
        $sizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        return view('backend.library.shipment_data.create', compact('distinctPoNumbers', 'sizes'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'date' => 'required|date',
            'rows' => 'required|array',
            'rows.*.product_combination_id' => 'required|exists:product_combinations,id',
            'rows.*.po_number' => 'required|string', // Add this validation
            'rows.*.shipment_quantities.*' => 'nullable|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->rows as $row) {
                $shipmentQuantities = [];
                $wasteQuantities = [];
                $totalShipmentQuantity = 0;
                $totalWasteQuantity = 0;

                // Process shipment quantities
                foreach ($row['shipment_quantities'] as $sizeId => $quantity) {
                    if ($quantity !== null && (int)$quantity > 0) {
                        $size = Size::find($sizeId);
                        if ($size) {
                            $shipmentQuantities[$size->id] = (int)$quantity;
                            $totalShipmentQuantity += (int)$quantity;
                        }
                    }
                }

                // Only create a record if there's at least one valid shipment or waste quantity
                if (!empty($shipmentQuantities) || !empty($wasteQuantities)) {
                    ShipmentData::create([
                        'date' => $request->date,
                        'product_combination_id' => $row['product_combination_id'],
                        'po_number' => $row['po_number'], // Use the specific PO number for this row
                        'shipment_quantities' => $shipmentQuantities,
                        'total_shipment_quantity' => $totalShipmentQuantity,
                        'shipment_waste_quantities' => $wasteQuantities,
                        'total_shipment_waste_quantity' => $totalWasteQuantity,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('shipment_data.index')
                ->withMessage('Shipment data added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(ShipmentData $shipmentDatum)
    {
        $shipmentDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Only valid sizes
        $validSizes = $allSizes->filter(function ($size) use ($shipmentDatum) {
            return isset($shipmentDatum->shipment_quantities[$size->id]) ||
                isset($shipmentDatum->shipment_waste_quantities[$size->id]);
        });

        $allSizes = $validSizes->values();

        return view('backend.library.shipment_data.show', compact('shipmentDatum', 'allSizes'));
    }

    public function edit(ShipmentData $shipmentDatum)
    {
        $shipmentDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Only valid sizes
        $validSizes = $allSizes->filter(function ($size) use ($shipmentDatum) {
            return isset($shipmentDatum->shipment_quantities[$size->id]) ||
                isset($shipmentDatum->shipment_waste_quantities[$size->id]);
        });

        $allSizes = $validSizes->values();

        // Get the PO numbers from the record
        $poNumbers = explode(',', $shipmentDatum->po_number);

        // Get max available quantities for this product combination and specific PO numbers
        $maxQuantities = $this->getMaxShipmentQuantities($shipmentDatum->productCombination, $poNumbers);

        // Get order quantities from order_data table
        $orderQuantities = [];
        foreach ($poNumbers as $poNumber) {
            $orderData = FinishPackingData::where('product_combination_id', $shipmentDatum->product_combination_id)
                ->where('po_number', 'like', '%' . $poNumber . '%')
                ->first();

            if ($orderData && $orderData->packing_quantities) {
                foreach ($orderData->packing_quantities as $sizeId => $qty) {
                    $orderQuantities[$sizeId] = ($orderQuantities[$sizeId] ?? 0) + $qty;
                }
            }
        }

        // Prepare size data with max available quantities and order quantities
        $sizeData = [];
        foreach ($allSizes as $size) {
            $shipmentQty = $shipmentDatum->shipment_quantities[$size->id] ?? 0;
            $wasteQty = $shipmentDatum->shipment_waste_quantities[$size->id] ?? 0;
            $maxAvailable = $maxQuantities[$size->id] ?? 0;
            $orderQty = $orderQuantities[$size->id] ?? 0;

            // Calculate the maximum allowed (available + current shipment)
            $maxAllowed = $maxAvailable + $shipmentQty;

            $sizeData[] = [
                'id' => $size->id,
                'name' => $size->name,
                'shipment_quantity' => $shipmentQty,
                'waste_quantity' => $wasteQty,
                'max_available' => $maxAvailable,
                'max_allowed' => $maxAllowed,
                'order_quantity' => $orderQty,
            ];
        }

        // Get distinct PO numbers from FinishPackingData
        $allPoNumbers = FinishPackingData::pluck('po_number')->toArray();
        $distinctPoNumbers = collect($allPoNumbers)
            ->flatMap(function ($poNumbers) {
                return explode(',', $poNumbers);
            })
            ->unique()
            ->filter()
            ->values();

        return view('backend.library.shipment_data.edit', compact('shipmentDatum', 'sizeData', 'distinctPoNumbers', 'poNumbers'));
    }

    public function update(Request $request, ShipmentData $shipmentDatum)
    {
        $request->validate([
            'date' => 'required|date',
            'po_number' => 'required|array',
            'po_number.*' => 'required|string',
            'shipment_quantities.*' => 'nullable|integer|min:0',
            'shipment_waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        try {
            $shipmentQuantities = [];
            $wasteQuantities = [];
            $totalShipmentQuantity = 0;
            $totalWasteQuantity = 0;

            // Process shipment quantities
            foreach ($request->shipment_quantities as $sizeId => $quantity) {
                if ($quantity !== null && (int)$quantity > 0) {
                    $shipmentQuantities[$sizeId] = (int)$quantity;
                    $totalShipmentQuantity += (int)$quantity;
                }
            }

            // Process waste quantities
            foreach ($request->shipment_waste_quantities as $sizeId => $quantity) {
                if ($quantity !== null && (int)$quantity > 0) {
                    $wasteQuantities[$sizeId] = (int)$quantity;
                    $totalWasteQuantity += (int)$quantity;
                }
            }

            $shipmentDatum->update([
                'date' => $request->date,
                'po_number' => implode(',', $request->po_number),
                'shipment_quantities' => $shipmentQuantities,
                'total_shipment_quantity' => $totalShipmentQuantity,
                'shipment_waste_quantities' => $wasteQuantities,
                'total_shipment_waste_quantity' => $totalWasteQuantity,
            ]);

            return redirect()->route('shipment_data.index')
                ->withMessage('Shipment data updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(ShipmentData $shipmentDatum)
    {
        $shipmentDatum->delete();
        return redirect()->route('shipment_data.index')->withMessage('Shipment data deleted successfully.');
    }

    private function getAvailablePoNumbers()
    {
        $poNumbers = [];

        // Get PO numbers from FinishPackingData
        $finishPackingPoNumbers = FinishPackingData::distinct()->pluck('po_number')->filter()->values();
        $poNumbers = array_merge($poNumbers, $finishPackingPoNumbers->toArray());

        return array_unique($poNumbers);
    }

    public function getMaxShipmentQuantities(ProductCombination $pc, $poNumbers = [])
    {
        $maxQuantities = [];
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Build query for packing quantities with PO number filter
        $packingQuery = FinishPackingData::where('product_combination_id', $pc->id);
        if (!empty($poNumbers)) {
            $packingQuery->where(function ($query) use ($poNumbers) {
                foreach ($poNumbers as $poNumber) {
                    $query->orWhere('po_number', 'like', '%' . $poNumber . '%');
                }
            });
        }

        // Get total packing quantities for the specific PO numbers
        $packingQuantities = $packingQuery->get()
            ->flatMap(function ($item) {
                return $item->packing_quantities;
            })
            ->groupBy(function ($value, $key) {
                return $key; // Use size ID as key
            })
            ->map(function ($group) {
                return $group->sum();
            })
            ->toArray();

        // Build query for shipped quantities with PO number filter
        $shippedQuery = ShipmentData::where('product_combination_id', $pc->id);
        if (!empty($poNumbers)) {
            $shippedQuery->where(function ($query) use ($poNumbers) {
                foreach ($poNumbers as $poNumber) {
                    $query->orWhere('po_number', 'like', '%' . $poNumber . '%');
                }
            });
        }

        // Get total shipped quantities for the specific PO numbers
        $shippedQuantities = $shippedQuery->get()
            ->flatMap(function ($item) {
                return $item->shipment_quantities;
            })
            ->groupBy(function ($value, $key) {
                return $key; // Use size ID as key
            })
            ->map(function ($group) {
                return $group->sum();
            })
            ->toArray();

        foreach ($allSizes as $size) {
            $packed = $packingQuantities[$size->id] ?? 0;
            $shipped = $shippedQuantities[$size->id] ?? 0;
            $maxQuantities[$size->id] = max(0, $packed - $shipped);
        }

        return $maxQuantities;
    }

    // public function find(Request $request)
    // {
    //     $poNumbers = $request->input('po_numbers', []);

    //     if (empty($poNumbers)) {
    //         return response()->json([]);
    //     }

    //     $result = [];
    //     $processedCombinations = [];

    //     foreach ($poNumbers as $poNumber) {
    //         // Get data for the selected PO number from FinishPackingData
    //         $productCombinations = ProductCombination::whereHas('finishPackingData', function ($query) use ($poNumber) {
    //             $query->where('po_number', 'like', '%' . $poNumber . '%');
    //         })
    //             ->with('style', 'color', 'size')
    //             ->get();

    //         foreach ($productCombinations as $pc) {
    //             // Skip if product combination doesn't have style or color
    //             if (!$pc->style || !$pc->color) {
    //                 continue;
    //             }

    //             // Create a unique key for this combination
    //             $combinationKey = $pc->id . '-' . $pc->style->name . '-' . $pc->color->name;

    //             // Skip if we've already processed this combination
    //             if (in_array($combinationKey, $processedCombinations)) {
    //                 continue;
    //             }

    //             // Mark this combination as processed
    //             $processedCombinations[] = $combinationKey;

    //             // Pass the PO numbers to getMaxShipmentQuantities
    //             $availableQuantities = $this->getMaxShipmentQuantities($pc, $poNumbers);

    //             $result[$poNumber][] = [
    //                 'combination_id' => $pc->id,
    //                 'style' => $pc->style->name,
    //                 'color' => $pc->color->name,
    //                 'available_quantities' => $availableQuantities,
    //                 'size_ids' => $pc->sizes->pluck('id')->toArray()
    //             ];
    //         }
    //     }

    //     return response()->json($result);
    // }

    // public function find(Request $request)
    // {
    //     $poNumbers = $request->input('po_numbers', []);

    //     if (empty($poNumbers)) {
    //         return response()->json([]);
    //     }

    //     $result = [];
    //     $processedCombinations = [];

    //     foreach ($poNumbers as $poNumber) {
    //         // Get data for the selected PO number from FinishPackingData
    //         $productCombinations = ProductCombination::whereHas('finishPackingData', function ($query) use ($poNumber) {
    //             $query->where('po_number', $poNumber); // Exact match instead of LIKE
    //         })
    //             ->with(['style', 'color', 'size', 'finishPackingData' => function ($query) use ($poNumber) {
    //                 $query->where('po_number', $poNumber);
    //             }])
    //             ->get();

    //         foreach ($productCombinations as $pc) {
    //             if (!$pc->style || !$pc->color) {
    //                 continue;
    //             }

    //             $combinationKey = $pc->id . '-' . $pc->style->name . '-' . $pc->color->name;

    //             if (in_array($combinationKey, $processedCombinations)) {
    //                 continue;
    //             }

    //             $processedCombinations[] = $combinationKey;

    //             // Calculate available quantities specifically for this PO
    //             $availableQuantities = $this->getAvailableShipmentQuantities($pc, $poNumber);

    //             $result[$poNumber][] = [
    //                 'combination_id' => $pc->id,
    //                 'style' => $pc->style->name,
    //                 'color' => $pc->color->name,
    //                 'available_quantities' => $availableQuantities,
    //                 'size_ids' => array_keys($availableQuantities)
    //             ];
    //         }
    //     }

    //     return response()->json($result);
    // }

    public function find(Request $request)
    {
        $poNumbers = $request->input('po_numbers', []);

        if (empty($poNumbers)) {
            return response()->json([]);
        }

        $result = [];
        $processedCombinations = [];

        foreach ($poNumbers as $poNumber) {
            // Get data for the selected PO number from FinishPackingData
            $productCombinations = ProductCombination::whereHas('finishPackingData', function ($query) use ($poNumber) {
                $query->where('po_number', $poNumber);
            })
                ->with(['style', 'color', 'size', 'finishPackingData' => function ($query) use ($poNumber) {
                    $query->where('po_number', $poNumber);
                }])
                ->get();

            foreach ($productCombinations as $pc) {
                if (!$pc->style || !$pc->color) {
                    continue;
                }

                // Create unique key that includes PO number to handle same combination across different POs
                $combinationKey = $pc->id . '-' . $pc->style->name . '-' . $pc->color->name . '-' . $poNumber;

                if (in_array($combinationKey, $processedCombinations)) {
                    continue;
                }

                $processedCombinations[] = $combinationKey;

                // Calculate available quantities specifically for this PO
                $availableQuantities = $this->getAvailableShipmentQuantities($pc, $poNumber);

                // Only include combinations that have available quantities
                $hasAvailableQuantities = array_sum($availableQuantities) > 0;

                if ($hasAvailableQuantities) {
                    $result[$poNumber][] = [
                        'combination_id' => $pc->id,
                        'style' => $pc->style->name,
                        'color' => $pc->color->name,
                        'available_quantities' => $availableQuantities,
                        'size_ids' => array_keys(array_filter($availableQuantities)) // Only include sizes with available quantities > 0
                    ];
                }
            }

            // If no combinations found for this PO, add an empty array to indicate PO exists
            if (!isset($result[$poNumber])) {
                $result[$poNumber] = [];
            }
        }

        return response()->json($result);
    }

    private function getAvailableShipmentQuantities(ProductCombination $pc, $poNumber)
    {
        // Get total packed quantities for this specific PO
        $packedQuantities = [];
        $pc->finishPackingData->each(function ($item) use (&$packedQuantities) {
            foreach ($item->packing_quantities as $sizeId => $quantity) {
                $packedQuantities[$sizeId] = ($packedQuantities[$sizeId] ?? 0) + $quantity;
            }
        });

        // Get total shipped quantities for this specific PO
        $shippedQuantities = [];
        ShipmentData::where('product_combination_id', $pc->id)
            ->where('po_number', $poNumber)
            ->get()
            ->each(function ($item) use (&$shippedQuantities) {
                foreach ($item->shipment_quantities as $sizeId => $quantity) {
                    $shippedQuantities[$sizeId] = ($shippedQuantities[$sizeId] ?? 0) + $quantity;
                }
            });

        // Calculate available quantities
        $availableQuantities = [];
        foreach ($packedQuantities as $sizeId => $quantity) {
            $shipped = $shippedQuantities[$sizeId] ?? 0;
            $availableQuantities[$sizeId] = max(0, $quantity - $shipped);
        }

        return $availableQuantities;
    }




    // Reports
    // public function totalShipmentReport(Request $request)
    // {
    //     $query = ShipmentData::with('productCombination.style', 'productCombination.color');

    //     if ($request->filled('start_date') && $request->filled('end_date')) {
    //         $query->whereBetween('date', [$request->start_date, $request->end_date]);
    //     }

    //     $shipmentData = $query->get();
    //     $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
    //     $reportData = [];

    //     foreach ($shipmentData as $data) {
    //         $style = $data->productCombination->style->name;
    //         $color = $data->productCombination->color->name;
    //         $poNumber = $data->po_number;
    //         $key = $poNumber . '-' . $style . '-' . $color;

    //         if (!isset($reportData[$key])) {
    //             $reportData[$key] = [
    //                 'po_number' => $poNumber,
    //                 'style' => $style,
    //                 'color' => $color,
    //                 'sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
    //                 'waste_sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
    //                 'total' => 0,
    //                 'total_waste' => 0
    //             ];
    //         }

    //         foreach ($data->shipment_quantities as $sizeId => $qty) {
    //             if (isset($reportData[$key]['sizes'][$sizeId])) {
    //                 $reportData[$key]['sizes'][$sizeId] += $qty;
    //             }
    //         }

    //         // Add waste quantities if they exist
    //         if ($data->shipment_waste_quantities) {
    //             foreach ($data->shipment_waste_quantities as $sizeId => $wasteQty) {
    //                 if (isset($reportData[$key]['waste_sizes'][$sizeId])) {
    //                     $reportData[$key]['waste_sizes'][$sizeId] += $wasteQty;
    //                 }
    //             }
    //             $reportData[$key]['total_waste'] += $data->total_shipment_waste_quantity;
    //         }

    //         $reportData[$key]['total'] += $data->total_shipment_quantity;
    //     }

    //     return view('backend.library.shipment_data.reports.total_shipment', [
    //         'reportData' => array_values($reportData),
    //         'allSizes' => $allSizes
    //     ]);
    // }

    // public function readyGoodsReport(Request $request)
    // {
    //     $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
    //     $reportData = [];

    //     // Get all product combinations that have finish packing data
    //     $productCombinations = ProductCombination::whereHas('finishPackingData')
    //         ->with('style', 'color')
    //         ->get();

    //     foreach ($productCombinations as $pc) {
    //         $style = $pc->style->name;
    //         $color = $pc->color->name;

    //         // Get all PO numbers for this product combination
    //         $poNumbers = FinishPackingData::where('product_combination_id', $pc->id)
    //             ->pluck('po_number')
    //             ->unique()
    //             ->toArray();

    //         foreach ($poNumbers as $poNumber) {
    //             $key = $poNumber . '-' . $style . '-' . $color;

    //             if (!isset($reportData[$key])) {
    //                 $reportData[$key] = [
    //                     'po_number' => $poNumber,
    //                     'style' => $style,
    //                     'color' => $color,
    //                     'sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
    //                     'total' => 0
    //                 ];
    //             }

    //             // Get total packed quantities for this PO number
    //             $packedQuantities = FinishPackingData::where('product_combination_id', $pc->id)
    //                 ->where('po_number', 'like', '%' . $poNumber . '%')
    //                 ->get()
    //                 ->flatMap(fn($item) => $item->packing_quantities)
    //                 ->groupBy(fn($value, $key) => $key)
    //                 ->map(fn($group) => $group->sum())
    //                 ->toArray();

    //             // Get total shipped quantities for this PO number
    //             $shippedQuantities = ShipmentData::where('product_combination_id', $pc->id)
    //                 ->where('po_number', 'like', '%' . $poNumber . '%')
    //                 ->get()
    //                 ->flatMap(fn($item) => $item->shipment_quantities)
    //                 ->groupBy(fn($value, $key) => $key)
    //                 ->map(fn($group) => $group->sum())
    //                 ->toArray();

    //             foreach ($allSizes as $size) {
    //                 $packed = $packedQuantities[$size->id] ?? 0;
    //                 $shipped = $shippedQuantities[$size->id] ?? 0;
    //                 $ready = max(0, $packed - $shipped);

    //                 $reportData[$key]['sizes'][$size->id] = $ready;
    //                 $reportData[$key]['total'] += $ready;
    //             }
    //         }
    //     }

    //     return view('backend.library.shipment_data.reports.ready_goods', [
    //         'reportData' => array_values($reportData),
    //         'allSizes' => $allSizes
    //     ]);
    // }

    // public function finalbalanceReport(Request $request)
    // {
    //     // Get filter parameters
    //     $styleId = $request->input('style_id');
    //     $colorId = $request->input('color_id');
    //     $poNumber = $request->input('po_number');
    //     $start_date = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
    //     $end_date = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

    //     // Get styles and colors for filters
    //     $styles = Style::get();
    //     $colors = Color::get();

    //     $reportData = [];

    //     // Base query for product combinations
    //     $productCombinationQuery = ProductCombination::with('style', 'color', 'size');

    //     if ($styleId) {
    //         $productCombinationQuery->where('style_id', $styleId);
    //     }
    //     if ($colorId) {
    //         $productCombinationQuery->where('color_id', $colorId);
    //     }

    //     $productCombinations = $productCombinationQuery->paginate(10);

    //     foreach ($productCombinations as $pc) {
    //         $style = $pc->style->name;
    //         $color = $pc->color->name;

    //         // Get all PO numbers for this product combination
    //         $poNumbers = OrderData::where('product_combination_id', $pc->id)
    //             ->pluck('po_number')
    //             ->unique()
    //             ->toArray();

    //         // If no PO numbers found, use an empty array
    //         if (empty($poNumbers)) {
    //             $poNumbers = [''];
    //         }

    //         foreach ($poNumbers as $poNum) {
    //             // Skip if PO number filter is set and doesn't match
    //             if ($poNumber && !str_contains($poNum, $poNumber)) {
    //                 continue;
    //             }

    //             // Date range and PO filter closure
    //             $dateFilter = function ($query) use ($start_date, $end_date, $poNum) {
    //                 if ($start_date && $end_date) {
    //                     $query->whereBetween('date', [$start_date, $end_date]);
    //                 } elseif ($start_date) {
    //                     $query->where('date', '>=', $start_date);
    //                 } elseif ($end_date) {
    //                     $query->where('date', '<=', $end_date);
    //                 }

    //                 if ($poNum) {
    //                     $query->where('po_number', 'like', '%' . $poNum . '%');
    //                 }
    //             };

    //             // Fetch quantities with date filtering and PO number filtering
    //             $orderQuantities = OrderData::where('product_combination_id', $pc->id)
    //                 ->when($start_date || $end_date || $poNum, $dateFilter)
    //                 ->get()
    //                 ->flatMap(function ($item) {
    //                     return collect($item->order_quantities)->mapWithKeys(function ($value, $key) {
    //                         return [$key => $value];
    //                     });
    //                 })
    //                 ->groupBy(function ($value, $key) {
    //                     return $key;
    //                 })
    //                 ->map(function ($group) {
    //                     return $group->sum();
    //                 })
    //                 ->toArray();

    //             $cutQuantities = CuttingData::where('product_combination_id', $pc->id)
    //                 ->when($start_date || $end_date || $poNum, $dateFilter)
    //                 ->get()
    //                 ->flatMap(function ($item) {
    //                     return collect($item->cut_quantities)->mapWithKeys(function ($value, $key) {
    //                         return [$key => $value];
    //                     });
    //                 })
    //                 ->groupBy(function ($value, $key) {
    //                     return $key;
    //                 })
    //                 ->map(function ($group) {
    //                     return $group->sum();
    //                 })
    //                 ->toArray();

    //             $printSendQuantities = PrintSendData::where('product_combination_id', $pc->id)
    //                 ->when($start_date || $end_date || $poNum, $dateFilter)
    //                 ->get()
    //                 ->flatMap(function ($item) {
    //                     return collect($item->send_quantities)->mapWithKeys(function ($value, $key) {
    //                         return [$key => $value];
    //                     });
    //                 })
    //                 ->groupBy(function ($value, $key) {
    //                     return $key;
    //                 })
    //                 ->map(function ($group) {
    //                     return $group->sum();
    //                 })
    //                 ->toArray();

    //             $printReceiveQuantities = PrintReceiveData::where('product_combination_id', $pc->id)
    //                 ->when($start_date || $end_date || $poNum, $dateFilter)
    //                 ->get()
    //                 ->flatMap(function ($item) {
    //                     return collect($item->receive_quantities)->mapWithKeys(function ($value, $key) {
    //                         return [$key => $value];
    //                     });
    //                 })
    //                 ->groupBy(function ($value, $key) {
    //                     return $key;
    //                 })
    //                 ->map(function ($group) {
    //                     return $group->sum();
    //                 })
    //                 ->toArray();

    //             $lineInputQuantities = LineInputData::where('product_combination_id', $pc->id)
    //                 ->when($start_date || $end_date || $poNum, $dateFilter)
    //                 ->get()
    //                 ->flatMap(function ($item) {
    //                     return collect($item->input_quantities)->mapWithKeys(function ($value, $key) {
    //                         return [$key => $value];
    //                     });
    //                 })
    //                 ->groupBy(function ($value, $key) {
    //                     return $key;
    //                 })
    //                 ->map(function ($group) {
    //                     return $group->sum();
    //                 })
    //                 ->toArray();

    //             $finishPackingQuantities = FinishPackingData::where('product_combination_id', $pc->id)
    //                 ->where('po_number', 'like', '%' . $poNum . '%')
    //                 ->when($start_date || $end_date, function ($query) use ($start_date, $end_date) {
    //                     if ($start_date && $end_date) {
    //                         $query->whereBetween('date', [$start_date, $end_date]);
    //                     } elseif ($start_date) {
    //                         $query->where('date', '>=', $start_date);
    //                     } elseif ($end_date) {
    //                         $query->where('date', '<=', $end_date);
    //                     }
    //                 })
    //                 ->get()
    //                 ->flatMap(function ($item) {
    //                     return collect($item->packing_quantities)->mapWithKeys(function ($value, $key) {
    //                         return [$key => $value];
    //                     });
    //                 })
    //                 ->groupBy(function ($value, $key) {
    //                     return $key;
    //                 })
    //                 ->map(function ($group) {
    //                     return $group->sum();
    //                 })
    //                 ->toArray();

    //             $shipmentQuantities = ShipmentData::where('product_combination_id', $pc->id)
    //                 ->where('po_number', 'like', '%' . $poNum . '%')
    //                 ->when($start_date || $end_date, function ($query) use ($start_date, $end_date) {
    //                     if ($start_date && $end_date) {
    //                         $query->whereBetween('date', [$start_date, $end_date]);
    //                     } elseif ($start_date) {
    //                         $query->where('date', '>=', $start_date);
    //                     } elseif ($end_date) {
    //                         $query->where('date', '<=', $end_date);
    //                     }
    //                 })
    //                 ->get()
    //                 ->flatMap(function ($item) {
    //                     return collect($item->shipment_quantities)->mapWithKeys(function ($value, $key) {
    //                         return [$key => $value];
    //                     });
    //                 })
    //                 ->groupBy(function ($value, $key) {
    //                     return $key;
    //                 })
    //                 ->map(function ($group) {
    //                     return $group->sum();
    //                 })
    //                 ->toArray();

    //             $shipmentWasteQuantities = ShipmentData::where('product_combination_id', $pc->id)
    //                 ->where('po_number', 'like', '%' . $poNum . '%')
    //                 ->when($start_date || $end_date, function ($query) use ($start_date, $end_date) {
    //                     if ($start_date && $end_date) {
    //                         $query->whereBetween('date', [$start_date, $end_date]);
    //                     } elseif ($start_date) {
    //                         $query->where('date', '>=', $start_date);
    //                     } elseif ($end_date) {
    //                         $query->where('date', '<=', $end_date);
    //                     }
    //                 })
    //                 ->get()
    //                 ->flatMap(function ($item) {
    //                     return collect($item->shipment_waste_quantities ?? [])->mapWithKeys(function ($value, $key) {
    //                         return [$key => $value];
    //                     });
    //                 })
    //                 ->groupBy(function ($value, $key) {
    //                     return $key;
    //                 })
    //                 ->map(function ($group) {
    //                     return $group->sum();
    //                 })
    //                 ->toArray();

    //             // Create rows for each size
    //             foreach ($pc->sizes as $size) {
    //                 $sizeName = $size->name;

    //                 $order = $orderQuantities[$sizeName] ?? 0;
    //                 $cut = $cutQuantities[$sizeName] ?? 0;
    //                 $printSent = $printSendQuantities[$sizeName] ?? 0;
    //                 $printReceived = $printReceiveQuantities[$sizeName] ?? 0;
    //                 $lineInput = $lineInputQuantities[$sizeName] ?? 0;
    //                 $packed = $finishPackingQuantities[$sizeName] ?? 0;
    //                 $shipped = $shipmentQuantities[$sizeName] ?? 0;
    //                 $shipmentWaste = $shipmentWasteQuantities[$sizeName] ?? 0;

    //                 // Calculate balances
    //                 $printSendBalance = $cut - $printSent;
    //                 $printReceiveBalance = $printSent - $printReceived;
    //                 $sewingInputBalance = $printReceived - $lineInput;
    //                 $packingBalance = $lineInput - $packed;
    //                 $readyGoods = $packed - $shipped;

    //                 $reportData[] = [
    //                     'po_number' => $poNum,
    //                     'style' => $style,
    //                     'color' => $color,
    //                     'size' => $size->name,
    //                     'order' => $order,
    //                     'cutting' => $cut,
    //                     'print_send' => $printSent,
    //                     'print_send_balance' => $printSendBalance,
    //                     'print_receive' => $printReceived,
    //                     'print_receive_balance' => $printReceiveBalance,
    //                     'sewing_input' => $lineInput,
    //                     'sewing_input_balance' => $sewingInputBalance,
    //                     'packing' => $packed,
    //                     'packing_balance' => $packingBalance,
    //                     'shipment' => $shipped,
    //                     'shipment_waste' => $shipmentWaste,
    //                     'ready_goods' => $readyGoods,
    //                 ];
    //             }
    //         }
    //     }

    //     // Group data for rowspan display
    //     $groupedData = [];
    //     foreach ($reportData as $row) {
    //         $key = $row['po_number'] . '_' . $row['style'] . '_' . $row['color'];
    //         if (!isset($groupedData[$key])) {
    //             $groupedData[$key] = [
    //                 'po_number' => $row['po_number'],
    //                 'style' => $row['style'],
    //                 'color' => $row['color'],
    //                 'rows' => [],
    //             ];
    //         }
    //         $groupedData[$key]['rows'][] = $row;
    //     }

    //     return view('backend.library.shipment_data.reports.balance', [
    //         'groupedData' => $groupedData,
    //         'styles' => $styles,
    //         'colors' => $colors,
    //         'productCombinations' => $productCombinations,
    //         'start_date' => $request->input('start_date'),
    //         'end_date' => $request->input('end_date'),
    //         'styleId' => $styleId,
    //         'colorId' => $colorId,
    //         'poNumber' => $poNumber,
    //     ]);
    // }

    public function totalShipmentReport(Request $request)
    {
        $query = ShipmentData::with('productCombination.style', 'productCombination.color');

        // Get filter parameters
        $styleIds = $request->input('style_id', []);
        $colorIds = $request->input('color_id', []);
        $poNumbers = $request->input('po_number', []);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');

        // Style filter
        if (!empty($styleIds)) {
            $query->whereHas('productCombination', function ($q) use ($styleIds) {
                $q->whereIn('style_id', $styleIds);
            });
        }

        // Color filter
        if (!empty($colorIds)) {
            $query->whereHas('productCombination', function ($q) use ($colorIds) {
                $q->whereIn('color_id', $colorIds);
            });
        }

        // PO Number filter
        if (!empty($poNumbers)) {
            $query->whereIn('po_number', $poNumbers);
        }

        // Date filter
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', '%' . $search . '%')
                    ->orWhere('shipment_number', 'like', '%' . $search . '%')
                    ->orWhereHas('productCombination.style', function ($q2) use ($search) {
                        $q2->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('productCombination.color', function ($q2) use ($search) {
                        $q2->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $shipmentData = $query->get();
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $reportData = [];

        foreach ($shipmentData as $data) {
            $style = $data->productCombination->style->name;
            $color = $data->productCombination->color->name;
            $poNumber = $data->po_number;
            $key = $poNumber . '-' . $style . '-' . $color;

            if (!isset($reportData[$key])) {
                $reportData[$key] = [
                    'po_number' => $poNumber,
                    'style' => $style,
                    'color' => $color,
                    'sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
                    'waste_sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
                    'total' => 0,
                    'total_waste' => 0
                ];
            }

            foreach ($data->shipment_quantities as $sizeId => $qty) {
                if (isset($reportData[$key]['sizes'][$sizeId])) {
                    $reportData[$key]['sizes'][$sizeId] += $qty;
                }
            }

            // Add waste quantities if they exist
            if ($data->shipment_waste_quantities) {
                foreach ($data->shipment_waste_quantities as $sizeId => $wasteQty) {
                    if (isset($reportData[$key]['waste_sizes'][$sizeId])) {
                        $reportData[$key]['waste_sizes'][$sizeId] += $wasteQty;
                    }
                }
                $reportData[$key]['total_waste'] += $data->total_shipment_waste_quantity;
            }

            $reportData[$key]['total'] += $data->total_shipment_quantity;
        }

        // Get filter options
        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = ShipmentData::distinct()->pluck('po_number')->filter()->values();

        return view('backend.library.shipment_data.reports.total_shipment', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes,
            'allStyles' => $allStyles,
            'allColors' => $allColors,
            'distinctPoNumbers' => $distinctPoNumbers
        ]);
    }

    public function readyGoodsReport(Request $request)
    {
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $reportData = [];

        // Get filter parameters
        $styleIds = $request->input('style_id', []);
        $colorIds = $request->input('color_id', []);
        $poNumbers = $request->input('po_number', []);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');

        // Base query for product combinations
        $productCombinationsQuery = ProductCombination::whereHas('finishPackingData')
            ->with('style', 'color');

        // Apply style and color filters
        if (!empty($styleIds)) {
            $productCombinationsQuery->whereIn('style_id', $styleIds);
        }

        if (!empty($colorIds)) {
            $productCombinationsQuery->whereIn('color_id', $colorIds);
        }

        // Apply search filter
        if ($search) {
            $productCombinationsQuery->where(function ($q) use ($search) {
                $q->whereHas('style', function ($q2) use ($search) {
                    $q2->where('name', 'like', '%' . $search . '%');
                })->orWhereHas('color', function ($q2) use ($search) {
                    $q2->where('name', 'like', '%' . $search . '%');
                });
            });
        }

        $productCombinations = $productCombinationsQuery->get();

        foreach ($productCombinations as $pc) {
            $style = $pc->style->name;
            $color = $pc->color->name;

            // Get all PO numbers for this product combination with filters
            $poNumbersQuery = FinishPackingData::where('product_combination_id', $pc->id);

            // Apply PO number filter
            if (!empty($poNumbers)) {
                $poNumbersQuery->whereIn('po_number', $poNumbers);
            }

            // Apply date filter
            if ($startDate && $endDate) {
                $poNumbersQuery->whereBetween('date', [$startDate, $endDate]);
            }

            $poNumbers = $poNumbersQuery->pluck('po_number')
                ->unique()
                ->toArray();

            foreach ($poNumbers as $poNumber) {
                $key = $poNumber . '-' . $style . '-' . $color;

                if (!isset($reportData[$key])) {
                    $reportData[$key] = [
                        'po_number' => $poNumber,
                        'style' => $style,
                        'color' => $color,
                        'sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
                        'total' => 0
                    ];
                }

                // Get total packed quantities for this PO number with filters
                $packedQuery = FinishPackingData::where('product_combination_id', $pc->id)
                    ->where('po_number', $poNumber);

                // Apply date filter
                if ($startDate && $endDate) {
                    $packedQuery->whereBetween('date', [$startDate, $endDate]);
                }

                $packedQuantities = $packedQuery->get()
                    ->flatMap(fn($item) => $item->packing_quantities)
                    ->groupBy(fn($value, $key) => $key)
                    ->map(fn($group) => $group->sum())
                    ->toArray();

                // Get total shipped quantities for this PO number with filters
                $shippedQuery = ShipmentData::where('product_combination_id', $pc->id)
                    ->where('po_number', $poNumber);

                // Apply date filter
                if ($startDate && $endDate) {
                    $shippedQuery->whereBetween('date', [$startDate, $endDate]);
                }

                $shippedQuantities = $shippedQuery->get()
                    ->flatMap(fn($item) => $item->shipment_quantities)
                    ->groupBy(fn($value, $key) => $key)
                    ->map(fn($group) => $group->sum())
                    ->toArray();

                foreach ($allSizes as $size) {
                    $packed = $packedQuantities[$size->id] ?? 0;
                    $shipped = $shippedQuantities[$size->id] ?? 0;
                    $ready = max(0, $packed - $shipped);

                    $reportData[$key]['sizes'][$size->id] = $ready;
                    $reportData[$key]['total'] += $ready;
                }

                // Remove if no ready goods match the filters
                if ($reportData[$key]['total'] == 0) {
                    unset($reportData[$key]);
                }
            }
        }

        // Get filter options
        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = array_unique(
            array_merge(
                FinishPackingData::distinct()->pluck('po_number')->filter()->values()->toArray(),
                ShipmentData::distinct()->pluck('po_number')->filter()->values()->toArray()
            )
        );
        sort($distinctPoNumbers);

        return view('backend.library.shipment_data.reports.ready_goods', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes,
            'allStyles' => $allStyles,
            'allColors' => $allColors,
            'distinctPoNumbers' => $distinctPoNumbers
        ]);
    }

    public function finalbalanceReport(Request $request)
    {
        // Get filter parameters
        $styleId = $request->input('style_id');
        $colorId = $request->input('color_id');
        $poNumber = $request->input('po_number');
        $start_date = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $end_date = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        // Get styles and colors for filters
        $styles = Style::get();
        $colors = Color::get();

        $reportData = [];

        // Base query for product combinations with eager loading
        $productCombinationQuery = ProductCombination::with('style', 'color', 'size');

        if ($styleId) {
            $productCombinationQuery->where('style_id', $styleId);
        }
        if ($colorId) {
            $productCombinationQuery->where('color_id', $colorId);
        }

        $productCombinations = $productCombinationQuery->paginate(10);

        // Preload all sizes for mapping
        $allSizes = Size::pluck('name', 'id')->toArray();

        foreach ($productCombinations as $pc) {
            $style = $pc->style->name;
            $color = $pc->color->name;

            // Get all PO numbers for this product combination
            $poNumbers = OrderData::where('product_combination_id', $pc->id)
                ->pluck('po_number')
                ->unique()
                ->toArray();

            // If no PO numbers found, use an empty array
            if (empty($poNumbers)) {
                $poNumbers = [''];
            }

            foreach ($poNumbers as $poNum) {
                // Skip if PO number filter is set and doesn't match
                if ($poNumber && !str_contains($poNum, $poNumber)) {
                    continue;
                }

                // Date range and PO filter closure
                $dateFilter = function ($query) use ($start_date, $end_date, $poNum) {
                    if ($start_date && $end_date) {
                        $query->whereBetween('date', [$start_date, $end_date]);
                    } elseif ($start_date) {
                        $query->where('date', '>=', $start_date);
                    } elseif ($end_date) {
                        $query->where('date', '<=', $end_date);
                    }

                    if ($poNum) {
                        $query->where('po_number', 'like', '%' . $poNum . '%');
                    }
                };

                // Fetch quantities using size IDs as keys
                $orderQuantities = $this->getQuantities(OrderData::class, 'order_quantities', $pc->id, $dateFilter);
                $cutQuantities = $this->getQuantities(CuttingData::class, 'cut_quantities', $pc->id, $dateFilter);

                // Handle different print types
                $printSendQuantities = [];
                $printReceiveQuantities = [];

                if ($pc->sublimation_print) {
                    $printSendQuantities = $this->getQuantities(SublimationPrintSend::class, 'sublimation_print_send_quantities', $pc->id, $dateFilter);
                    $printReceiveQuantities = $this->getQuantities(SublimationPrintReceive::class, 'sublimation_print_receive_quantities', $pc->id, $dateFilter);
                }

                if ($pc->print_embroidery) {
                    $embroiderySend = $this->getQuantities(PrintSendData::class, 'send_quantities', $pc->id, $dateFilter);
                    $embroideryReceive = $this->getQuantities(PrintReceiveData::class, 'receive_quantities', $pc->id, $dateFilter);

                    // Merge quantities if both print types exist
                    foreach ($embroiderySend as $sizeId => $qty) {
                        $printSendQuantities[$sizeId] = ($printSendQuantities[$sizeId] ?? 0) + $qty;
                    }

                    foreach ($embroideryReceive as $sizeId => $qty) {
                        $printReceiveQuantities[$sizeId] = ($printReceiveQuantities[$sizeId] ?? 0) + $qty;
                    }
                }

                // Fetch other quantities
                $lineInputQuantities = $this->getQuantities(LineInputData::class, 'input_quantities', $pc->id, $dateFilter);
                $finishOutputQuantities = $this->getQuantities(OutputFinishingData::class, 'output_quantities', $pc->id, $dateFilter);
                $finishPackingQuantities = $this->getPackingQuantities($pc->id, $poNum, $start_date, $end_date);

                // Fetch shipment quantities
                $shipmentData = $this->getShipmentQuantities($pc->id, $poNum, $start_date, $end_date);
                $shipmentQuantities = $shipmentData['shipment'];
                $shipmentWasteQuantities = $shipmentData['waste'];

                // Create rows for each size
                foreach ($pc->sizes as $size) {
                    $sizeId = (string)$size->id; // Convert to string to match JSON keys
                    $sizeName = $size->name;

                    // Get quantities by size ID
                    $order = $orderQuantities[$sizeId] ?? 0;
                    $cut = $cutQuantities[$sizeId] ?? 0;
                    $printSent = $printSendQuantities[$sizeId] ?? 0;
                    $printReceived = $printReceiveQuantities[$sizeId] ?? 0;
                    $lineInput = $lineInputQuantities[$sizeId] ?? 0;
                    $finishOutput = $finishOutputQuantities[$sizeId] ?? 0;
                    $packed = $finishPackingQuantities[$sizeId] ?? 0;
                    $shipped = $shipmentQuantities[$sizeId] ?? 0;
                    $shipmentWaste = $shipmentWasteQuantities[$sizeId] ?? 0;

                    // Calculate balances
                    $printSendBalance = $cut - $printSent;
                    $printReceiveBalance = $printSent - $printReceived;
                    $sewingInputBalance = $printReceived - $lineInput;
                    $finishingBalance = $lineInput - $finishOutput;
                    $packingBalance = $finishOutput - $packed;
                    $readyGoods = $packed - $shipped;

                    $reportData[] = [
                        'po_number' => $poNum,
                        'style' => $style,
                        'color' => $color,
                        'size' => $sizeName,
                        'order' => $order,
                        'cutting' => $cut,
                        'print_send' => $printSent,
                        'print_send_balance' => $printSendBalance,
                        'print_receive' => $printReceived,
                        'print_receive_balance' => $printReceiveBalance,
                        'sewing_input' => $lineInput,
                        'sewing_input_balance' => $sewingInputBalance,
                        'finish_output' => $finishOutput,
                        'finish_balance' => $finishingBalance,
                        'packing' => $packed,
                        'packing_balance' => $packingBalance,
                        'shipment' => $shipped,
                        'shipment_waste' => $shipmentWaste,
                        'ready_goods' => $readyGoods,
                    ];
                }
            }
        }

        // Group data for rowspan display
        $groupedData = [];
        foreach ($reportData as $row) {
            $key = $row['po_number'] . '_' . $row['style'] . '_' . $row['color'];
            if (!isset($groupedData[$key])) {
                $groupedData[$key] = [
                    'po_number' => $row['po_number'],
                    'style' => $row['style'],
                    'color' => $row['color'],
                    'rows' => [],
                ];
            }
            $groupedData[$key]['rows'][] = $row;
        }

        return view('backend.library.shipment_data.reports.balance', [
            'groupedData' => $groupedData,
            'styles' => $styles,
            'colors' => $colors,
            'productCombinations' => $productCombinations,
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'styleId' => $styleId,
            'colorId' => $colorId,
            'poNumber' => $poNumber,
        ]);
    }

    // Helper method to get quantities from various tables
    private function getQuantities($model, $quantityField, $productCombinationId, $dateFilter)
    {
        return $model::where('product_combination_id', $productCombinationId)
            ->when($dateFilter, function ($query) use ($dateFilter) {
                return $dateFilter($query);
            })
            ->get()
            ->flatMap(function ($item) use ($quantityField) {
                // Return quantities with size IDs as keys
                return collect($item->$quantityField)->mapWithKeys(function ($value, $key) {
                    return [(string)$key => $value]; // Ensure key is string to match JSON format
                });
            })
            ->groupBy(function ($value, $key) {
                return $key;
            })
            ->map(function ($group) {
                return $group->sum();
            })
            ->toArray();
    }

    // Helper method for packing quantities
    private function getPackingQuantities($productCombinationId, $poNumber, $start_date, $end_date)
    {
        $query = FinishPackingData::where('product_combination_id', $productCombinationId);

        if ($poNumber) {
            $query->where('po_number', 'like', '%' . $poNumber . '%');
        }

        if ($start_date && $end_date) {
            $query->whereBetween('date', [$start_date, $end_date]);
        } elseif ($start_date) {
            $query->where('date', '>=', $start_date);
        } elseif ($end_date) {
            $query->where('date', '<=', $end_date);
        }

        return $query->get()
            ->flatMap(function ($item) {
                return collect($item->packing_quantities)->mapWithKeys(function ($value, $key) {
                    return [(string)$key => $value]; // Ensure key is string
                });
            })
            ->groupBy(function ($value, $key) {
                return $key;
            })
            ->map(function ($group) {
                return $group->sum();
            })
            ->toArray();
    }

    // Helper method for shipment quantities
    private function getShipmentQuantities($productCombinationId, $poNumber, $start_date, $end_date)
    {
        $query = ShipmentData::where('product_combination_id', $productCombinationId);

        if ($poNumber) {
            $query->where('po_number', 'like', '%' . $poNumber . '%');
        }

        if ($start_date && $end_date) {
            $query->whereBetween('date', [$start_date, $end_date]);
        } elseif ($start_date) {
            $query->where('date', '>=', $start_date);
        } elseif ($end_date) {
            $query->where('date', '<=', $end_date);
        }

        $shipments = $query->get();

        $shipmentQuantities = $shipments
            ->flatMap(function ($item) {
                return collect($item->shipment_quantities)->mapWithKeys(function ($value, $key) {
                    return [(string)$key => $value]; // Ensure key is string
                });
            })
            ->groupBy(function ($value, $key) {
                return $key;
            })
            ->map(function ($group) {
                return $group->sum();
            })
            ->toArray();

        $shipmentWasteQuantities = $shipments
            ->flatMap(function ($item) {
                return collect($item->shipment_waste_quantities ?? [])->mapWithKeys(function ($value, $key) {
                    return [(string)$key => $value]; // Ensure key is string
                });
            })
            ->groupBy(function ($value, $key) {
                return $key;
            })
            ->map(function ($group) {
                return $group->sum();
            })
            ->toArray();

        return [
            'shipment' => $shipmentQuantities,
            'waste' => $shipmentWasteQuantities
        ];
    }

    //// Controller Method for Waste Quantity Report
    public function wasteReport(Request $request)
    {
        // Get filter parameters
        $styleId = $request->input('style_id');
        $colorId = $request->input('color_id');
        $poNumber = $request->input('po_number');
        $start_date = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $end_date = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        // Get styles and colors for filters
        $styles = Style::get();
        $colors = Color::get();

        $reportData = [];

        // Base query for product combinations with eager loading
        $productCombinationQuery = ProductCombination::with('style', 'color', 'size');

        if ($styleId) {
            $productCombinationQuery->where('style_id', $styleId);
        }
        if ($colorId) {
            $productCombinationQuery->where('color_id', $colorId);
        }

        $productCombinations = $productCombinationQuery->paginate(10);

        foreach ($productCombinations as $pc) {
            $style = $pc->style->name;
            $color = $pc->color->name;

            // Get all PO numbers for this product combination
            $poNumbers = OrderData::where('product_combination_id', $pc->id)
                ->pluck('po_number')
                ->unique()
                ->toArray();

            // If no PO numbers found, use an empty array
            if (empty($poNumbers)) {
                $poNumbers = [''];
            }

            foreach ($poNumbers as $poNum) {
                // Skip if PO number filter is set and doesn't match
                if ($poNumber && !str_contains($poNum, $poNumber)) {
                    continue;
                }

                // Date range and PO filter closure
                $dateFilter = function ($query) use ($start_date, $end_date, $poNum) {
                    if ($start_date && $end_date) {
                        $query->whereBetween('date', [$start_date, $end_date]);
                    } elseif ($start_date) {
                        $query->where('date', '>=', $start_date);
                    } elseif ($end_date) {
                        $query->where('date', '<=', $end_date);
                    }

                    if ($poNum) {
                        $query->where('po_number', 'like', '%' . $poNum . '%');
                    }
                };

                // Fetch waste quantities from all stages
                $cuttingWaste = $this->getWasteQuantities(CuttingData::class, 'cut_waste_quantities', $pc->id, $dateFilter);

                // Handle different print types
                $printSendWaste = [];
                $printReceiveWaste = [];

                if ($pc->sublimation_print) {
                    $printSendWaste = $this->getWasteQuantities(SublimationPrintSend::class, 'sublimation_print_send_waste_quantities', $pc->id, $dateFilter);
                    $printReceiveWaste = $this->getWasteQuantities(SublimationPrintReceive::class, 'sublimation_print_receive_waste_quantities', $pc->id, $dateFilter);
                }

                if ($pc->print_embroidery) {
                    $embroiderySendWaste = $this->getWasteQuantities(PrintSendData::class, 'send_waste_quantities', $pc->id, $dateFilter);
                    $embroideryReceiveWaste = $this->getWasteQuantities(PrintReceiveData::class, 'receive_waste_quantities', $pc->id, $dateFilter);

                    // Merge waste quantities if both print types exist
                    foreach ($embroiderySendWaste as $sizeId => $qty) {
                        $printSendWaste[$sizeId] = ($printSendWaste[$sizeId] ?? 0) + $qty;
                    }

                    foreach ($embroideryReceiveWaste as $sizeId => $qty) {
                        $printReceiveWaste[$sizeId] = ($printReceiveWaste[$sizeId] ?? 0) + $qty;
                    }
                }

                // Fetch other waste quantities
                $lineInputWaste = $this->getWasteQuantities(LineInputData::class, 'input_waste_quantities', $pc->id, $dateFilter);
                $finishOutputWaste = $this->getWasteQuantities(OutputFinishingData::class, 'output_waste_quantities', $pc->id, $dateFilter);
                $finishPackingWaste = $this->getWasteQuantities(FinishPackingData::class, 'packing_waste_quantities', $pc->id, $dateFilter);

                // Fetch shipment waste quantities
                $shipmentWasteData = $this->getShipmentWasteQuantities($pc->id, $poNum, $start_date, $end_date);

                // Create rows for each size
                foreach ($pc->sizes as $size) {
                    $sizeId = (string)$size->id; // Convert to string to match JSON keys
                    $sizeName = $size->name;

                    // Get waste quantities by size ID
                    $cutting = $cuttingWaste[$sizeId] ?? 0;
                    $printSent = $printSendWaste[$sizeId] ?? 0;
                    $printReceived = $printReceiveWaste[$sizeId] ?? 0;
                    $lineInput = $lineInputWaste[$sizeId] ?? 0;
                    $finishOutput = $finishOutputWaste[$sizeId] ?? 0;
                    $packing = $finishPackingWaste[$sizeId] ?? 0;
                    $shipment = $shipmentWasteData[$sizeId] ?? 0;

                    // Calculate total waste
                    $totalWaste = $cutting + $printSent + $printReceived + $lineInput + $finishOutput + $packing + $shipment;

                    $reportData[] = [
                        'po_number' => $poNum,
                        'style' => $style,
                        'color' => $color,
                        'size' => $sizeName,
                        'cutting_waste' => $cutting,
                        'print_send_waste' => $printSent,
                        'print_receive_waste' => $printReceived,
                        'sewing_input_waste' => $lineInput,
                        'finish_output_waste' => $finishOutput,
                        'packing_waste' => $packing,
                        'shipment_waste' => $shipment,
                        'total_waste' => $totalWaste,
                    ];
                }
            }
        }

        // Group data for rowspan display
        $groupedData = [];
        foreach ($reportData as $row) {
            $key = $row['po_number'] . '_' . $row['style'] . '_' . $row['color'];
            if (!isset($groupedData[$key])) {
                $groupedData[$key] = [
                    'po_number' => $row['po_number'],
                    'style' => $row['style'],
                    'color' => $row['color'],
                    'rows' => [],
                ];
            }
            $groupedData[$key]['rows'][] = $row;
        }

        return view('backend.library.shipment_data.reports.waste', [
            'groupedData' => $groupedData,
            'styles' => $styles,
            'colors' => $colors,
            'productCombinations' => $productCombinations,
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'styleId' => $styleId,
            'colorId' => $colorId,
            'poNumber' => $poNumber,
        ]);
    }

    // Helper method to get waste quantities from various tables
    private function getWasteQuantities($model, $wasteField, $productCombinationId, $dateFilter)
    {
        return $model::where('product_combination_id', $productCombinationId)
            ->when($dateFilter, function ($query) use ($dateFilter) {
                return $dateFilter($query);
            })
            ->get()
            ->flatMap(function ($item) use ($wasteField) {
                $wasteData = $item->$wasteField ?? [];
                return collect($wasteData)->mapWithKeys(function ($value, $key) {
                    return [(string)$key => $value]; // Ensure key is string to match JSON format
                });
            })
            ->groupBy(function ($value, $key) {
                return $key;
            })
            ->map(function ($group) {
                return $group->sum();
            })
            ->toArray();
    }

    // Helper method for shipment waste quantities
    private function getShipmentWasteQuantities($productCombinationId, $poNumber, $start_date, $end_date)
    {
        $query = ShipmentData::where('product_combination_id', $productCombinationId);

        if ($poNumber) {
            $query->where('po_number', 'like', '%' . $poNumber . '%');
        }

        if ($start_date && $end_date) {
            $query->whereBetween('date', [$start_date, $end_date]);
        } elseif ($start_date) {
            $query->where('date', '>=', $start_date);
        } elseif ($end_date) {
            $query->where('date', '<=', $end_date);
        }

        return $query->get()
            ->flatMap(function ($item) {
                $wasteData = $item->shipment_waste_quantities ?? [];
                return collect($wasteData)->mapWithKeys(function ($value, $key) {
                    return [(string)$key => $value]; // Ensure key is string
                });
            })
            ->groupBy(function ($value, $key) {
                return $key;
            })
            ->map(function ($group) {
                return $group->sum();
            })
            ->toArray();
    }


    //old_data

    public function old_data_create()
    {
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $distinctPoNumbers = OrderData::where('po_status', 'running')->distinct()->pluck('po_number');

        return view('backend.library.old_data.create', compact('allSizes', 'distinctPoNumbers'));
    }

    // public function old_data_store(Request $request)
    // {
    //     // dd($request->all());
    //     Log::info('Old Data Store Request Data:', $request->all());

    //     $request->validate([
    //         'date' => 'required|date',
    //         'po_number' => 'required|array',
    //         'po_number.*' => 'string',
    //         'Stage' => 'required|string|in:CuttingData,SublimationPrintSend,SublimationPrintReceive,PrintSendData,PrintReceiveData,LineInputData,OutputFinishingData,FinishPackingData',
    //         'old_order' => 'required|in:yes,no',
    //         'rows' => 'required|array',
    //         'rows.*.po_number' => 'required|string|in:' . implode(',', $request->input('po_number', [])),
    //         'rows.*.product_combination_id' => 'required|integer|exists:product_combinations,id',
    //         'rows.*.Old_data_qty' => 'required|array',
    //         'rows.*.Old_data_qty.*' => 'nullable|integer|min:0',
    //     ]);
    //     // dd($request->all());


    //     $allStages = [
    //         'CuttingData',
    //         'SublimationPrintSend',
    //         'SublimationPrintReceive',
    //         'PrintSendData',
    //         'PrintReceiveData',
    //         'LineInputData',
    //         'OutputFinishingData',
    //         'FinishPackingData',
    //         // 'ShipmentData'
    //     ];

    //     $currentStage = $request->Stage;
    //     $stagesToProcess = [];

    //     // Determine all stages from the beginning up to and including the current stage
    //     foreach ($allStages as $stage) {
    //         $stagesToProcess[] = $stage;
    //         if ($stage === $currentStage) {
    //             break;
    //         }
    //     }

    //     try {
    //         DB::beginTransaction();

    //         $poNumbers = implode(',', $request->po_number);

    //         foreach ($request->rows as $rowIndex => $row) {
    //             $productCombinationId = $row['product_combination_id'];
    //             $oldDataQuantities = array_filter($row['Old_data_qty'] ?? [], fn($value) => $value !== null && $value !== '');
    //             $oldDataWasteQuantities = array_filter($row['Old_data_waste'] ?? [], fn($value) => $value !== null && $value !== '');

    //             // Fetch product combination to check flags
    //             $productCombination = ProductCombination::find($productCombinationId);

    //             if (!$productCombination) {
    //                 throw ValidationException::withMessages([
    //                     "rows.{$rowIndex}.product_combination_id" => "Product Combination not found for ID: {$productCombinationId}"
    //                 ]);
    //             }

    //             if (empty($oldDataQuantities) && empty($oldDataWasteQuantities)) {
    //                 Log::info("Skipping row {$rowIndex}: No old data quantities or waste provided.");
    //                 continue;
    //             }

    //             foreach ($stagesToProcess as $stage) {
    //                 // Skip conditional stages if their flags are not set in ProductCombination
    //                 if (in_array($stage, ['SublimationPrintSend', 'SublimationPrintReceive']) && !$productCombination->sublimation_print) {
    //                     Log::info("Skipping stage {$stage} for Product Combination ID {$productCombinationId} as sublimation_print is false.");
    //                     continue;
    //                 }
    //                 if (in_array($stage, ['PrintSendData', 'PrintReceiveData']) && !$productCombination->print_embroidery) {
    //                     Log::info("Skipping stage {$stage} for Product Combination ID {$productCombinationId} as print_embroidery is false.");
    //                     continue;
    //                 }

    //                 $quantities = [];
    //                 $wasteQuantities = [];
    //                 $totalQuantity = 0;
    //                 $totalWasteQuantity = 0;

    //                 // Use the Old_data_qty and Old_data_waste for all stages being populated
    //                 foreach ($oldDataQuantities as $sizeId => $qty) {
    //                     if ((int)$qty > 0) {
    //                         $quantities[$sizeId] = (int)$qty;
    //                         $totalQuantity += (int)$qty;
    //                     }
    //                 }

    //                 foreach ($oldDataWasteQuantities as $sizeId => $waste) {
    //                     if ((int)$waste > 0) {
    //                         $wasteQuantities[$sizeId] = (int)$waste;
    //                         $totalWasteQuantity += (int)$waste;
    //                     }
    //                 }

    //                 // Only create a record if there's at least one valid quantity or waste quantity
    //                 if (empty($quantities) && empty($wasteQuantities)) {
    //                     Log::info("Skipping record creation for stage {$stage} for row {$rowIndex}: No quantities or waste provided for this stage.");
    //                     continue;
    //                 }

    //                 $commonData = [
    //                     'date' => $request->date,
    //                     'product_combination_id' => $productCombinationId,
    //                     'po_number' => $poNumbers,
    //                     'old_order' => $request->old_order,
    //                 ];

    //                 switch ($stage) {
    //                     case 'CuttingData':
    //                         CuttingData::create(array_merge($commonData, [
    //                             'cut_quantities' => $quantities,
    //                             'total_cut_quantity' => $totalQuantity,
    //                         ]));
    //                         break;
    //                     case 'SublimationPrintSend':
    //                         SublimationPrintSend::create(array_merge($commonData, [
    //                             'sublimation_print_send_quantities' => $quantities,
    //                             'total_sublimation_print_send_quantity' => $totalQuantity,
    //                             'sublimation_print_send_waste_quantities' => $wasteQuantities,
    //                             'total_sublimation_print_send_waste_quantity' => $totalWasteQuantity,
    //                         ]));
    //                         break;
    //                     case 'SublimationPrintReceive':
    //                         SublimationPrintReceive::create(array_merge($commonData, [
    //                             'sublimation_print_receive_quantities' => $quantities,
    //                             'total_sublimation_print_receive_quantity' => $totalQuantity,
    //                             'sublimation_print_receive_waste_quantities' => $wasteQuantities,
    //                             'total_sublimation_print_receive_waste_quantity' => $totalWasteQuantity,
    //                         ]));
    //                         break;
    //                     case 'PrintSendData':
    //                         PrintSendData::create(array_merge($commonData, [
    //                             'send_quantities' => $quantities,
    //                             'total_send_quantity' => $totalQuantity,
    //                             'send_waste_quantities' => $wasteQuantities,
    //                             'total_send_waste_quantity' => $totalWasteQuantity,
    //                         ]));
    //                         break;
    //                     case 'PrintReceiveData':
    //                         PrintReceiveData::create(array_merge($commonData, [
    //                             'receive_quantities' => $quantities,
    //                             'total_receive_quantity' => $totalQuantity,
    //                             'receive_waste_quantities' => $wasteQuantities,
    //                             'total_receive_waste_quantity' => $totalWasteQuantity,
    //                         ]));
    //                         break;
    //                     case 'LineInputData':
    //                         LineInputData::create(array_merge($commonData, [
    //                             'input_quantities' => $quantities,
    //                             'total_input_quantity' => $totalQuantity,
    //                             'input_waste_quantities' => $wasteQuantities,
    //                             'total_input_waste_quantity' => $totalWasteQuantity,
    //                         ]));
    //                         break;
    //                     case 'OutputFinishingData':
    //                         OutputFinishingData::create(array_merge($commonData, [
    //                             'output_quantities' => $quantities,
    //                             'total_output_quantity' => $totalQuantity,
    //                             'output_waste_quantities' => $wasteQuantities,
    //                             'total_output_waste_quantity' => $totalWasteQuantity,
    //                         ]));
    //                         break;
    //                     case 'FinishPackingData':
    //                         FinishPackingData::create(array_merge($commonData, [
    //                             'packing_quantities' => $quantities,
    //                             'total_packing_quantity' => $totalQuantity,
    //                             'packing_waste_quantities' => $wasteQuantities,
    //                             'total_packing_waste_quantity' => $totalWasteQuantity,
    //                         ]));
    //                         break;
    //                     case 'ShipmentData':
    //                         ShipmentData::create(array_merge($commonData, [
    //                             'shipment_quantities' => $quantities,
    //                             'total_shipment_quantity' => $totalQuantity,
    //                             'shipment_waste_quantities' => $wasteQuantities,
    //                             'total_shipment_waste_quantity' => $totalWasteQuantity,
    //                         ]));
    //                         break;
    //                     default:
    //                         // Should not happen if validation is correct
    //                         break;
    //                 }
    //                 Log::info("Created data for stage {$stage} for PO {$poNumbers}, Combination ID: {$productCombinationId}");
    //             }
    //         }

    //         DB::commit();

    //         return redirect()->route('old_data_index')->withMessage('Old order data created successfully.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Error storing old order data: ' . $e->getMessage(), ['exception' => $e]);
    //         return redirect()->back()
    //             ->with('error', 'Error occurred while creating old order data: ' . $e->getMessage())
    //             ->withInput();
    //     }
    // }

    public function old_data_store(Request $request)
    {
        Log::info('Old Data Store Request Data:', $request->all());

        $request->validate([
            'date' => 'required|date',
            'po_number' => 'required|array',
            'po_number.*' => 'string',
            'Stage' => 'required|string|in:CuttingData,SublimationPrintSend,SublimationPrintReceive,PrintSendData,PrintReceiveData,LineInputData,OutputFinishingData,FinishPackingData',
            'old_order' => 'required|in:yes,no',
            'rows' => 'required|array',
            'rows.*.po_number' => 'required|string|in:' . implode(',', $request->input('po_number', [])),
            'rows.*.product_combination_id' => 'required|integer|exists:product_combinations,id',
            'rows.*.Old_data_qty' => 'required|array',
            'rows.*.Old_data_qty.*' => 'nullable|integer|min:0',
        ]);

        $allStages = [
            'CuttingData',
            'SublimationPrintSend',
            'SublimationPrintReceive',
            'PrintSendData',
            'PrintReceiveData',
            'LineInputData',
            'OutputFinishingData',
            'FinishPackingData',
        ];

        $currentStage = $request->Stage;
        $stagesToProcess = [];

        foreach ($allStages as $stage) {
            $stagesToProcess[] = $stage;
            if ($stage === $currentStage) {
                break;
            }
        }

        try {
            DB::beginTransaction();

            foreach ($request->rows as $rowIndex => $row) {
                $productCombinationId = $row['product_combination_id'];
                $poNumber = $row['po_number']; // Get individual PO number from row

                $oldDataQuantities = array_filter($row['Old_data_qty'] ?? [], fn($value) => $value !== null && $value !== '');
                $oldDataWasteQuantities = array_filter($row['Old_data_waste'] ?? [], fn($value) => $value !== null && $value !== '');

                $productCombination = ProductCombination::find($productCombinationId);

                if (!$productCombination) {
                    throw ValidationException::withMessages([
                        "rows.{$rowIndex}.product_combination_id" => "Product Combination not found for ID: {$productCombinationId}"
                    ]);
                }

                if (empty($oldDataQuantities) && empty($oldDataWasteQuantities)) {
                    Log::info("Skipping row {$rowIndex}: No old data quantities or waste provided.");
                    continue;
                }

                foreach ($stagesToProcess as $stage) {
                    // Skip conditional stages if their flags are not set in ProductCombination
                    if (in_array($stage, ['SublimationPrintSend', 'SublimationPrintReceive']) && !$productCombination->sublimation_print) {
                        Log::info("Skipping stage {$stage} for Product Combination ID {$productCombinationId} as sublimation_print is false.");
                        continue;
                    }
                    if (in_array($stage, ['PrintSendData', 'PrintReceiveData']) && !$productCombination->print_embroidery) {
                        Log::info("Skipping stage {$stage} for Product Combination ID {$productCombinationId} as print_embroidery is false.");
                        continue;
                    }

                    $quantities = [];
                    $wasteQuantities = [];
                    $totalQuantity = 0;
                    $totalWasteQuantity = 0;

                    foreach ($oldDataQuantities as $sizeId => $qty) {
                        if ((int)$qty > 0) {
                            $quantities[$sizeId] = (int)$qty;
                            $totalQuantity += (int)$qty;
                        }
                    }

                    foreach ($oldDataWasteQuantities as $sizeId => $waste) {
                        if ((int)$waste > 0) {
                            $wasteQuantities[$sizeId] = (int)$waste;
                            $totalWasteQuantity += (int)$waste;
                        }
                    }

                    if (empty($quantities) && empty($wasteQuantities)) {
                        Log::info("Skipping record creation for stage {$stage} for row {$rowIndex}: No quantities or waste provided for this stage.");
                        continue;
                    }

                    $commonData = [
                        'date' => $request->date,
                        'product_combination_id' => $productCombinationId,
                        'po_number' => $poNumber, // Use individual PO number instead of imploded string
                        'old_order' => $request->old_order,
                    ];

                    switch ($stage) {
                        case 'CuttingData':
                            CuttingData::create(array_merge($commonData, [
                                'cut_quantities' => $quantities,
                                'total_cut_quantity' => $totalQuantity,
                            ]));
                            break;
                        case 'SublimationPrintSend':
                            SublimationPrintSend::create(array_merge($commonData, [
                                'sublimation_print_send_quantities' => $quantities,
                                'total_sublimation_print_send_quantity' => $totalQuantity,
                                'sublimation_print_send_waste_quantities' => $wasteQuantities,
                                'total_sublimation_print_send_waste_quantity' => $totalWasteQuantity,
                            ]));
                            break;
                        case 'SublimationPrintReceive':
                            SublimationPrintReceive::create(array_merge($commonData, [
                                'sublimation_print_receive_quantities' => $quantities,
                                'total_sublimation_print_receive_quantity' => $totalQuantity,
                                'sublimation_print_receive_waste_quantities' => $wasteQuantities,
                                'total_sublimation_print_receive_waste_quantity' => $totalWasteQuantity,
                            ]));
                            break;
                        case 'PrintSendData':
                            PrintSendData::create(array_merge($commonData, [
                                'send_quantities' => $quantities,
                                'total_send_quantity' => $totalQuantity,
                                'send_waste_quantities' => $wasteQuantities,
                                'total_send_waste_quantity' => $totalWasteQuantity,
                            ]));
                            break;
                        case 'PrintReceiveData':
                            PrintReceiveData::create(array_merge($commonData, [
                                'receive_quantities' => $quantities,
                                'total_receive_quantity' => $totalQuantity,
                                'receive_waste_quantities' => $wasteQuantities,
                                'total_receive_waste_quantity' => $totalWasteQuantity,
                            ]));
                            break;
                        case 'LineInputData':
                            LineInputData::create(array_merge($commonData, [
                                'input_quantities' => $quantities,
                                'total_input_quantity' => $totalQuantity,
                                'input_waste_quantities' => $wasteQuantities,
                                'total_input_waste_quantity' => $totalWasteQuantity,
                            ]));
                            break;
                        case 'OutputFinishingData':
                            OutputFinishingData::create(array_merge($commonData, [
                                'output_quantities' => $quantities,
                                'total_output_quantity' => $totalQuantity,
                                'output_waste_quantities' => $wasteQuantities,
                                'total_output_waste_quantity' => $totalWasteQuantity,
                            ]));
                            break;
                        case 'FinishPackingData':
                            FinishPackingData::create(array_merge($commonData, [
                                'packing_quantities' => $quantities,
                                'total_packing_quantity' => $totalQuantity,
                                'packing_waste_quantities' => $wasteQuantities,
                                'total_packing_waste_quantity' => $totalWasteQuantity,
                            ]));
                            break;
                        default:
                            break;
                    }
                    Log::info("Created data for stage {$stage} for PO {$poNumber}, Combination ID: {$productCombinationId}");
                }
            }

            DB::commit();

            return redirect()->route('old_data_index')->withMessage('Old order data created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing old order data: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()
                ->with('error', 'Error occurred while creating old order data: ' . $e->getMessage())
                ->withInput();
        }
    }


    public function old_data_index(Request $request)
    {
        // Define all models that store old order data
        $oldOrderModels = [
            'CuttingData' => CuttingData::class,
            'SublimationPrintSend' => SublimationPrintSend::class,
            'SublimationPrintReceive' => SublimationPrintReceive::class,
            'PrintSendData' => PrintSendData::class,
            'PrintReceiveData' => PrintReceiveData::class,
            'LineInputData' => LineInputData::class,
            'OutputFinishingData' => OutputFinishingData::class,
            'FinishPackingData' => FinishPackingData::class,
            'ShipmentData' => ShipmentData::class,
        ];

        $allOldData = collect(); // Initialize an empty collection to hold all consolidated data

        // Get filter parameters
        $styleIds = (array) $request->input('style_id', []);
        $colorIds = (array) $request->input('color_id', []);
        $poNumbers = (array) $request->input('po_number', []);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');
        $perPage = $request->input('per_page', 10); // Items per page for pagination

        foreach ($oldOrderModels as $stageName => $modelClass) {
            // Start a query for the current model
            $query = $modelClass::where('old_order', 'yes');

            // Eager load relationships for productCombination, style, color, size
            $query->with('productCombination.style', 'productCombination.color', 'productCombination.size');

            // Apply filters at the database level where possible for efficiency
            if (!empty($styleIds) || !empty($colorIds)) {
                $query->whereHas('productCombination', function ($q) use ($styleIds, $colorIds) {
                    if (!empty($styleIds)) {
                        $q->whereIn('style_id', $styleIds);
                    }
                    if (!empty($colorIds)) {
                        $q->whereIn('color_id', $colorIds);
                    }
                });
            }

            if (!empty($poNumbers)) {
                $query->whereIn('po_number', $poNumbers);
            }

            if ($startDate && $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            } elseif ($request->filled('date')) {
                $query->whereDate('date', $request->input('date'));
            }

            // Global search at the database level for relevant fields
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('po_number', 'like', '%' . $search . '%')
                        ->orWhereHas('productCombination.style', function ($q2) use ($search) {
                            $q2->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('productCombination.color', function ($q2) use ($search) {
                            $q2->where('name', 'like', '%' . $search . '%');
                        });
                    // You might need to add other searchable fields depending on the specific model's schema
                    // For example, if shipment_number exists in ShipmentData:
                    if ($q->getModel() instanceof ShipmentData) {
                        $q->orWhere('shipment_number', 'like', '%' . $search . '%');
                    }
                });
            }

            $data = $query->get(); // Get the filtered data for the current model

            foreach ($data as $item) {
                // Prepare common attributes
                $record = [
                    'id' => $item->id,
                    'stage' => $stageName,
                    'date' => $item->date,
                    'po_number' => $item->po_number,
                    'old_order' => $item->old_order,
                    'product_combination_id' => $item->product_combination_id,
                    'style_name' => $item->productCombination->style->name ?? 'N/A',
                    'color_name' => $item->productCombination->color->name ?? 'N/A',
                    'size_name' => $item->productCombination->size->name ?? 'N/A', // This might not be relevant if quantities are by size_id
                    'quantities' => [],
                    'waste_quantities' => [],
                    'total_quantity' => 0,
                    'total_waste_quantity' => 0,
                ];

                // Handle specific field names for each model
                if ($stageName === 'CuttingData') {
                    $record['quantities'] = $item->cut_quantities ?? [];
                    $record['total_quantity'] = $item->total_cut_quantity ?? 0;
                } else if ($stageName === 'SublimationPrintSend') {
                    $record['quantities'] = $item->sublimation_print_send_quantities ?? [];
                    $record['total_quantity'] = $item->total_sublimation_print_send_quantity ?? 0;
                    $record['waste_quantities'] = $item->sublimation_print_send_waste_quantities ?? [];
                    $record['total_waste_quantity'] = $item->total_sublimation_print_send_waste_quantity ?? 0;
                } else if ($stageName === 'SublimationPrintReceive') {
                    $record['quantities'] = $item->sublimation_print_receive_quantities ?? [];
                    $record['total_quantity'] = $item->total_sublimation_print_receive_quantity ?? 0;
                    $record['waste_quantities'] = $item->sublimation_print_receive_waste_quantities ?? [];
                    $record['total_waste_quantity'] = $item->total_sublimation_print_receive_waste_quantity ?? 0;
                } else if ($stageName === 'PrintSendData') {
                    $record['quantities'] = $item->send_quantities ?? [];
                    $record['total_quantity'] = $item->total_send_quantity ?? 0;
                    $record['waste_quantities'] = $item->send_waste_quantities ?? [];
                    $record['total_waste_quantity'] = $item->total_send_waste_quantity ?? 0;
                } else if ($stageName === 'PrintReceiveData') {
                    $record['quantities'] = $item->receive_quantities ?? [];
                    $record['total_quantity'] = $item->total_receive_quantity ?? 0;
                    $record['waste_quantities'] = $item->receive_waste_quantities ?? [];
                    $record['total_waste_quantity'] = $item->total_receive_waste_quantity ?? 0;
                } else if ($stageName === 'LineInputData') {
                    $record['quantities'] = $item->input_quantities ?? [];
                    $record['total_quantity'] = $item->total_input_quantity ?? 0;
                    $record['waste_quantities'] = $item->input_waste_quantities ?? [];
                    $record['total_waste_quantity'] = $item->total_input_waste_quantity ?? 0;
                } else if ($stageName === 'OutputFinishingData') {
                    $record['quantities'] = $item->output_quantities ?? [];
                    $record['total_quantity'] = $item->total_output_quantity ?? 0;
                    $record['waste_quantities'] = $item->output_waste_quantities ?? [];
                    $record['total_waste_quantity'] = $item->total_output_waste_quantity ?? 0;
                } else if ($stageName === 'FinishPackingData') {
                    $record['quantities'] = $item->packing_quantities ?? [];
                    $record['total_quantity'] = $item->total_packing_quantity ?? 0;
                    $record['waste_quantities'] = $item->packing_waste_quantities ?? [];
                    $record['total_waste_quantity'] = $item->total_packing_waste_quantity ?? 0;
                } else if ($stageName === 'ShipmentData') {
                    $record['quantities'] = $item->shipment_quantities ?? [];
                    $record['total_quantity'] = $item->total_shipment_quantity ?? 0;
                    $record['waste_quantities'] = $item->shipment_waste_quantities ?? [];
                    $record['total_waste_quantity'] = $item->total_shipment_waste_quantity ?? 0;
                }

                $allOldData->push($record);
            }
        }

        // Apply Collection-based filters that couldn't be pushed to the database
        // For example, if you had a search on 'stage'
        // if ($request->filled('stage')) {
        //     $allOldData = $allOldData->where('stage', $request->input('stage'));
        // }

        // Sort the consolidated data
        $allOldData = $allOldData->sortBy('date')->sortBy('po_number')->values(); // values() to reset keys after sorting

        // Manual Pagination for the Collection
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $itemCollection = $allOldData;
        $currentItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->values();

        $paginatedOldData = new LengthAwarePaginator(
            $currentItems,
            $itemCollection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        $paginatedOldData->appends(request()->except('page')); // Keep filter parameters in pagination links

        $allSizes = Size::all();

        // Get filter options
        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        // Fetch distinct PO numbers from all relevant models, or just CuttingData if it's the primary source
        $distinctPoNumbers = CuttingData::where('old_order', 'yes')->distinct()->pluck('po_number')->filter()->values();

        return view('backend.library.old_data.index', compact('paginatedOldData', 'allSizes', 'allStyles', 'allColors', 'distinctPoNumbers', 'perPage'));
    }

    // DashboardData
    // public function DashboardData()
    // {
    //     $currentMonth = Carbon::now()->format('Y-m');
    //     $currentYear = Carbon::now()->year;

    //     // Get unique po_status values
    //     $statuses = DB::table('order_data')->select('po_status')->distinct()->pluck('po_status')->toArray();
    //     $statusSums = array_map(function ($status) {
    //         return DB::raw("SUM(CASE WHEN po_status = '$status' THEN total_order_quantity ELSE 0 END) as {$status}_orders");
    //     }, $statuses);

    //     $selectStatements = array_merge([
    //         DB::raw('SUM(total_order_quantity) as total_orders'),
    //         DB::raw('COUNT(*) as order_count'),
    //     ], $statusSums);

    //     $ordersData = DB::table('order_data')
    //         ->select($selectStatements)
    //         ->first();

    //     $cuttingData = DB::table('cutting_data')
    //         ->select(
    //             DB::raw('SUM(total_cut_quantity) as total_cut'),
    //             DB::raw('AVG(total_cut_quantity) as avg_cut'),
    //             DB::raw('SUM(COALESCE(total_cut_waste_quantity, 0)) as total_cut_waste')
    //         )
    //         ->first();

    //     $printingData = DB::table('sublimation_print_sends as sps')
    //         ->leftJoin('sublimation_print_receives as spr', function ($join) {
    //             $join->on('sps.po_number', '=', 'spr.po_number')
    //                 ->on('sps.product_combination_id', '=', 'spr.product_combination_id');
    //         })
    //         ->select(
    //             DB::raw('SUM(sps.total_sublimation_print_send_quantity) as total_sublimation_sent'),
    //             DB::raw('SUM(COALESCE(spr.total_sublimation_print_receive_quantity, 0)) as total_sublimation_received'),
    //             DB::raw('SUM(COALESCE(spr.total_sublimation_print_receive_waste_quantity, 0)) as sublimation_waste')
    //         )
    //         ->first();

    //     $printData = DB::table('print_send_data as psd')
    //         ->leftJoin('print_receive_data as prd', function ($join) {
    //             $join->on('psd.po_number', '=', 'prd.po_number')
    //                 ->on('psd.product_combination_id', '=', 'prd.product_combination_id');
    //         })
    //         ->select(
    //             DB::raw('SUM(psd.total_send_quantity) as total_print_sent'),
    //             DB::raw('SUM(COALESCE(prd.total_receive_quantity, 0)) as total_print_received'),
    //             DB::raw('SUM(COALESCE(prd.total_receive_waste_quantity, 0)) as print_waste')
    //         )
    //         ->first();

    //     $sewingData = DB::table('line_input_data as lid')
    //         ->leftJoin('output_finishing_data as ofd', function ($join) {
    //             $join->on('lid.po_number', '=', 'ofd.po_number')
    //                 ->on('lid.product_combination_id', '=', 'ofd.product_combination_id');
    //         })
    //         ->select(
    //             DB::raw('SUM(lid.total_input_quantity) as total_input'),
    //             DB::raw('SUM(COALESCE(ofd.total_output_quantity, 0)) as total_output'),
    //             DB::raw('SUM(COALESCE(lid.total_input_waste_quantity, 0)) as input_waste'),
    //             DB::raw('SUM(COALESCE(ofd.total_output_waste_quantity, 0)) as output_waste')
    //         )
    //         ->first();

    //     $packingData = DB::table('finish_packing_data as fpd')
    //         ->leftJoin('shipment_data as sd', function ($join) {
    //             $join->on('fpd.po_number', '=', 'sd.po_number')
    //                 ->on('fpd.product_combination_id', '=', 'sd.product_combination_id');
    //         })
    //         ->select(
    //             DB::raw('SUM(fpd.total_packing_quantity) as total_packed'),
    //             DB::raw('SUM(COALESCE(sd.total_shipment_quantity, 0)) as total_shipped'),
    //             DB::raw('SUM(COALESCE(fpd.total_packing_waste_quantity, 0)) as packing_waste'),
    //             DB::raw('SUM(COALESCE(sd.total_shipment_waste_quantity, 0)) as shipment_waste')
    //         )
    //         ->first();

    //     $wasteData = [
    //         'cutting' => $cuttingData->total_cut_waste ?? 0,
    //         'printing' => ($printingData->sublimation_waste ?? 0) + ($printData->print_waste ?? 0),
    //         'sewing' => ($sewingData->input_waste ?? 0) + ($sewingData->output_waste ?? 0),
    //         'packing' => ($packingData->packing_waste ?? 0) + ($packingData->shipment_waste ?? 0)
    //     ];

    //     $recentActivities = $this->getRecentActivities();
    //     $monthlyTrends = $this->getMonthlyTrends();
    //     $efficiencies = $this->calculateEfficiencies($ordersData, $cuttingData, $sewingData, $packingData);

    //     // Get monthly data for each metric
    //     $monthlyData = $this->getMonthlyData($currentYear);

    //     $readyGoodsReportDashboard = app(ShipmentDataController::class)->readyGoodsReportDashboard();


    //     return view('backend.DashboardData', compact(
    //         'ordersData',
    //         'cuttingData',
    //         'printingData',
    //         'printData',
    //         'sewingData',
    //         'packingData',
    //         'wasteData',
    //         'recentActivities',
    //         'monthlyTrends',
    //         'efficiencies',
    //         'statuses',
    //         'monthlyData'
    //     ));
    // }

    public function DashboardDataJson()
    {
        $currentMonth = Carbon::now()->format('Y-m');
        $currentYear = Carbon::now()->year;

        $statuses = DB::table('order_data')->select('po_status')->distinct()->pluck('po_status')->toArray();
        $statusSums = array_map(function ($status) {
            return DB::raw("SUM(CASE WHEN po_status = '$status' THEN total_order_quantity ELSE 0 END) as {$status}_orders");
        }, $statuses);
        $selectStatements = array_merge([
            DB::raw('SUM(total_order_quantity) as total_orders'),
            DB::raw('COUNT(*) as order_count'),
        ], $statusSums);

        $ordersData = DB::table('order_data')
            ->select($selectStatements)
            ->first();

        $cuttingData = DB::table('cutting_data')
            ->select(
                DB::raw('SUM(total_cut_quantity) as total_cut'),
                DB::raw('AVG(total_cut_quantity) as avg_cut'),
                DB::raw('SUM(COALESCE(total_cut_waste_quantity, 0)) as total_cut_waste')
            )
            ->first();

        $printingData = DB::table('sublimation_print_sends as sps')
            ->leftJoin('sublimation_print_receives as spr', function ($join) {
                $join->on('sps.po_number', '=', 'spr.po_number')
                    ->on('sps.product_combination_id', '=', 'spr.product_combination_id');
            })
            ->select(
                DB::raw('SUM(sps.total_sublimation_print_send_quantity) as total_sublimation_sent'),
                DB::raw('SUM(COALESCE(spr.total_sublimation_print_receive_quantity, 0)) as total_sublimation_received'),
                DB::raw('SUM(COALESCE(spr.total_sublimation_print_receive_waste_quantity, 0)) as sublimation_waste')
            )
            ->first();

        $printData = DB::table('print_send_data as psd')
            ->leftJoin('print_receive_data as prd', function ($join) {
                $join->on('psd.po_number', '=', 'prd.po_number')
                    ->on('psd.product_combination_id', '=', 'prd.product_combination_id');
            })
            ->select(
                DB::raw('SUM(psd.total_send_quantity) as total_print_sent'),
                DB::raw('SUM(COALESCE(prd.total_receive_quantity, 0)) as total_print_received'),
                DB::raw('SUM(COALESCE(prd.total_receive_waste_quantity, 0)) as print_waste')
            )
            ->first();

        $sewingData = DB::table('line_input_data as lid')
            ->leftJoin('output_finishing_data as ofd', function ($join) {
                $join->on('lid.po_number', '=', 'ofd.po_number')
                    ->on('lid.product_combination_id', '=', 'ofd.product_combination_id');
            })
            ->select(
                DB::raw('SUM(lid.total_input_quantity) as total_input'),
                DB::raw('SUM(COALESCE(ofd.total_output_quantity, 0)) as total_output'),
                DB::raw('SUM(COALESCE(lid.total_input_waste_quantity, 0)) as input_waste'),
                DB::raw('SUM(COALESCE(ofd.total_output_waste_quantity, 0)) as output_waste')
            )
            ->first();

        $packingData = DB::table('finish_packing_data as fpd')
            ->leftJoin('shipment_data as sd', function ($join) {
                $join->on('fpd.po_number', '=', 'sd.po_number')
                    ->on('fpd.product_combination_id', '=', 'sd.product_combination_id');
            })
            ->select(
                DB::raw('SUM(fpd.total_packing_quantity) as total_packed'),
                DB::raw('SUM(COALESCE(sd.total_shipment_quantity, 0)) as total_shipped'),
                DB::raw('SUM(COALESCE(fpd.total_packing_waste_quantity, 0)) as packing_waste'),
                DB::raw('SUM(COALESCE(sd.total_shipment_waste_quantity, 0)) as shipment_waste')
            )
            ->first();

        $wasteData = [
            'cutting' => $cuttingData->total_cut_waste ?? 0,
            'printing' => ($printingData->sublimation_waste ?? 0) + ($printData->print_waste ?? 0),
            'sewing' => ($sewingData->input_waste ?? 0) + ($sewingData->output_waste ?? 0),
            'packing' => ($packingData->packing_waste ?? 0) + ($packingData->shipment_waste ?? 0)
        ];

        $monthlyTrends = $this->getMonthlyTrends();
        $monthlyData = $this->getMonthlyData($currentYear);

        return response()->json([
            'total_orders' => $ordersData->total_orders ?? 0,
            'order_count' => $ordersData->order_count ?? 0,
            'statuses' => array_map(function ($status) use ($ordersData) {
                return [
                    'status' => $status,
                    'quantity' => $ordersData->{$status . '_orders'} ?? 0
                ];
            }, $statuses),
            'cutting' => [
                'total_cut' => $cuttingData->total_cut ?? 0,
                'avg_cut' => $cuttingData->avg_cut ?? 0,
                'total_cut_waste' => $cuttingData->total_cut_waste ?? 0
            ],
            'printing' => [
                'total_sublimation_sent' => $printingData->total_sublimation_sent ?? 0,
                'total_sublimation_received' => $printingData->total_sublimation_received ?? 0,
                'sublimation_waste' => $printingData->sublimation_waste ?? 0,
                'total_print_sent' => $printData->total_print_sent ?? 0,
                'total_print_received' => $printData->total_print_received ?? 0,
                'print_waste' => $printData->print_waste ?? 0
            ],
            'sewing' => [
                'total_input' => $sewingData->total_input ?? 0,
                'total_output' => $sewingData->total_output ?? 0,
                'input_waste' => $sewingData->input_waste ?? 0,
                'output_waste' => $sewingData->output_waste ?? 0
            ],
            'packing' => [
                'total_packed' => $packingData->total_packed ?? 0,
                'total_shipped' => $packingData->total_shipped ?? 0,
                'packing_waste' => $packingData->packing_waste ?? 0,
                'shipment_waste' => $packingData->shipment_waste ?? 0
            ],
            'waste_distribution' => array_values($wasteData),
            'monthly_orders' => array_column($monthlyTrends, 'orders'),
            'monthly_shipments' => array_column($monthlyTrends, 'shipments'),
            'monthly_data' => $monthlyData
        ]);
    }

    private function getMonthlyTrends()
    {
        $currentYear = Carbon::now()->year;

        $monthlyData = DB::table('order_data')
            ->select(
                DB::raw('MONTH(date) as month'),
                DB::raw('SUM(total_order_quantity) as orders'),
                DB::raw('(SELECT SUM(total_shipment_quantity) FROM shipment_data sd WHERE MONTH(sd.date) = MONTH(order_data.date) AND YEAR(sd.date) = ' . $currentYear . ') as shipments')
            )
            ->whereYear('date', $currentYear)
            ->groupBy(DB::raw('MONTH(date)'))
            ->orderBy(DB::raw('MONTH(date)'))
            ->get();

        $trends = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthData = $monthlyData->firstWhere('month', $i);
            $trends[] = [
                'month' => Carbon::create()->month($i)->format('M'),
                'orders' => $monthData->orders ?? 0,
                'shipments' => $monthData->shipments ?? 0
            ];
        }

        return $trends;
    }

    private function getMonthlyData($currentYear)
    {
        $months = array_map(function ($i) {
            return Carbon::create()->month($i)->format('M');
        }, range(1, 12));

        // Monthly Orders Data
        $monthlyOrders = DB::table('order_data')
            ->select(
                DB::raw('MONTH(order_data.date) as month'),
                DB::raw('SUM(total_order_quantity) as total_orders')
            )
            ->whereYear('order_data.date', $currentYear)
            ->groupBy(DB::raw('MONTH(order_data.date)'))
            ->get();

        // Monthly Cutting Data
        $monthlyCutting = DB::table('cutting_data')
            ->select(
                DB::raw('MONTH(cutting_data.date) as month'),
                DB::raw('SUM(total_cut_quantity) as total_cut'),
                DB::raw('SUM(COALESCE(total_cut_waste_quantity, 0)) as total_cut_waste')
            )
            ->whereYear('cutting_data.date', $currentYear)
            ->groupBy(DB::raw('MONTH(cutting_data.date)'))
            ->get();

        // Monthly Sewing Data
        $monthlySewing = DB::table('line_input_data')
            ->select(
                DB::raw('MONTH(line_input_data.date) as month'),
                DB::raw('SUM(total_input_quantity) as total_input'),
                DB::raw('SUM(COALESCE(total_input_waste_quantity, 0)) as input_waste')
            )
            ->whereYear('line_input_data.date', $currentYear)
            ->groupBy(DB::raw('MONTH(line_input_data.date)'))
            ->leftJoin('output_finishing_data as ofd', function ($join) {
                $join->on('line_input_data.po_number', '=', 'ofd.po_number')
                    ->on('line_input_data.product_combination_id', '=', 'ofd.product_combination_id');
            })
            ->selectRaw('SUM(COALESCE(ofd.total_output_quantity, 0)) as total_output')
            ->selectRaw('SUM(COALESCE(ofd.total_output_waste_quantity, 0)) as output_waste')
            ->get();

        // Monthly Packing Data
        $monthlyPacking = DB::table('finish_packing_data')
            ->select(
                DB::raw('MONTH(finish_packing_data.date) as month'),
                DB::raw('SUM(total_packing_quantity) as total_packed'),
                DB::raw('SUM(COALESCE(total_packing_waste_quantity, 0)) as packing_waste')
            )
            ->whereYear('finish_packing_data.date', $currentYear)
            ->groupBy(DB::raw('MONTH(finish_packing_data.date)'))
            ->leftJoin('shipment_data as sd', function ($join) {
                $join->on('finish_packing_data.po_number', '=', 'sd.po_number')
                    ->on('finish_packing_data.product_combination_id', '=', 'sd.product_combination_id');
            })
            ->selectRaw('SUM(COALESCE(sd.total_shipment_quantity, 0)) as total_shipped')
            ->selectRaw('SUM(COALESCE(sd.total_shipment_waste_quantity, 0)) as shipment_waste')
            ->get();

        // Monthly Waste Data
        $monthlyWaste = DB::table('cutting_data')
            ->select(DB::raw('MONTH(cutting_data.date) as month'))
            ->whereYear('cutting_data.date', $currentYear)
            ->groupBy(DB::raw('MONTH(cutting_data.date)'))
            ->selectRaw('SUM(COALESCE(total_cut_waste_quantity, 0)) as cutting_waste')
            ->leftJoin('sublimation_print_sends as sps', function ($join) use ($currentYear) {
                $join->on('cutting_data.po_number', '=', 'sps.po_number')
                    ->on('cutting_data.product_combination_id', '=', 'sps.product_combination_id')
                    ->whereRaw('MONTH(sps.date) = MONTH(cutting_data.date)')
                    ->whereRaw('YEAR(sps.date) = ?', [$currentYear]);
            })
            ->leftJoin('sublimation_print_receives as spr', function ($join) {
                $join->on('sps.po_number', '=', 'spr.po_number')
                    ->on('sps.product_combination_id', '=', 'spr.product_combination_id');
            })
            ->leftJoin('print_send_data as psd', function ($join) use ($currentYear) {
                $join->on('cutting_data.po_number', '=', 'psd.po_number')
                    ->on('cutting_data.product_combination_id', '=', 'psd.product_combination_id')
                    ->whereRaw('MONTH(psd.date) = MONTH(cutting_data.date)')
                    ->whereRaw('YEAR(psd.date) = ?', [$currentYear]);
            })
            ->leftJoin('print_receive_data as prd', function ($join) {
                $join->on('psd.po_number', '=', 'prd.po_number')
                    ->on('psd.product_combination_id', '=', 'prd.product_combination_id');
            })
            ->leftJoin('line_input_data as lid', function ($join) use ($currentYear) {
                $join->on('cutting_data.po_number', '=', 'lid.po_number')
                    ->on('cutting_data.product_combination_id', '=', 'lid.product_combination_id')
                    ->whereRaw('MONTH(lid.date) = MONTH(cutting_data.date)')
                    ->whereRaw('YEAR(lid.date) = ?', [$currentYear]);
            })
            ->leftJoin('output_finishing_data as ofd', function ($join) {
                $join->on('lid.po_number', '=', 'ofd.po_number')
                    ->on('lid.product_combination_id', '=', 'ofd.product_combination_id');
            })
            ->leftJoin('finish_packing_data as fpd', function ($join) use ($currentYear) {
                $join->on('cutting_data.po_number', '=', 'fpd.po_number')
                    ->on('cutting_data.product_combination_id', '=', 'fpd.product_combination_id')
                    ->whereRaw('MONTH(fpd.date) = MONTH(cutting_data.date)')
                    ->whereRaw('YEAR(fpd.date) = ?', [$currentYear]);
            })
            ->leftJoin('shipment_data as sd', function ($join) {
                $join->on('fpd.po_number', '=', 'sd.po_number')
                    ->on('fpd.product_combination_id', '=', 'sd.product_combination_id');
            })
            ->selectRaw('SUM(COALESCE(spr.total_sublimation_print_receive_waste_quantity, 0)) + SUM(COALESCE(prd.total_receive_waste_quantity, 0)) as printing_waste')
            ->selectRaw('SUM(COALESCE(lid.total_input_waste_quantity, 0)) + SUM(COALESCE(ofd.total_output_waste_quantity, 0)) as sewing_waste')
            ->selectRaw('SUM(COALESCE(fpd.total_packing_waste_quantity, 0)) + SUM(COALESCE(sd.total_shipment_waste_quantity, 0)) as packing_waste')
            ->get();

        // Monthly Completion Rate
        $monthlyCompletion = DB::table('order_data')
            ->select(
                DB::raw('MONTH(order_data.date) as month'),
                DB::raw('SUM(total_order_quantity) as total_orders')
            )
            ->whereYear('order_data.date', $currentYear)
            ->groupBy(DB::raw('MONTH(order_data.date)'))
            ->leftJoin('shipment_data as sd', function ($join) use ($currentYear) {
                $join->on('order_data.po_number', '=', 'sd.po_number')
                    ->on('order_data.product_combination_id', '=', 'sd.product_combination_id')
                    ->whereRaw('MONTH(sd.date) = MONTH(order_data.date)')
                    ->whereRaw('YEAR(sd.date) = ?', [$currentYear]);
            })
            ->selectRaw('SUM(COALESCE(sd.total_shipment_quantity, 0)) as total_shipped')
            ->get();

        $monthlyData = [];
        for ($i = 1; $i <= 12; $i++) {
            $month = Carbon::create()->month($i)->format('M');
            $orderData = $monthlyOrders->firstWhere('month', $i);
            $cuttingData = $monthlyCutting->firstWhere('month', $i);
            $sewingData = $monthlySewing->firstWhere('month', $i);
            $packingData = $monthlyPacking->firstWhere('month', $i);
            $wasteData = $monthlyWaste->firstWhere('month', $i);
            $completionData = $monthlyCompletion->firstWhere('month', $i);

            $monthlyData[$month] = [
                'total_orders' => [
                    'value' => $orderData->total_orders ?? 0,
                    'label' => 'Total Orders',
                    'unit' => 'Units'
                ],
                'cutting_efficiency' => [
                    'value' => ($orderData && $orderData->total_orders > 0 && $cuttingData) ?
                        round(($cuttingData->total_cut ?? 0) / $orderData->total_orders * 100, 1) : 0,
                    'label' => 'Cutting Efficiency',
                    'unit' => '%'
                ],
                'sewing_efficiency' => [
                    'value' => ($sewingData && $sewingData->total_input > 0) ?
                        round(($sewingData->total_output ?? 0) / $sewingData->total_input * 100, 1) : 0,
                    'label' => 'Sewing Efficiency',
                    'unit' => '%'
                ],
                'packing_progress' => [
                    'value' => $packingData->total_packed ?? 0,
                    'label' => 'Packing Progress',
                    'unit' => 'Units'
                ],
                'total_waste' => [
                    'value' => ($wasteData ?
                        (($wasteData->cutting_waste ?? 0) +
                            ($wasteData->printing_waste ?? 0) +
                            ($wasteData->sewing_waste ?? 0) +
                            ($wasteData->packing_waste ?? 0)) : 0),
                    'label' => 'Total Waste',
                    'unit' => 'Units'
                ],
                'completion_rate' => [
                    'value' => ($completionData && $completionData->total_orders > 0) ?
                        round(($completionData->total_shipped ?? 0) / $completionData->total_orders * 100, 1) : 0,
                    'label' => 'Completion Rate',
                    'unit' => '%'
                ]
            ];
        }

        return $monthlyData;
    }

    private function getRecentActivities()
    {
        $activities = [];

        $recentOrders = DB::table('order_data')
            ->join('styles', 'order_data.style_id', '=', 'styles.id')
            ->join('colors', 'order_data.color_id', '=', 'colors.id')
            ->select('order_data.*', 'styles.name as style_name', 'colors.name as color_name')
            ->orderBy('order_data.created_at', 'desc')
            ->limit(3)
            ->get();

        foreach ($recentOrders as $order) {
            $activities[] = [
                'type' => 'order',
                'po' => $order->po_number,
                'style' => $order->style_name,
                'color' => $order->color_name,
                'quantity' => $order->total_order_quantity,
                'time' => Carbon::parse($order->created_at)->diffForHumans()
            ];
        }

        $recentCutting = DB::table('cutting_data')
            ->join('product_combinations', 'cutting_data.product_combination_id', '=', 'product_combinations.id')
            ->join('styles', 'product_combinations.style_id', '=', 'styles.id')
            ->select('cutting_data.*', 'styles.name as style_name')
            ->orderBy('cutting_data.created_at', 'desc')
            ->limit(2)
            ->get();

        foreach ($recentCutting as $cutting) {
            $activities[] = [
                'type' => 'cutting',
                'po' => $cutting->po_number,
                'style' => $cutting->style_name,
                'quantity' => $cutting->total_cut_quantity,
                'time' => Carbon::parse($cutting->created_at)->diffForHumans()
            ];
        }

        $recentPrinting = DB::table('print_send_data')
            ->join('product_combinations', 'print_send_data.product_combination_id', '=', 'product_combinations.id')
            ->join('styles', 'product_combinations.style_id', '=', 'styles.id')
            ->select('print_send_data.*', 'styles.name as style_name')
            ->orderBy('print_send_data.created_at', 'desc')
            ->limit(2)
            ->get();

        foreach ($recentPrinting as $printing) {
            $activities[] = [
                'type' => 'printing',
                'po' => $printing->po_number,
                'style' => $printing->style_name,
                'quantity' => $printing->total_send_quantity,
                'time' => Carbon::parse($printing->created_at)->diffForHumans()
            ];
        }

        $recentSewing = DB::table('line_input_data')
            ->join('product_combinations', 'line_input_data.product_combination_id', '=', 'product_combinations.id')
            ->join('styles', 'product_combinations.style_id', '=', 'styles.id')
            ->select('line_input_data.*', 'styles.name as style_name')
            ->orderBy('line_input_data.created_at', 'desc')
            ->limit(2)
            ->get();

        foreach ($recentSewing as $sewing) {
            $activities[] = [
                'type' => 'sewing',
                'po' => $sewing->po_number,
                'style' => $sewing->style_name,
                'quantity' => $sewing->total_input_quantity,
                'time' => Carbon::parse($sewing->created_at)->diffForHumans()
            ];
        }

        $recentShipments = DB::table('shipment_data')
            ->join('product_combinations', 'shipment_data.product_combination_id', '=', 'product_combinations.id')
            ->join('styles', 'product_combinations.style_id', '=', 'styles.id')
            ->select('shipment_data.*', 'styles.name as style_name')
            ->orderBy('shipment_data.created_at', 'desc')
            ->limit(2)
            ->get();

        foreach ($recentShipments as $shipment) {
            $activities[] = [
                'type' => 'shipment',
                'po' => $shipment->po_number,
                'style' => $shipment->style_name,
                'quantity' => $shipment->total_shipment_quantity,
                'time' => Carbon::parse($shipment->created_at)->diffForHumans()
            ];
        }

        usort($activities, function ($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });

        return array_slice($activities, 0, 5);
    }

    private function calculateEfficiencies($orders, $cutting, $sewing, $packing)
    {
        $cuttingEfficiency = $orders->total_orders > 0 ?
            (($cutting->total_cut ?? 0) / $orders->total_orders) * 100 : 0;

        $sewingEfficiency = ($sewing->total_input ?? 0) > 0 ?
            (($sewing->total_output ?? 0) / ($sewing->total_input ?? 1)) * 100 : 0;

        $packingEfficiency = ($packing->total_packed ?? 0) > 0 ?
            (($packing->total_shipped ?? 0) / ($packing->total_packed ?? 1)) * 100 : 0;

        $overallEfficiency = ($cuttingEfficiency + $sewingEfficiency + $packingEfficiency) / 3;

        return [
            'cutting' => round($cuttingEfficiency, 1),
            'sewing' => round($sewingEfficiency, 1),
            'packing' => round($packingEfficiency, 1),
            'overall' => round($overallEfficiency, 1)
        ];
    }

    public function DashboardData(Request $request)
    {
        $currentMonth = Carbon::now()->format('Y-m');
        $currentYear = Carbon::now()->year;

        // Get unique po_status values
        $statuses = DB::table('order_data')->select('po_status')->distinct()->pluck('po_status')->toArray();
        $statusSums = array_map(function ($status) {
            return DB::raw("SUM(CASE WHEN po_status = '$status' THEN total_order_quantity ELSE 0 END) as {$status}_orders");
        }, $statuses);

        $selectStatements = array_merge([
            DB::raw('SUM(total_order_quantity) as total_orders'),
            DB::raw('COUNT(*) as order_count'),
        ], $statusSums);

        $ordersData = DB::table('order_data')
            ->select($selectStatements)
            ->first();

        $cuttingData = DB::table('cutting_data')
            ->select(
                DB::raw('SUM(total_cut_quantity) as total_cut'),
                DB::raw('AVG(total_cut_quantity) as avg_cut'),
                DB::raw('SUM(COALESCE(total_cut_waste_quantity, 0)) as total_cut_waste')
            )
            ->first();

        $printingData = DB::table('sublimation_print_sends as sps')
            ->leftJoin('sublimation_print_receives as spr', function ($join) {
                $join->on('sps.po_number', '=', 'spr.po_number')
                    ->on('sps.product_combination_id', '=', 'spr.product_combination_id');
            })
            ->select(
                DB::raw('SUM(sps.total_sublimation_print_send_quantity) as total_sublimation_sent'),
                DB::raw('SUM(COALESCE(spr.total_sublimation_print_receive_quantity, 0)) as total_sublimation_received'),
                DB::raw('SUM(COALESCE(spr.total_sublimation_print_receive_waste_quantity, 0)) as sublimation_waste')
            )
            ->first();

        $printData = DB::table('print_send_data as psd')
            ->leftJoin('print_receive_data as prd', function ($join) {
                $join->on('psd.po_number', '=', 'prd.po_number')
                    ->on('psd.product_combination_id', '=', 'prd.product_combination_id');
            })
            ->select(
                DB::raw('SUM(psd.total_send_quantity) as total_print_sent'),
                DB::raw('SUM(COALESCE(prd.total_receive_quantity, 0)) as total_print_received'),
                DB::raw('SUM(COALESCE(prd.total_receive_waste_quantity, 0)) as print_waste')
            )
            ->first();

        $sewingData = DB::table('line_input_data as lid')
            ->leftJoin('output_finishing_data as ofd', function ($join) {
                $join->on('lid.po_number', '=', 'ofd.po_number')
                    ->on('lid.product_combination_id', '=', 'ofd.product_combination_id');
            })
            ->select(
                DB::raw('SUM(lid.total_input_quantity) as total_input'),
                DB::raw('SUM(COALESCE(ofd.total_output_quantity, 0)) as total_output'),
                DB::raw('SUM(COALESCE(lid.total_input_waste_quantity, 0)) as input_waste'),
                DB::raw('SUM(COALESCE(ofd.total_output_waste_quantity, 0)) as output_waste')
            )
            ->first();

        $packingData = DB::table('finish_packing_data as fpd')
            ->leftJoin('shipment_data as sd', function ($join) {
                $join->on('fpd.po_number', '=', 'sd.po_number')
                    ->on('fpd.product_combination_id', '=', 'sd.product_combination_id');
            })
            ->select(
                DB::raw('SUM(fpd.total_packing_quantity) as total_packed'),
                DB::raw('SUM(COALESCE(sd.total_shipment_quantity, 0)) as total_shipped'),
                DB::raw('SUM(COALESCE(fpd.total_packing_waste_quantity, 0)) as packing_waste'),
                DB::raw('SUM(COALESCE(sd.total_shipment_waste_quantity, 0)) as shipment_waste')
            )
            ->first();

        $wasteData = [
            'cutting' => $cuttingData->total_cut_waste ?? 0,
            'printing' => ($printingData->sublimation_waste ?? 0) + ($printData->print_waste ?? 0),
            'sewing' => ($sewingData->input_waste ?? 0) + ($sewingData->output_waste ?? 0),
            'packing' => ($packingData->packing_waste ?? 0) + ($packingData->shipment_waste ?? 0)
        ];

        // Ready Goods Data
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $reportData = [];
        $cardData = [
            'total_ready' => 0,
            'by_style' => [],
            'by_color' => [],
            'by_po_number' => []
        ];

        // Get distinct month-year combinations
        $monthYears = FinishPackingData::selectRaw("FORMAT(date, 'yyyy-MM') as month_year")
            ->distinct()
            ->orderBy('month_year', 'desc')
            ->pluck('month_year')
            ->toArray();

        // Base query for product combinations
        $productCombinations = ProductCombination::whereHas('finishPackingData')
            ->with('style', 'color')
            ->get();

        foreach ($productCombinations as $pc) {
            $style = $pc->style->name;
            $color = $pc->color->name;

            $poNumbersQuery = FinishPackingData::where('product_combination_id', $pc->id)
                ->whereRaw("FORMAT(date, 'yyyy-MM') = ?", [$currentMonth]);

            $poNumbers = $poNumbersQuery->pluck('po_number')
                ->unique()
                ->toArray();

            foreach ($poNumbers as $poNumber) {
                $key = $poNumber . '-' . $style . '-' . $color;

                if (!isset($reportData[$key])) {
                    $reportData[$key] = [
                        'month_year' => $currentMonth,
                        'po_number' => $poNumber,
                        'style' => $style,
                        'color' => $color,
                        'sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
                        'total' => 0
                    ];
                }

                $packedQuery = FinishPackingData::where('product_combination_id', $pc->id)
                    ->where('po_number', $poNumber)
                    ->whereRaw("FORMAT(date, 'yyyy-MM') = ?", [$currentMonth]);

                $packedQuantities = $packedQuery->get()
                    ->flatMap(fn($item) => $item->packing_quantities)
                    ->groupBy(fn($value, $key) => $key)
                    ->map(fn($group) => $group->sum())
                    ->toArray();

                $shippedQuery = ShipmentData::where('product_combination_id', $pc->id)
                    ->where('po_number', $poNumber)
                    ->whereRaw("FORMAT(date, 'yyyy-MM') = ?", [$currentMonth]);

                $shippedQuantities = $shippedQuery->get()
                    ->flatMap(fn($item) => $item->shipment_quantities)
                    ->groupBy(fn($value, $key) => $key)
                    ->map(fn($group) => $group->sum())
                    ->toArray();

                foreach ($allSizes as $size) {
                    $packed = $packedQuantities[$size->id] ?? 0;
                    $shipped = $shippedQuantities[$size->id] ?? 0;
                    $ready = max(0, $packed - $shipped);

                    $reportData[$key]['sizes'][$size->id] = $ready;
                    $reportData[$key]['total'] += $ready;

                    $cardData['total_ready'] += $ready;
                    $cardData['by_style'][$style] = ($cardData['by_style'][$style] ?? 0) + $ready;
                    $cardData['by_color'][$color] = ($cardData['by_color'][$color] ?? 0) + $ready;
                    $cardData['by_po_number'][$poNumber] = ($cardData['by_po_number'][$poNumber] ?? 0) + $ready;
                }

                if ($reportData[$key]['total'] == 0) {
                    unset($reportData[$key]);
                }
            }
        }

        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = array_unique(
            array_merge(
                FinishPackingData::distinct()->pluck('po_number')->filter()->values()->toArray(),
                ShipmentData::distinct()->pluck('po_number')->filter()->values()->toArray()
            )
        );
        sort($distinctPoNumbers);

        $recentActivities = $this->getRecentActivities();
        $monthlyTrends = $this->getMonthlyTrends();
        $efficiencies = $this->calculateEfficiencies($ordersData, $cuttingData, $sewingData, $packingData);
        $monthlyData = $this->getMonthlyData($currentYear);

        return view('backend.DashboardData', compact(
            'ordersData',
            'cuttingData',
            'printingData',
            'printData',
            'sewingData',
            'packingData',
            'wasteData',
            'recentActivities',
            'monthlyTrends',
            'efficiencies',
            'statuses',
            'monthlyData',
            'reportData',
            'allSizes',
            'allStyles',
            'allColors',
            'distinctPoNumbers',
            'monthYears',
            'cardData'
        ));
    }

    // Keep the original readyGoodsReportDashboard for the standalone report page
    public function readyGoodsReportDashboard(Request $request)
    {
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $reportData = [];
        $cardData = [
            'total_ready' => 0,
            'by_style' => [],
            'by_color' => [],
            'by_po_number' => []
        ];

        $styleIds = $request->input('style_id', []);
        $colorIds = $request->input('color_id', []);
        $poNumbers = $request->input('po_number', []);
        $monthYear = $request->input('month_year');
        $search = $request->input('search');

        $monthYears = FinishPackingData::selectRaw("FORMAT(date, 'yyyy-MM') as month_year")
            ->distinct()
            ->orderBy('month_year', 'desc')
            ->pluck('month_year')
            ->toArray();

        $productCombinationsQuery = ProductCombination::whereHas('finishPackingData')
            ->with('style', 'color');

        if (!empty($styleIds)) {
            $productCombinationsQuery->whereIn('style_id', $styleIds);
        }

        if (!empty($colorIds)) {
            $productCombinationsQuery->whereIn('color_id', $colorIds);
        }

        if ($search) {
            $productCombinationsQuery->where(function ($q) use ($search) {
                $q->whereHas('style', function ($q2) use ($search) {
                    $q2->where('name', 'like', '%' . $search . '%');
                })->orWhereHas('color', function ($q2) use ($search) {
                    $q2->where('name', 'like', '%' . $search . '%');
                });
            });
        }

        $productCombinations = $productCombinationsQuery->get();

        foreach ($productCombinations as $pc) {
            $style = $pc->style->name;
            $color = $pc->color->name;

            $poNumbersQuery = FinishPackingData::where('product_combination_id', $pc->id);

            if (!empty($poNumbers)) {
                $poNumbersQuery->whereIn('po_number', $poNumbers);
            }

            if ($monthYear) {
                $poNumbersQuery->whereRaw("FORMAT(date, 'yyyy-MM') = ?", [$monthYear]);
            }

            $poNumbers = $poNumbersQuery->pluck('po_number')
                ->unique()
                ->toArray();

            foreach ($poNumbers as $poNumber) {
                $key = $poNumber . '-' . $style . '-' . $color;

                if (!isset($reportData[$key])) {
                    $reportData[$key] = [
                        'month_year' => $monthYear ?: 'All',
                        'po_number' => $poNumber,
                        'style' => $style,
                        'color' => $color,
                        'sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
                        'total' => 0
                    ];
                }

                $packedQuery = FinishPackingData::where('product_combination_id', $pc->id)
                    ->where('po_number', $poNumber);

                if ($monthYear) {
                    $packedQuery->whereRaw("FORMAT(date, 'yyyy-MM') = ?", [$monthYear]);
                }

                $packedQuantities = $packedQuery->get()
                    ->flatMap(fn($item) => $item->packing_quantities)
                    ->groupBy(fn($value, $key) => $key)
                    ->map(fn($group) => $group->sum())
                    ->toArray();

                $shippedQuery = ShipmentData::where('product_combination_id', $pc->id)
                    ->where('po_number', $poNumber);

                if ($monthYear) {
                    $shippedQuery->whereRaw("FORMAT(date, 'yyyy-MM') = ?", [$monthYear]);
                }

                $shippedQuantities = $shippedQuery->get()
                    ->flatMap(fn($item) => $item->shipment_quantities)
                    ->groupBy(fn($value, $key) => $key)
                    ->map(fn($group) => $group->sum())
                    ->toArray();

                foreach ($allSizes as $size) {
                    $packed = $packedQuantities[$size->id] ?? 0;
                    $shipped = $shippedQuantities[$size->id] ?? 0;
                    $ready = max(0, $packed - $shipped);

                    $reportData[$key]['sizes'][$size->id] = $ready;
                    $reportData[$key]['total'] += $ready;

                    $cardData['total_ready'] += $ready;
                    $cardData['by_style'][$style] = ($cardData['by_style'][$style] ?? 0) + $ready;
                    $cardData['by_color'][$color] = ($cardData['by_color'][$color] ?? 0) + $ready;
                    $cardData['by_po_number'][$poNumber] = ($cardData['by_po_number'][$poNumber] ?? 0) + $ready;
                }

                if ($reportData[$key]['total'] == 0) {
                    unset($reportData[$key]);
                }
            }
        }

        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = array_unique(
            array_merge(
                FinishPackingData::distinct()->pluck('po_number')->filter()->values()->toArray(),
                ShipmentData::distinct()->pluck('po_number')->filter()->values()->toArray()
            )
        );
        sort($distinctPoNumbers);

        return view('backend.library.shipment_data.reports.ready_goods', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes,
            'allStyles' => $allStyles,
            'allColors' => $allColors,
            'distinctPoNumbers' => $distinctPoNumbers,
            'monthYears' => $monthYears,
            'cardData' => $cardData
        ]);
    }

    public function homeDashboardData(Request $request)
    {
        $currentMonth = Carbon::now()->format('Y-m');
        $currentYear = Carbon::now()->year;

        // Get unique po_status values
        $statuses = DB::table('order_data')->select('po_status')->distinct()->pluck('po_status')->toArray();
        $statusSums = array_map(function ($status) {
            return DB::raw("SUM(CASE WHEN po_status = '$status' THEN total_order_quantity ELSE 0 END) as {$status}_orders");
        }, $statuses);

        $selectStatements = array_merge([
            DB::raw('SUM(total_order_quantity) as total_orders'),
            DB::raw('COUNT(*) as order_count'),
        ], $statusSums);

        $ordersData = DB::table('order_data')
            ->select($selectStatements)
            ->first();

        $cuttingData = DB::table('cutting_data')
            ->select(
                DB::raw('SUM(total_cut_quantity) as total_cut'),
                DB::raw('AVG(total_cut_quantity) as avg_cut'),
                DB::raw('SUM(COALESCE(total_cut_waste_quantity, 0)) as total_cut_waste')
            )
            ->first();

        $printingData = DB::table('sublimation_print_sends as sps')
            ->leftJoin('sublimation_print_receives as spr', function ($join) {
                $join->on('sps.po_number', '=', 'spr.po_number')
                    ->on('sps.product_combination_id', '=', 'spr.product_combination_id');
            })
            ->select(
                DB::raw('SUM(sps.total_sublimation_print_send_quantity) as total_sublimation_sent'),
                DB::raw('SUM(COALESCE(spr.total_sublimation_print_receive_quantity, 0)) as total_sublimation_received'),
                DB::raw('SUM(COALESCE(spr.total_sublimation_print_receive_waste_quantity, 0)) as sublimation_waste')
            )
            ->first();

        $printData = DB::table('print_send_data as psd')
            ->leftJoin('print_receive_data as prd', function ($join) {
                $join->on('psd.po_number', '=', 'prd.po_number')
                    ->on('psd.product_combination_id', '=', 'prd.product_combination_id');
            })
            ->select(
                DB::raw('SUM(psd.total_send_quantity) as total_print_sent'),
                DB::raw('SUM(COALESCE(prd.total_receive_quantity, 0)) as total_print_received'),
                DB::raw('SUM(COALESCE(prd.total_receive_waste_quantity, 0)) as print_waste')
            )
            ->first();

        $sewingData = DB::table('line_input_data as lid')
            ->leftJoin('output_finishing_data as ofd', function ($join) {
                $join->on('lid.po_number', '=', 'ofd.po_number')
                    ->on('lid.product_combination_id', '=', 'ofd.product_combination_id');
            })
            ->select(
                DB::raw('SUM(lid.total_input_quantity) as total_input'),
                DB::raw('SUM(COALESCE(ofd.total_output_quantity, 0)) as total_output'),
                DB::raw('SUM(COALESCE(lid.total_input_waste_quantity, 0)) as input_waste'),
                DB::raw('SUM(COALESCE(ofd.total_output_waste_quantity, 0)) as output_waste')
            )
            ->first();

        $packingData = DB::table('finish_packing_data as fpd')
            ->leftJoin('shipment_data as sd', function ($join) {
                $join->on('fpd.po_number', '=', 'sd.po_number')
                    ->on('fpd.product_combination_id', '=', 'sd.product_combination_id');
            })
            ->select(
                DB::raw('SUM(fpd.total_packing_quantity) as total_packed'),
                DB::raw('SUM(COALESCE(sd.total_shipment_quantity, 0)) as total_shipped'),
                DB::raw('SUM(COALESCE(fpd.total_packing_waste_quantity, 0)) as packing_waste'),
                DB::raw('SUM(COALESCE(sd.total_shipment_waste_quantity, 0)) as shipment_waste')
            )
            ->first();

        $wasteData = [
            'cutting' => $cuttingData->total_cut_waste ?? 0,
            'printing' => ($printingData->sublimation_waste ?? 0) + ($printData->print_waste ?? 0),
            'sewing' => ($sewingData->input_waste ?? 0) + ($sewingData->output_waste ?? 0),
            'packing' => ($packingData->packing_waste ?? 0) + ($packingData->shipment_waste ?? 0)
        ];

        // Ready Goods Data
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $reportData = [];
        $cardData = [
            'total_ready' => 0,
            'by_style' => [],
            'by_color' => [],
            'by_po_number' => []
        ];

        // Get distinct month-year combinations
        $monthYears = FinishPackingData::selectRaw("FORMAT(date, 'yyyy-MM') as month_year")
            ->distinct()
            ->orderBy('month_year', 'desc')
            ->pluck('month_year')
            ->toArray();

        // Base query for product combinations
        $productCombinations = ProductCombination::whereHas('finishPackingData')
            ->with('style', 'color')
            ->get();

        foreach ($productCombinations as $pc) {
            $style = $pc->style->name;
            $color = $pc->color->name;

            $poNumbersQuery = FinishPackingData::where('product_combination_id', $pc->id)
                ->whereRaw("FORMAT(date, 'yyyy-MM') = ?", [$currentMonth]);

            $poNumbers = $poNumbersQuery->pluck('po_number')
                ->unique()
                ->toArray();

            foreach ($poNumbers as $poNumber) {
                $key = $poNumber . '-' . $style . '-' . $color;

                if (!isset($reportData[$key])) {
                    $reportData[$key] = [
                        'month_year' => $currentMonth,
                        'po_number' => $poNumber,
                        'style' => $style,
                        'color' => $color,
                        'sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
                        'total' => 0
                    ];
                }

                $packedQuery = FinishPackingData::where('product_combination_id', $pc->id)
                    ->where('po_number', $poNumber)
                    ->whereRaw("FORMAT(date, 'yyyy-MM') = ?", [$currentMonth]);

                $packedQuantities = $packedQuery->get()
                    ->flatMap(fn($item) => $item->packing_quantities)
                    ->groupBy(fn($value, $key) => $key)
                    ->map(fn($group) => $group->sum())
                    ->toArray();

                $shippedQuery = ShipmentData::where('product_combination_id', $pc->id)
                    ->where('po_number', $poNumber)
                    ->whereRaw("FORMAT(date, 'yyyy-MM') = ?", [$currentMonth]);

                $shippedQuantities = $shippedQuery->get()
                    ->flatMap(fn($item) => $item->shipment_quantities)
                    ->groupBy(fn($value, $key) => $key)
                    ->map(fn($group) => $group->sum())
                    ->toArray();

                foreach ($allSizes as $size) {
                    $packed = $packedQuantities[$size->id] ?? 0;
                    $shipped = $shippedQuantities[$size->id] ?? 0;
                    $ready = max(0, $packed - $shipped);

                    $reportData[$key]['sizes'][$size->id] = $ready;
                    $reportData[$key]['total'] += $ready;

                    $cardData['total_ready'] += $ready;
                    $cardData['by_style'][$style] = ($cardData['by_style'][$style] ?? 0) + $ready;
                    $cardData['by_color'][$color] = ($cardData['by_color'][$color] ?? 0) + $ready;
                    $cardData['by_po_number'][$poNumber] = ($cardData['by_po_number'][$poNumber] ?? 0) + $ready;
                }

                if ($reportData[$key]['total'] == 0) {
                    unset($reportData[$key]);
                }
            }
        }

        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = array_unique(
            array_merge(
                FinishPackingData::distinct()->pluck('po_number')->filter()->values()->toArray(),
                ShipmentData::distinct()->pluck('po_number')->filter()->values()->toArray()
            )
        );
        sort($distinctPoNumbers);

        $recentActivities = $this->getRecentActivities();
        $monthlyTrends = $this->getMonthlyTrends();
        $efficiencies = $this->calculateEfficiencies($ordersData, $cuttingData, $sewingData, $packingData);
        $monthlyData = $this->getMonthlyData($currentYear);

        return view('backend.home', compact(
            'ordersData',
            'cuttingData',
            'printingData',
            'printData',
            'sewingData',
            'packingData',
            'wasteData',
            'recentActivities',
            'monthlyTrends',
            'efficiencies',
            'statuses',
            'monthlyData',
            'reportData',
            'allSizes',
            'allStyles',
            'allColors',
            'distinctPoNumbers',
            'monthYears',
            'cardData'
        ));
    }
}
