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

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('productCombination.style', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            })->orWhereHas('productCombination.color', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }
        if ($request->filled('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        $shipmentData = $query->orderBy('date', 'desc')->paginate(10);
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        return view('backend.library.shipment_data.index', compact('shipmentData', 'allSizes'));
    }

    public function create()
    {
        // Get distinct PO numbers from FinishPackingData
        $distinctPoNumbers = FinishPackingData::distinct()->pluck('po_number')->filter()->values();
        $sizes = Size::where('is_active', 1)->get();

        return view('backend.library.shipment_data.create', compact('distinctPoNumbers', 'sizes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'po_number' => 'required|array',
            'po_number.*' => 'required|string',
            'rows' => 'required|array',
            'rows.*.product_combination_id' => 'required|exists:product_combinations,id',
            'rows.*.shipment_quantities.*' => 'nullable|integer|min:0',
            'rows.*.shipment_waste_quantities.*' => 'nullable|integer|min:0',
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

                // Process waste quantities
                foreach ($row['shipment_waste_quantities'] as $sizeId => $quantity) {
                    if ($quantity !== null && (int)$quantity > 0) {
                        $size = Size::find($sizeId);
                        if ($size) {
                            $wasteQuantities[$size->id] = (int)$quantity;
                            $totalWasteQuantity += (int)$quantity;
                        }
                    }
                }

                // Only create a record if there's at least one valid shipment or waste quantity
                if (!empty($shipmentQuantities) || !empty($wasteQuantities)) {
                    ShipmentData::create([
                        'date' => $request->date,
                        'product_combination_id' => $row['product_combination_id'],
                        'po_number' => implode(',', $request->po_number),
                        'shipment_quantities' => $shipmentQuantities,
                        'total_shipment_quantity' => $totalShipmentQuantity,
                        'shipment_waste_quantities' => $wasteQuantities,
                        'total_shipment_waste_quantity' => $totalWasteQuantity,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('shipment_data.index')
                ->with('success', 'Shipment data added successfully.');
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
                ->with('success', 'Shipment data updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(ShipmentData $shipmentDatum)
    {
        $shipmentDatum->delete();
        return redirect()->route('shipment_data.index')->with('success', 'Shipment data deleted successfully.');
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
        $allSizes = Size::where('is_active', 1)->get();

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
                $query->where('po_number', $poNumber); // Exact match instead of LIKE
            })
                ->with(['style', 'color', 'size', 'finishPackingData' => function ($query) use ($poNumber) {
                    $query->where('po_number', $poNumber);
                }])
                ->get();

            foreach ($productCombinations as $pc) {
                if (!$pc->style || !$pc->color) {
                    continue;
                }

                $combinationKey = $pc->id . '-' . $pc->style->name . '-' . $pc->color->name;

                if (in_array($combinationKey, $processedCombinations)) {
                    continue;
                }

                $processedCombinations[] = $combinationKey;

                // Calculate available quantities specifically for this PO
                $availableQuantities = $this->getAvailableShipmentQuantities($pc, $poNumber);

                $result[$poNumber][] = [
                    'combination_id' => $pc->id,
                    'style' => $pc->style->name,
                    'color' => $pc->color->name,
                    'available_quantities' => $availableQuantities,
                    'size_ids' => array_keys($availableQuantities)
                ];
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
    public function totalShipmentReport(Request $request)
    {
        $query = ShipmentData::with('productCombination.style', 'productCombination.color');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
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

        return view('backend.library.shipment_data.reports.total_shipment', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes
        ]);
    }

    public function readyGoodsReport(Request $request)
    {
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $reportData = [];

        // Get all product combinations that have finish packing data
        $productCombinations = ProductCombination::whereHas('finishPackingData')
            ->with('style', 'color')
            ->get();

        foreach ($productCombinations as $pc) {
            $style = $pc->style->name;
            $color = $pc->color->name;

            // Get all PO numbers for this product combination
            $poNumbers = FinishPackingData::where('product_combination_id', $pc->id)
                ->pluck('po_number')
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

                // Get total packed quantities for this PO number
                $packedQuantities = FinishPackingData::where('product_combination_id', $pc->id)
                    ->where('po_number', 'like', '%' . $poNumber . '%')
                    ->get()
                    ->flatMap(fn($item) => $item->packing_quantities)
                    ->groupBy(fn($value, $key) => $key)
                    ->map(fn($group) => $group->sum())
                    ->toArray();

                // Get total shipped quantities for this PO number
                $shippedQuantities = ShipmentData::where('product_combination_id', $pc->id)
                    ->where('po_number', 'like', '%' . $poNumber . '%')
                    ->get()
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
            }
        }

        return view('backend.library.shipment_data.reports.ready_goods', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes
        ]);
    }

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

    //old_data_store

    public function old_data_store(Request $request)
    {
        Log::info('Old Data Store Request Data:', $request->all());

        $request->validate([
            'date' => 'required|date',
            'po_number' => 'required|array',
            'po_number.*' => 'string',
            'Stage' => 'required|string|in:CuttingData,SublimationPrintSend,SublimationPrintReceive,PrintSendData,PrintReceiveData,LineInputData,OutputFinishingData,FinishPackingData,ShipmentData',
            'old_order' => 'required|in:yes,no',
            'rows' => 'required|array',
            'rows.*.po_number' => 'required|string|in:' . implode(',', $request->input('po_number', [])),
            'rows.*.product_combination_id' => 'required|integer|exists:product_combinations,id',
            'rows.*.Old_data_qty' => 'required|array',
            'rows.*.Old_data_qty.*' => 'nullable|integer|min:0',
            'rows.*.Old_data_waste' => 'required|array',
            'rows.*.Old_data_waste.*' => 'nullable|integer|min:0',
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
            'ShipmentData'
        ];

        $currentStage = $request->Stage;
        $stagesToProcess = [];

        // Determine all stages from the beginning up to and including the current stage
        foreach ($allStages as $stage) {
            $stagesToProcess[] = $stage;
            if ($stage === $currentStage) {
                break;
            }
        }

        try {
            DB::beginTransaction();

            $poNumbers = implode(',', $request->po_number);

            foreach ($request->rows as $rowIndex => $row) {
                $productCombinationId = $row['product_combination_id'];
                $oldDataQuantities = array_filter($row['Old_data_qty'] ?? [], fn($value) => $value !== null && $value !== '');
                $oldDataWasteQuantities = array_filter($row['Old_data_waste'] ?? [], fn($value) => $value !== null && $value !== '');

                // Fetch product combination to check flags
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

                    // Use the Old_data_qty and Old_data_waste for all stages being populated
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

                    // Only create a record if there's at least one valid quantity or waste quantity
                    if (empty($quantities) && empty($wasteQuantities)) {
                        Log::info("Skipping record creation for stage {$stage} for row {$rowIndex}: No quantities or waste provided for this stage.");
                        continue;
                    }

                    $commonData = [
                        'date' => $request->date,
                        'product_combination_id' => $productCombinationId,
                        'po_number' => $poNumbers,
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
                        case 'ShipmentData':
                            ShipmentData::create(array_merge($commonData, [
                                'shipment_quantities' => $quantities,
                                'total_shipment_quantity' => $totalQuantity,
                                'shipment_waste_quantities' => $wasteQuantities,
                                'total_shipment_waste_quantity' => $totalWasteQuantity,
                            ]));
                            break;
                        default:
                            // Should not happen if validation is correct
                            break;
                    }
                    Log::info("Created data for stage {$stage} for PO {$poNumbers}, Combination ID: {$productCombinationId}");
                }
            }

            DB::commit();

            return redirect()->route('old_data_index')->with('success', 'Old order data created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing old order data: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()
                ->with('error', 'Error occurred while creating old order data: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function old_data_index()
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

        foreach ($oldOrderModels as $stageName => $modelClass) {
            $data = $modelClass::where('old_order', 'yes')
                ->with('productCombination.style', 'productCombination.color', 'productCombination.size')
                ->get();

            foreach ($data as $item) {
                // Prepare common attributes
                $record = [
                    'id' => $item->id,
                    'stage' => $stageName,
                    'date' => $item->date,
                    'po_number' => $item->po_number, // This might be a comma-separated string
                    'old_order' => $item->old_order,
                    'product_combination_id' => $item->product_combination_id,
                    'style_name' => $item->productCombination->style->name ?? 'N/A',
                    'color_name' => $item->productCombination->color->name ?? 'N/A',
                    'size_name' => $item->productCombination->size->name ?? 'N/A', // This might not be relevant if quantities are by size_id
                    'quantities' => [], // Will store specific quantity data for the stage
                    'waste_quantities' => [], // Will store specific waste data for the stage
                    'total_quantity' => 0,
                    'total_waste_quantity' => 0,
                ];

                // Dynamically get the quantity and waste fields based on the stage name
                $qtyField = strtolower(str_replace('Data', '', str_replace('SublimationPrint', 'sublimation_print_', str_replace('Print', '', $stageName)))) . '_quantities';
                $totalQtyField = 'total_' . $qtyField;
                $wasteQtyField = strtolower(str_replace('Data', '', str_replace('SublimationPrint', 'sublimation_print_', str_replace('Print', '', $stageName)))) . '_waste_quantities';
                $totalWasteQtyField = 'total_' . $wasteQtyField;


                // Handle specific field names for each model
                if ($stageName === 'CuttingData') {
                    $record['quantities'] = $item->cut_quantities ?? [];
                    $record['total_quantity'] = $item->total_cut_quantity ?? 0;
                    $record['waste_quantities'] = []; // Cutting data typically doesn't have waste directly
                    $record['total_waste_quantity'] = 0;
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

        // You might want to sort $allOldData for better display, e.g., by date or PO number
        $allOldData = $allOldData->sortBy('date')->sortBy('po_number');
        $allSizes = Size::all();

        return view('backend.library.old_data.index', compact('allOldData', 'allSizes'));
    }
}
