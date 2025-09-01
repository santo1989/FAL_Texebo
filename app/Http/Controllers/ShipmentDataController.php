<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\CuttingData;
use App\Models\LineInputData;
use App\Models\PrintReceiveData;
use App\Models\PrintSendData;
use App\Models\ShipmentData;
use App\Models\ProductCombination;
use App\Models\Size;
use App\Models\FinishPackingData;
use App\Models\Style;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();

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
                $query->where('po_number', 'like', '%' . $poNumber . '%');
            })
                ->with('style', 'color', 'size')
                ->get();

            foreach ($productCombinations as $pc) {
                // Skip if product combination doesn't have style or color
                if (!$pc->style || !$pc->color) {
                    continue;
                }

                // Create a unique key for this combination
                $combinationKey = $pc->id . '-' . $pc->style->name . '-' . $pc->color->name;

                // Skip if we've already processed this combination
                if (in_array($combinationKey, $processedCombinations)) {
                    continue;
                }

                // Mark this combination as processed
                $processedCombinations[] = $combinationKey;

                // Pass the PO numbers to getMaxShipmentQuantities
                $availableQuantities = $this->getMaxShipmentQuantities($pc, $poNumbers);

                $result[$poNumber][] = [
                    'combination_id' => $pc->id,
                    'style' => $pc->style->name,
                    'color' => $pc->color->name,
                    'available_quantities' => $availableQuantities,
                    'size_ids' => $pc->sizes->pluck('id')->toArray()
                ];
            }
        }

        return response()->json($result);
    }


    // public function index(Request $request)
    // {
    //     $query = ShipmentData::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

    //     if ($request->filled('search')) {
    //         $search = $request->input('search');
    //         $query->whereHas('productCombination.style', function ($q) use ($search) {
    //             $q->where('name', 'like', '%' . $search . '%');
    //         })->orWhereHas('productCombination.color', function ($q) use ($search) {
    //             $q->where('name', 'like', '%' . $search . '%');
    //         });
    //     }
    //     if ($request->filled('date')) {
    //         $query->whereDate('date', $request->input('date'));
    //     }

    //     $shipmentData = $query->orderBy('date', 'desc')->paginate(10);
    //     $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();

    //     return view('backend.library.shipment_data.index', compact('shipmentData', 'allSizes'));
    // }

    // public function create()
    // {
    //     $productCombinations = ProductCombination::whereHas('finishPackingData')
    //         ->with('buyer', 'style', 'color')
    //         ->get();

    //     $sizes = Size::where('is_active', 1)->get();

    //     return view('backend.library.shipment_data.create', compact('productCombinations', 'sizes'));
    // }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'date' => 'required|date',
    //         'product_combination_id' => 'required|exists:product_combinations,id',
    //         'quantities.*' => 'nullable|integer|min:0',
    //     ]);

    //     $productCombination = ProductCombination::findOrFail($request->product_combination_id);
    //     $availableQuantities = $this->getAvailableQuantitiesArray($productCombination);

    //     $shipmentQuantities = [];
    //     $totalShipmentQuantity = 0;
    //     $errors = [];

    //     foreach ($request->input('quantities', []) as $sizeId => $quantity) {
    //         $size = Size::where('is_active', 1)->find($sizeId);
    //         if ($size && $quantity > 0) {
    //             $sizeName = strtolower($size->name);
    //             $available = $availableQuantities[$sizeName] ?? 0;

    //             if ($quantity > $available) {
    //                 $errors["quantities.$sizeId"] = "Quantity for {$size->name} exceeds available limit ($available)";
    //                 continue;
    //             }

    //             $shipmentQuantities[$size->name] = (int)$quantity;
    //             $totalShipmentQuantity += (int)$quantity;
    //         }
    //     }

    //     if (!empty($errors)) {
    //         return redirect()->back()->withErrors($errors)->withInput();
    //     }

    //     ShipmentData::create([
    //         'date' => $request->date,
    //         'product_combination_id' => $request->product_combination_id,
    //         'shipment_quantities' => $shipmentQuantities,
    //         'total_shipment_quantity' => $totalShipmentQuantity,
    //     ]);

    //     return redirect()->route('shipment_data.index')->with('success', 'Shipment data added successfully.');
    // }

    // // Replace the existing getAvailableQuantities method with these two methods:

    // protected function getAvailableQuantitiesArray(ProductCombination $productCombination)
    // {
    //     $availableQuantities = [];
    //     $allSizes = Size::where('is_active', 1)->get();

    //     $packedQuantities = FinishPackingData::where('product_combination_id', $productCombination->id)
    //         ->get()
    //         ->flatMap(fn($item) => $item->packing_quantities)
    //         ->groupBy(fn($value, $key) => strtolower($key))
    //         ->map(fn($group) => $group->sum())
    //         ->toArray();

    //     $shippedQuantities = ShipmentData::where('product_combination_id', $productCombination->id)
    //         ->get()
    //         ->flatMap(fn($item) => $item->shipment_quantities)
    //         ->groupBy(fn($value, $key) => strtolower($key))
    //         ->map(fn($group) => $group->sum())
    //         ->toArray();

    //     foreach ($allSizes as $size) {
    //         $sizeName = strtolower($size->name);
    //         $packed = $packedQuantities[$sizeName] ?? 0;
    //         $shipped = $shippedQuantities[$sizeName] ?? 0;
    //         $availableQuantities[$sizeName] = max(0, $packed - $shipped);
    //     }

    //     return $availableQuantities;
    // }

    // public function getAvailableQuantities(ProductCombination $productCombination)
    // {
    //     $availableQuantities = $this->getAvailableQuantitiesArray($productCombination);
    //     $allSizes = Size::where('is_active', 1)->get();

    //     return response()->json([
    //         'availableQuantities' => $availableQuantities,
    //         'sizes' => $allSizes->map(fn($size) => [
    //             'id' => $size->id,
    //             'name' => $size->name
    //         ])->toArray()
    //     ]);
    // }
    // public function show(ShipmentData $shipmentDatum)
    // {
    //     return view('backend.library.shipment_data.show', compact('shipmentDatum'));
    // }

    // public function edit(ShipmentData $shipmentDatum)
    // {
    //     $shipmentDatum->load('productCombination.style', 'productCombination.color');
    //     $sizes = Size::where('is_active', 1)->get();
    //     // In edit method
    //     // In edit() method:
    //     $availableQuantities = $this->getAvailableQuantitiesArray($shipmentDatum->productCombination);

    //     // Add back current shipment quantities to available
    //     foreach ($shipmentDatum->shipment_quantities as $size => $quantity) {
    //         $sizeName = strtolower($size);
    //         if (isset($availableQuantities[$sizeName])) {
    //             $availableQuantities[$sizeName] += $quantity;
    //         }
    //     }

    //     $sizeData = $sizes->map(function ($size) use ($shipmentDatum, $availableQuantities) {
    //         $sizeName = strtolower($size->name);
    //         return [
    //             'id' => $size->id,
    //             'name' => $size->name,
    //             'available' => $availableQuantities[$sizeName] ?? 0,
    //             'current_quantity' => $shipmentDatum->shipment_quantities[$size->name] ?? 0
    //         ];
    //     });

    //     return view('backend.library.shipment_data.edit', [
    //         'shipmentDatum' => $shipmentDatum,
    //         'sizeData' => $sizeData
    //     ]);
    // }

    // public function update(Request $request, ShipmentData $shipmentDatum)
    // {
    //     $request->validate([
    //         'date' => 'required|date',
    //         'quantities.*' => 'nullable|integer|min:0',
    //     ]);

    //     $productCombination = $shipmentDatum->productCombination;
    //     $availableQuantities = $this->getAvailableQuantitiesArray($productCombination);

    //     // Add back current shipment quantities to available
    //     foreach ($shipmentDatum->shipment_quantities as $size => $quantity) {
    //         $sizeName = strtolower($size);
    //         if (isset($availableQuantities[$sizeName])) {
    //             $availableQuantities[$sizeName] += $quantity;
    //         }
    //     }

    //     $shipmentQuantities = [];
    //     $totalShipmentQuantity = 0;
    //     $errors = [];

    //     foreach ($request->input('quantities', []) as $sizeId => $quantity) {
    //         $size = Size::where('is_active', 1)->find($sizeId);
    //         if ($size && $quantity > 0) {
    //             $sizeName = strtolower($size->name);
    //             $available = $availableQuantities[$sizeName] ?? 0;

    //             if ($quantity > $available) {
    //                 $errors["quantities.$sizeId"] = "Quantity for {$size->name} exceeds available limit ($available)";
    //                 continue;
    //             }

    //             $shipmentQuantities[$size->name] = (int)$quantity;
    //             $totalShipmentQuantity += (int)$quantity;
    //         }
    //     }

    //     if (!empty($errors)) {
    //         return redirect()->back()->withErrors($errors)->withInput();
    //     }

    //     $shipmentDatum->update([
    //         'date' => $request->date,
    //         'shipment_quantities' => $shipmentQuantities,
    //         'total_shipment_quantity' => $totalShipmentQuantity,
    //     ]);

    //     return redirect()->route('shipment_data.index')->with('success', 'Shipment data updated successfully.');
    // }

    // public function destroy(ShipmentData $shipmentDatum)
    // {
    //     $shipmentDatum->delete();
    //     return redirect()->route('shipment_data.index')->with('success', 'Shipment data deleted successfully.');
    // }



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
            $key = $style . '-' . $color;

            if (!isset($reportData[$key])) {
                $reportData[$key] = [
                    'style' => $style,
                    'color' => $color,
                    'sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
                    'total' => 0
                ];
            }

            foreach ($data->shipment_quantities as $size => $qty) {
                $normalized = strtolower($size);
                if (array_key_exists($normalized, $reportData[$key]['sizes'])) {
                    $reportData[$key]['sizes'][$normalized] += $qty;
                }
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

        $productCombinations = ProductCombination::whereHas('finishPackingData')
            ->with('style', 'color')
            ->get();

        foreach ($productCombinations as $pc) {
            $style = $pc->style->name;
            $color = $pc->color->name;
            $key = $style . '-' . $color;

            if (!isset($reportData[$key])) {
                $reportData[$key] = [
                    'style' => $style,
                    'color' => $color,
                    'sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
                    'total' => 0
                ];
            }

            // Get total packed quantities
            $packedQuantities = FinishPackingData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->packing_quantities)
                ->groupBy(fn($value, $key) => strtolower($key))
                ->map(fn($group) => $group->sum())
                ->toArray();

            // Get total shipped quantities
            $shippedQuantities = ShipmentData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->shipment_quantities)
                ->groupBy(fn($value, $key) => strtolower($key))
                ->map(fn($group) => $group->sum())
                ->toArray();

            foreach ($allSizes as $size) {
                $sizeName = strtolower($size->name);
                $packed = $packedQuantities[$sizeName] ?? 0;
                $shipped = $shippedQuantities[$sizeName] ?? 0;
                $ready = max(0, $packed - $shipped);

                $reportData[$key]['sizes'][$sizeName] = $ready;
                $reportData[$key]['total'] += $ready;
            }
        }

        return view('backend.library.shipment_data.reports.ready_goods', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes
        ]);
    }



    public function finalbalanceReport(Request $request)
    {
        // Get filter parameters
        $styleId = $request->input('style_id');
        $colorId = $request->input('color_id');
        $start_date = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $end_date = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        // Get styles and colors for filters
        $styles = Style::get();
        $colors = Color::get();

        $reportData = [];

        // Query with filters and pagination
        $productCombinations = ProductCombination::with('style', 'color', 'size')
            ->when($styleId, function ($query) use ($styleId) {
                $query->where('style_id', $styleId);
            })
            ->when($colorId, function ($query) use ($colorId) {
                $query->where('color_id', $colorId);
            })
            ->paginate(10);

        // Date range filter closure
        $dateFilter = function ($query) use ($start_date, $end_date) {
            if ($start_date && $end_date) {
                $query->whereBetween('date', [$start_date, $end_date]);
            } elseif ($start_date) {
                $query->where('date', '>=', $start_date);
            } elseif ($end_date) {
                $query->where('date', '<=', $end_date);
            }
        };

        foreach ($productCombinations as $pc) {
            $style = $pc->style->name;
            $color = $pc->color->name;

            // Fetch quantities with date filtering
            $cutQuantities = CuttingData::where('product_combination_id', $pc->id)
                ->when($start_date || $end_date, $dateFilter)
                ->get()
                ->flatMap(fn($item) => $item->cut_quantities)
                ->groupBy(fn($value, $key) => strtolower($key))
                ->map(fn($group) => $group->sum())
                ->toArray();

            $printSendQuantities = PrintSendData::where('product_combination_id', $pc->id)
                ->when($start_date || $end_date, $dateFilter)
                ->get()
                ->flatMap(fn($item) => $item->send_quantities)
                ->groupBy(fn($value, $key) => strtolower($key))
                ->map(fn($group) => $group->sum())
                ->toArray();

            $printReceiveQuantities = PrintReceiveData::where('product_combination_id', $pc->id)
                ->when($start_date || $end_date, $dateFilter)
                ->get()
                ->flatMap(fn($item) => $item->receive_quantities)
                ->groupBy(fn($value, $key) => strtolower($key))
                ->map(fn($group) => $group->sum())
                ->toArray();

            $lineInputQuantities = LineInputData::where('product_combination_id', $pc->id)
                ->when($start_date || $end_date, $dateFilter)
                ->get()
                ->flatMap(fn($item) => $item->input_quantities)
                ->groupBy(fn($value, $key) => strtolower($key))
                ->map(fn($group) => $group->sum())
                ->toArray();

            $finishPackingQuantities = FinishPackingData::where('product_combination_id', $pc->id)
                ->when($start_date || $end_date, $dateFilter)
                ->get()
                ->flatMap(fn($item) => $item->packing_quantities)
                ->groupBy(fn($value, $key) => strtolower($key))
                ->map(fn($group) => $group->sum())
                ->toArray();

            $shipmentQuantities = ShipmentData::where('product_combination_id', $pc->id)
                ->when($start_date || $end_date, $dateFilter)
                ->get()
                ->flatMap(fn($item) => $item->shipment_quantities)
                ->groupBy(fn($value, $key) => strtolower($key))
                ->map(fn($group) => $group->sum())
                ->toArray();

            // Create rows for each size
            foreach ($pc->sizes as $size) {
                $sizeName = strtolower($size->name);

                $cut = $cutQuantities[$sizeName] ?? 0;
                $printSent = $printSendQuantities[$sizeName] ?? 0;
                $printReceived = $printReceiveQuantities[$sizeName] ?? 0;
                $lineInput = $lineInputQuantities[$sizeName] ?? 0;
                $packed = $finishPackingQuantities[$sizeName] ?? 0;
                $shipped = $shipmentQuantities[$sizeName] ?? 0;

                // Calculate balances
                $printSendBalance = $cut - $printSent;
                $printReceiveBalance = $printSent - $printReceived;
                $sewingInputBalance = $printReceived - $lineInput;
                $packingBalance = $lineInput - $packed;
                $readyGoods = $packed - $shipped;

                $reportData[] = [
                    'style' => $style,
                    'color' => $color,
                    'size' => $size->name,
                    'cutting' => $cut,
                    'print_send' => $printSent,
                    'print_send_balance' => $printSendBalance,
                    'print_receive' => $printReceived,
                    'print_receive_balance' => $printReceiveBalance,
                    'sewing_input' => $lineInput,
                    'sewing_input_balance' => $sewingInputBalance,
                    'packing' => $packed,
                    'packing_balance' => $packingBalance,
                    'shipment' => $shipped,
                    'ready_goods' => $readyGoods,
                ];
            }
        }

        // Group data for rowspan display
        $groupedData = [];
        foreach ($reportData as $row) {
            $key = $row['style'] . '_' . $row['color'];
            if (!isset($groupedData[$key])) {
                $groupedData[$key] = [
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
        ]);
    }
}