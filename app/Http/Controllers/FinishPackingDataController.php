<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\FinishPackingData;
use App\Models\OrderData;
use App\Models\OutputFinishingData;
use App\Models\ProductCombination;
use App\Models\Size;
use App\Models\CuttingData; // Import CuttingData
use App\Models\PrintSendData; // Import PrintSendData
use App\Models\PrintReceiveData; // Import PrintReceiveData
use App\Models\Style;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinishPackingDataController extends Controller
{

    public function index(Request $request)
    {
        $query = FinishPackingData::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

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

        $finishPackingData = $query->orderBy('date', 'desc')->paginate(10);
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Get filter options
        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = FinishPackingData::distinct()->pluck('po_number')->filter()->values();

        return view('backend.library.finish_packing_data.index', compact(
            'finishPackingData',
            'allSizes',
            'allStyles',
            'allColors',
            'distinctPoNumbers'
        ));
    }

    public function create()
    {
        // Get distinct PO numbers based on product combination type
        $distinctPoNumbers = $this->getAvailablePoNumbers();
        $sizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        return view('backend.library.finish_packing_data.create', compact('distinctPoNumbers', 'sizes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'po_number' => 'required|array',
            'po_number.*' => 'required|string',
            'rows' => 'required|array',
            'rows.*.product_combination_id' => 'required|exists:product_combinations,id',
            'rows.*.packing_quantities.*' => 'nullable|integer|min:0',
            'rows.*.packing_waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->rows as $row) {
                $packingQuantities = [];
                $wasteQuantities = [];
                $totalPackingQuantity = 0;
                $totalWasteQuantity = 0;

                // Process packing quantities
                foreach ($row['packing_quantities'] as $sizeId => $quantity) {
                    if ($quantity !== null && (int)$quantity > 0) {
                        $size = Size::find($sizeId);
                        if ($size) {
                            $packingQuantities[$size->id] = (int)$quantity;
                            $totalPackingQuantity += (int)$quantity;
                        }
                    }
                }

                // Process waste quantities
                foreach ($row['packing_waste_quantities'] as $sizeId => $quantity) {
                    if ($quantity !== null && (int)$quantity > 0) {
                        $size = Size::find($sizeId);
                        if ($size) {
                            $wasteQuantities[$size->id] = (int)$quantity;
                            $totalWasteQuantity += (int)$quantity;
                        }
                    }
                }

                // Only create a record if there's at least one valid packing or waste quantity
                if (!empty($packingQuantities) || !empty($wasteQuantities)) {
                    FinishPackingData::create([
                        'date' => $request->date,
                        'product_combination_id' => $row['product_combination_id'],
                        'po_number' => $row['po_number'],
                        'packing_quantities' => $packingQuantities,
                        'total_packing_quantity' => $totalPackingQuantity,
                        'packing_waste_quantities' => $wasteQuantities,
                        'total_packing_waste_quantity' => $totalWasteQuantity,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('finish_packing_data.index')
                ->withMessage('Finish packing data added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(FinishPackingData $finishPackingDatum)
    {
        $finishPackingDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Only valid sizes
        $validSizes = $allSizes->filter(function ($size) use ($finishPackingDatum) {
            return isset($finishPackingDatum->packing_quantities[$size->id]) ||
                isset($finishPackingDatum->packing_waste_quantities[$size->id]);
        });

        $allSizes = $validSizes->values();

        return view('backend.library.finish_packing_data.show', compact('finishPackingDatum', 'allSizes'));
    }

    public function edit(FinishPackingData $finishPackingDatum)
    {
        $finishPackingDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Get the PO numbers from the record
        $poNumbers = explode(',', $finishPackingDatum->po_number);

        // Get total output quantities from OutputFinishingData for this product combination and PO numbers
        $totalOutputQuantities = [];
        OutputFinishingData::where('product_combination_id', $finishPackingDatum->product_combination_id)
            ->whereIn('po_number', $poNumbers)
            ->get()
            ->each(function ($item) use (&$totalOutputQuantities) {
                foreach ($item->output_quantities ?? [] as $sizeId => $quantity) {
                    $totalOutputQuantities[$sizeId] = ($totalOutputQuantities[$sizeId] ?? 0) + $quantity;
                }
            });

        // Get total packing quantities from other FinishPackingData entries (excluding current one)
        $totalPackingQuantities = [];
        $totalPackingWasteQuantities = [];
        FinishPackingData::where('product_combination_id', $finishPackingDatum->product_combination_id)
            ->whereIn('po_number', $poNumbers)
            ->where('id', '!=', $finishPackingDatum->id)
            ->get()
            ->each(function ($item) use (&$totalPackingQuantities, &$totalPackingWasteQuantities) {
                foreach ($item->packing_quantities ?? [] as $sizeId => $quantity) {
                    $totalPackingQuantities[$sizeId] = ($totalPackingQuantities[$sizeId] ?? 0) + $quantity;
                }
                foreach ($item->packing_waste_quantities ?? [] as $sizeId => $quantity) {
                    $totalPackingWasteQuantities[$sizeId] = ($totalPackingWasteQuantities[$sizeId] ?? 0) + $quantity;
                }
            });

        // Get order quantities
        $orderQuantities = [];
        foreach ($poNumbers as $poNumber) {
            $orderData = OrderData::where('product_combination_id', $finishPackingDatum->product_combination_id)
                ->where('po_number', $poNumber)
                ->first();

            if ($orderData && $orderData->order_quantities) {
                foreach ($orderData->order_quantities as $sizeId => $qty) {
                    $orderQuantities[$sizeId] = ($orderQuantities[$sizeId] ?? 0) + $qty;
                }
            }
        }

        // Prepare size data
        $sizeData = [];
        foreach ($allSizes as $size) {
            $currentPackingQty = $finishPackingDatum->packing_quantities[$size->id] ?? 0;
            $currentWasteQty = $finishPackingDatum->packing_waste_quantities[$size->id] ?? 0;

            $totalOutputQty = $totalOutputQuantities[$size->id] ?? 0;
            $otherPackingQty = $totalPackingQuantities[$size->id] ?? 0;
            $otherWasteQty = $totalPackingWasteQuantities[$size->id] ?? 0;

            // Calculate available quantity: total output - (other packing + other waste + current packing + current waste)
            $availableQty = $totalOutputQty - ($otherPackingQty + $otherWasteQty + $currentPackingQty + $currentWasteQty);

            $sizeData[] = [
                'id' => $size->id,
                'name' => $size->name,
                'packing_quantity' => $currentPackingQty,
                'waste_quantity' => $currentWasteQty,
                'total_output_quantity' => $totalOutputQty,
                'available_quantity' => $availableQty,
                'order_quantity' => $orderQuantities[$size->id] ?? 0,
            ];
        }

        return view('backend.library.finish_packing_data.edit', compact('finishPackingDatum', 'sizeData'));
    }
    public function update(Request $request, FinishPackingData $finishPackingDatum)
    {
        $request->validate([
            'date' => 'required|date',
            'packing_quantities.*' => 'nullable|integer|min:0',
            'packing_waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        try {
            $packingQuantities = [];
            $wasteQuantities = [];
            $totalPackingQuantity = 0;
            $totalWasteQuantity = 0;

            // Process packing quantities
            foreach ($request->packing_quantities as $sizeId => $quantity) {
                if ($quantity !== null && (int)$quantity > 0) {
                    $packingQuantities[$sizeId] = (int)$quantity;
                    $totalPackingQuantity += (int)$quantity;
                }
            }

            // Process waste quantities
            foreach ($request->packing_waste_quantities as $sizeId => $quantity) {
                if ($quantity !== null && (int)$quantity > 0) {
                    $wasteQuantities[$sizeId] = (int)$quantity;
                    $totalWasteQuantity += (int)$quantity;
                }
            }

            $finishPackingDatum->update([
                'date' => $request->date,
                'packing_quantities' => $packingQuantities,
                'total_packing_quantity' => $totalPackingQuantity,
                'packing_waste_quantities' => $wasteQuantities,
                'total_packing_waste_quantity' => $totalWasteQuantity,
            ]);

            return redirect()->route('finish_packing_data.index')
                ->withMessage('Finish packing data updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(FinishPackingData $finishPackingDatum)
    {
        $finishPackingDatum->delete();
        return redirect()->route('finish_packing_data.index')->withMessage('Finish packing data deleted successfully.');
    }

    // Reports


    private function getAvailablePoNumbers()
    {
        $poNumbers = [];

        // Get PO numbers from OutputFinishingData
        $outputFinishingPoNumbers = OutputFinishingData::distinct()->pluck('po_number')->filter()->values();
        $poNumbers = array_merge($poNumbers, $outputFinishingPoNumbers->toArray());

        return array_unique($poNumbers);
    }

    public function getAvailablePackingQuantities(ProductCombination $pc, $poNumber)
    {
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $availableQuantities = [];

        // Get total output quantities for this specific PO number
        $outputQuantities = [];
        OutputFinishingData::where('product_combination_id', $pc->id)
            ->where('po_number', $poNumber) // Exact match instead of LIKE
            ->get()
            ->each(function ($item) use (&$outputQuantities) {
                foreach ($item->output_quantities as $sizeId => $quantity) {
                    $outputQuantities[$sizeId] = ($outputQuantities[$sizeId] ?? 0) + $quantity;
                }
            });


        // Get total packed quantities for this specific PO number
        $packedQuantities = [];
        FinishPackingData::where('product_combination_id', $pc->id)
            ->where('po_number', $poNumber) // Exact match instead of LIKE
            ->get()
            ->each(function ($item) use (&$packedQuantities) {
                foreach ($item->packing_quantities as $sizeId => $quantity) {
                    $packedQuantities[$sizeId] = ($packedQuantities[$sizeId] ?? 0) + $quantity;
                }

                // Include waste quantities in the subtraction
                foreach ($item->packing_waste_quantities as $sizeId => $wasteQty) {
                    $packedQuantities[$sizeId] = ($packedQuantities[$sizeId] ?? 0) + $wasteQty;
                }
            });

        // Calculate available quantities
        foreach ($allSizes as $size) {
            $output = $outputQuantities[$size->id] ?? 0;
            $packed = $packedQuantities[$size->id] ?? 0;
            $availableQuantities[$size->id] = max(0, $output - $packed);
        }

        return $availableQuantities;
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
    //         // Get data for the selected PO number from OutputFinishingData
    //         $productCombinations = ProductCombination::whereHas('outputFinishingData', function ($query) use ($poNumber) {
    //             $query->where('po_number', $poNumber); // Exact match instead of LIKE
    //         })
    //             ->with(['style', 'color', 'size', 'outputFinishingData' => function ($query) use ($poNumber) {
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

    //             // Get available quantities for this specific PO
    //             $availableQuantities = $this->getAvailablePackingQuantities($pc, $poNumber);

    //             $result[$poNumber][] = [
    //                 'combination_id' => $pc->id,
    //                 'style' => $pc->style->name,
    //                 'color' => $pc->color->name,
    //                 'available_quantities' => $availableQuantities,
    //                 'size_ids' => array_keys($availableQuantities) // Only include sizes with available quantities
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
            // Get data for the selected PO number from OutputFinishingData
            $productCombinations = ProductCombination::whereHas('outputFinishingData', function ($query) use ($poNumber) {
                $query->where('po_number', $poNumber);
            })
                ->with(['style', 'color', 'size', 'outputFinishingData' => function ($query) use ($poNumber) {
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

                // Get available quantities for this specific PO
                $availableQuantities = $this->getAvailablePackingQuantities($pc, $poNumber);

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
    public function getMaxPackingQuantities(ProductCombination $pc)
    {
        $maxQuantities = [];
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Get total output quantities from OutputFinishingData
        $outputQuantities = OutputFinishingData::where('product_combination_id', $pc->id)
            ->get()
            ->flatMap(function ($item) {
                return $item->output_quantities;
            })
            ->groupBy(function ($value, $key) {
                return $key; // Use size ID as key
            })
            ->map(function ($group) {
                return $group->sum();
            })
            ->toArray();

        // Get total packed quantities
        $packedQuantities = FinishPackingData::where('product_combination_id', $pc->id)
            ->get()
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

        foreach ($allSizes as $size) {
            $output = $outputQuantities[$size->id] ?? 0;
            $packed = $packedQuantities[$size->id] ?? 0;
            $maxQuantities[$size->id] = max(0, $output - $packed);
        }

        return $maxQuantities;
    }

    // public function totalPackingReport(Request $request)
    // {
    //     $query = FinishPackingData::with('productCombination.style', 'productCombination.color');

    //     // Get filter parameters
    //     $styleIds = $request->input('style_id', []);
    //     $colorIds = $request->input('color_id', []);
    //     $poNumbers = $request->input('po_number', []);
    //     $startDate = $request->input('start_date');
    //     $endDate = $request->input('end_date');
    //     $search = $request->input('search');

    //     // Style filter
    //     if (!empty($styleIds)) {
    //         $query->whereHas('productCombination', function ($q) use ($styleIds) {
    //             $q->whereIn('style_id', $styleIds);
    //         });
    //     }

    //     // Color filter
    //     if (!empty($colorIds)) {
    //         $query->whereHas('productCombination', function ($q) use ($colorIds) {
    //             $q->whereIn('color_id', $colorIds);
    //         });
    //     }

    //     // PO Number filter
    //     if (!empty($poNumbers)) {
    //         $query->whereIn('po_number', $poNumbers);
    //     }

    //     // Date filter
    //     if ($startDate && $endDate) {
    //         $query->whereBetween('date', [$startDate, $endDate]);
    //     }

    //     // Search filter
    //     if ($search) {
    //         $query->where(function ($q) use ($search) {
    //             $q->where('po_number', 'like', '%' . $search . '%')
    //                 ->orWhereHas('productCombination.style', function ($q2) use ($search) {
    //                     $q2->where('name', 'like', '%' . $search . '%');
    //                 })
    //                 ->orWhereHas('productCombination.color', function ($q2) use ($search) {
    //                     $q2->where('name', 'like', '%' . $search . '%');
    //                 });
    //         });
    //     }

    //     $finishPackingData = $query->get();
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $reportData = [];

    //     foreach ($finishPackingData as $data) {
    //         $style = $data->productCombination->style->name;
    //         $color = $data->productCombination->color->name;
    //         $key = $style . '-' . $color;

    //         if (!isset($reportData[$key])) {
    //             $reportData[$key] = [
    //                 'style' => $style,
    //                 'color' => $color,
    //                 'sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
    //                 'total' => 0
    //             ];
    //         }

    //         foreach ($data->packing_quantities as $sizeId => $qty) {
    //             if (isset($reportData[$key]['sizes'][$sizeId])) {
    //                 $reportData[$key]['sizes'][$sizeId] += $qty;
    //             }
    //         }
    //         $reportData[$key]['total'] += $data->total_packing_quantity;
    //     }

    //     // Get filter options
    //     $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
    //     $allColors = Color::where('is_active', 1)->orderBy('name')->get();
    //     $distinctPoNumbers = FinishPackingData::distinct()->pluck('po_number')->filter()->values();

    //     return view('backend.library.finish_packing_data.reports.total_packing', [
    //         'reportData' => array_values($reportData),
    //         'allSizes' => $allSizes,
    //         'allStyles' => $allStyles,
    //         'allColors' => $allColors,
    //         'distinctPoNumbers' => $distinctPoNumbers
    //     ]);
    // }

    public function sewingWipReport(Request $request)
    {
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $wipData = [];

        // Get filter parameters
        $styleIds = $request->input('style_id', []);
        $colorIds = $request->input('color_id', []);
        $poNumbers = $request->input('po_number', []);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');

        // Base query for product combinations
        $productCombinationsQuery = ProductCombination::whereHas('outputFinishingData')
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
            $key = $style . '-' . $color;

            // Initialize with size IDs as keys
            $wipData[$key] = [
                'style' => $style,
                'color' => $color,
                'sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
                'total' => 0
            ];

            // Get output quantities with filters
            $outputQuery = OutputFinishingData::where('product_combination_id', $pc->id);

            // Apply PO number filter
            if (!empty($poNumbers)) {
                $outputQuery->whereIn('po_number', $poNumbers);
            }

            // Apply date filter
            if ($startDate && $endDate) {
                $outputQuery->whereBetween('date', [$startDate, $endDate]);
            }

            $outputQuantities = $outputQuery->get()
                ->flatMap(fn($item) => $item->output_quantities)
                ->groupBy(fn($value, $key) => $key) // Use size ID as key
                ->map(fn($group) => $group->sum())
                ->toArray();

            // Get packed quantities with filters
            $packedQuery = FinishPackingData::where('product_combination_id', $pc->id);

            // Apply PO number filter
            if (!empty($poNumbers)) {
                $packedQuery->whereIn('po_number', $poNumbers);
            }

            // Apply date filter
            if ($startDate && $endDate) {
                $packedQuery->whereBetween('date', [$startDate, $endDate]);
            }

            $packedQuantities = $packedQuery->get()
                ->flatMap(fn($item) => $item->packing_quantities)
                ->groupBy(fn($value, $key) => $key) // Use size ID as key
                ->map(fn($group) => $group->sum())
                ->toArray();

            foreach ($allSizes as $size) {
                $output = $outputQuantities[$size->id] ?? 0;
                $packed = $packedQuantities[$size->id] ?? 0;
                $wip = max(0, $output - $packed);

                $wipData[$key]['sizes'][$size->id] = $wip;
                $wipData[$key]['total'] += $wip;
            }

            // Remove if no data matches the filters
            if ($wipData[$key]['total'] == 0) {
                unset($wipData[$key]);
            }
        }

        // Get filter options
        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = array_unique(
            array_merge(
                OutputFinishingData::distinct()->pluck('po_number')->filter()->values()->toArray(),
                FinishPackingData::distinct()->pluck('po_number')->filter()->values()->toArray()
            )
        );
        sort($distinctPoNumbers);

        return view('backend.library.finish_packing_data.reports.sewing_wip', [
            'wipData' => array_values($wipData),
            'allSizes' => $allSizes,
            'allStyles' => $allStyles,
            'allColors' => $allColors,
            'distinctPoNumbers' => $distinctPoNumbers
        ]);
    }

    // public function balanceReport(Request $request)
    // {
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $reportData = [];

    //     // Get filter parameters
    //     $styleIds = $request->input('style_id', []);
    //     $colorIds = $request->input('color_id', []);
    //     $poNumbers = $request->input('po_number', []);
    //     $startDate = $request->input('start_date');
    //     $endDate = $request->input('end_date');
    //     $search = $request->input('search');

    //     // Base query for product combinations
    //     $productCombinationsQuery = ProductCombination::whereHas('lineInputData')
    //         ->with('style', 'color');

    //     // Apply style and color filters
    //     if (!empty($styleIds)) {
    //         $productCombinationsQuery->whereIn('style_id', $styleIds);
    //     }

    //     if (!empty($colorIds)) {
    //         $productCombinationsQuery->whereIn('color_id', $colorIds);
    //     }

    //     // Apply search filter
    //     if ($search) {
    //         $productCombinationsQuery->where(function ($q) use ($search) {
    //             $q->whereHas('style', function ($q2) use ($search) {
    //                 $q2->where('name', 'like', '%' . $search . '%');
    //             })->orWhereHas('color', function ($q2) use ($search) {
    //                 $q2->where('name', 'like', '%' . $search . '%');
    //             });
    //         });
    //     }

    //     $productCombinations = $productCombinationsQuery->get();

    //     foreach ($productCombinations as $pc) {
    //         $style = $pc->style->name;
    //         $color = $pc->color->name;
    //         $key = $pc->id; // Use product combination ID as key for uniqueness

    //         // Initialize data structure for this product combination
    //         $reportData[$key] = [
    //             'style' => $style,
    //             'color' => $color,
    //             'stage_balances' => [
    //                 'cutting' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
    //                 'print_wip' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
    //                 'sewing_wip' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
    //                 'packing_wip' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
    //             ],
    //             'total_per_stage' => [
    //                 'cutting' => 0,
    //                 'print_wip' => 0,
    //                 'sewing_wip' => 0,
    //                 'packing_wip' => 0,
    //             ]
    //         ];

    //         // Fetch all relevant quantities for this product combination with filters
    //         $cutQuery = CuttingData::where('product_combination_id', $pc->id);
    //         $printSendQuery = PrintSendData::where('product_combination_id', $pc->id);
    //         $printReceiveQuery = PrintReceiveData::where('product_combination_id', $pc->id);
    //         $lineInputQuery = OutputFinishingData::where('product_combination_id', $pc->id);
    //         $finishPackingQuery = FinishPackingData::where('product_combination_id', $pc->id);

    //         // Apply PO number filter
    //         if (!empty($poNumbers)) {
    //             $cutQuery->whereIn('po_number', $poNumbers);
    //             $printSendQuery->whereIn('po_number', $poNumbers);
    //             $printReceiveQuery->whereIn('po_number', $poNumbers);
    //             $lineInputQuery->whereIn('po_number', $poNumbers);
    //             $finishPackingQuery->whereIn('po_number', $poNumbers);
    //         }

    //         // Apply date filter
    //         if ($startDate && $endDate) {
    //             $cutQuery->whereBetween('created_at', [$startDate, $endDate]);
    //             $printSendQuery->whereBetween('date', [$startDate, $endDate]);
    //             $printReceiveQuery->whereBetween('date', [$startDate, $endDate]);
    //             $lineInputQuery->whereBetween('date', [$startDate, $endDate]);
    //             $finishPackingQuery->whereBetween('date', [$startDate, $endDate]);
    //         }

    //         $cutQuantities = $cutQuery->get()
    //             ->flatMap(fn($item) => $item->cut_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         $printSendQuantities = $printSendQuery->get()
    //             ->flatMap(fn($item) => $item->send_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         $printReceiveQuantities = $printReceiveQuery->get()
    //             ->flatMap(fn($item) => $item->receive_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         $lineInputQuantities = $lineInputQuery->get()
    //             ->flatMap(fn($item) => $item->output_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         $finishPackingQuantities = $finishPackingQuery->get()
    //             ->flatMap(fn($item) => $item->packing_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         foreach ($allSizes as $size) {
    //             $sizeName = strtolower($size->name);

    //             $cut = $cutQuantities[$sizeName] ?? 0;
    //             $printSent = $printSendQuantities[$sizeName] ?? 0;
    //             $printReceived = $printReceiveQuantities[$sizeName] ?? 0;
    //             $lineInput = $lineInputQuantities[$sizeName] ?? 0;
    //             $packed = $finishPackingQuantities[$sizeName] ?? 0;

    //             // Calculate stage balances for this size
    //             $reportData[$key]['stage_balances']['cutting'][$sizeName] = $cut;
    //             $reportData[$key]['stage_balances']['print_wip'][$sizeName] = max(0, $printSent - $printReceived);
    //             $reportData[$key]['stage_balances']['sewing_wip'][$sizeName] = max(0, $printReceived - $lineInput);
    //             $reportData[$key]['stage_balances']['packing_wip'][$sizeName] = max(0, $lineInput - $packed);

    //             // Accumulate totals for the current product combination
    //             $reportData[$key]['total_per_stage']['cutting'] += $reportData[$key]['stage_balances']['cutting'][$sizeName];
    //             $reportData[$key]['total_per_stage']['print_wip'] += $reportData[$key]['stage_balances']['print_wip'][$sizeName];
    //             $reportData[$key]['total_per_stage']['sewing_wip'] += $reportData[$key]['stage_balances']['sewing_wip'][$sizeName];
    //             $reportData[$key]['total_per_stage']['packing_wip'] += $reportData[$key]['stage_balances']['packing_wip'][$sizeName];
    //         }

    //         // Remove if no data matches the filters
    //         if (
    //             $reportData[$key]['total_per_stage']['cutting'] == 0 &&
    //             $reportData[$key]['total_per_stage']['print_wip'] == 0 &&
    //             $reportData[$key]['total_per_stage']['sewing_wip'] == 0 &&
    //             $reportData[$key]['total_per_stage']['packing_wip'] == 0
    //         ) {
    //             unset($reportData[$key]);
    //         }
    //     }

    //     // Get filter options
    //     $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
    //     $allColors = Color::where('is_active', 1)->orderBy('name')->get();
    //     $distinctPoNumbers = array_unique(
    //         array_merge(
    //             CuttingData::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //             PrintSendData::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //             PrintReceiveData::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //             OutputFinishingData::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //             FinishPackingData::distinct()->pluck('po_number')->filter()->values()->toArray()
    //         )
    //     );
    //     sort($distinctPoNumbers);

    //     return view('backend.library.finish_packing_data.reports.balance', [
    //         'reportData' => array_values($reportData),
    //         'allSizes' => $allSizes,
    //         'allStyles' => $allStyles,
    //         'allColors' => $allColors,
    //         'distinctPoNumbers' => $distinctPoNumbers
    //     ]);
    // }



    // public function totalPackingReport(Request $request)
    // {
    //     $query = FinishPackingData::with('productCombination.style', 'productCombination.color');

    //     if ($request->filled('start_date') && $request->filled('end_date')) {
    //         $query->whereBetween('date', [$request->start_date, $request->end_date]);
    //     }

    //     $finishPackingData = $query->get();
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $reportData = [];

    //     foreach ($finishPackingData as $data) {
    //         $style = $data->productCombination->style->name;
    //         $color = $data->productCombination->color->name;
    //         $key = $style . '-' . $color;

    //         if (!isset($reportData[$key])) {
    //             $reportData[$key] = [
    //                 'style' => $style,
    //                 'color' => $color,
    //                 'sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
    //                 'total' => 0
    //             ];
    //         }

    //         foreach ($data->packing_quantities as $sizeId => $qty) {
    //             if (isset($reportData[$key]['sizes'][$sizeId])) {
    //                 $reportData[$key]['sizes'][$sizeId] += $qty;
    //             }
    //         }
    //         $reportData[$key]['total'] += $data->total_packing_quantity;
    //     }

    //     return view('backend.library.finish_packing_data.reports.total_packing', [
    //         'reportData' => array_values($reportData),
    //         'allSizes' => $allSizes
    //     ]);
    // }

    // public function sewingWipReport(Request $request)
    // {
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $wipData = [];

    //     $productCombinations = ProductCombination::whereHas('outputFinishingData')
    //         ->with('style', 'color')
    //         ->get();

    //     foreach ($productCombinations as $pc) {
    //         $style = $pc->style->name;
    //         $color = $pc->color->name;
    //         $key = $style . '-' . $color;

    //         // Initialize with size IDs as keys
    //         $wipData[$key] = [
    //             'style' => $style,
    //             'color' => $color,
    //             'sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
    //             'total' => 0
    //         ];

    //         // Get output quantities (using size IDs as keys)
    //         $outputQuantities = OutputFinishingData::where('product_combination_id', $pc->id)
    //             ->get()
    //             ->flatMap(fn($item) => $item->output_quantities)
    //             ->groupBy(fn($value, $key) => $key) // Use size ID as key
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         // Get packed quantities (using size IDs as keys)
    //         $packedQuantities = FinishPackingData::where('product_combination_id', $pc->id)
    //             ->get()
    //             ->flatMap(fn($item) => $item->packing_quantities)
    //             ->groupBy(fn($value, $key) => $key) // Use size ID as key
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         foreach ($allSizes as $size) {
    //             $output = $outputQuantities[$size->id] ?? 0;
    //             $packed = $packedQuantities[$size->id] ?? 0;
    //             $wip = max(0, $output - $packed);

    //             $wipData[$key]['sizes'][$size->id] = $wip;
    //             $wipData[$key]['total'] += $wip;
    //         }
    //     }

    //     return view('backend.library.finish_packing_data.reports.sewing_wip', [
    //         'wipData' => array_values($wipData),
    //         'allSizes' => $allSizes
    //     ]);
    // }
    // public function balanceReport(Request $request)
    // {
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $reportData = [];

    //     $productCombinations = ProductCombination::whereHas('lineInputData') // Only include PCs that have at least some line input
    //         ->with('style', 'color')
    //         ->get();

    //     foreach ($productCombinations as $pc) {
    //         $style = $pc->style->name;
    //         $color = $pc->color->name;
    //         $key = $pc->id; // Use product combination ID as key for uniqueness

    //         // Initialize data structure for this product combination
    //         $reportData[$key] = [
    //             'style' => $style,
    //             'color' => $color,
    //             'stage_balances' => [ // Will hold stage => size => quantity
    //                 'cutting' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
    //                 'print_wip' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
    //                 'sewing_wip' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
    //                 'packing_wip' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
    //             ],
    //             'total_per_stage' => [ // Will hold total balances for each stage for this PC
    //                 'cutting' => 0,
    //                 'print_wip' => 0,
    //                 'sewing_wip' => 0,
    //                 'packing_wip' => 0,
    //             ]
    //         ];

    //         // Fetch all relevant quantities for this product combination
    //         $cutQuantities = CuttingData::where('product_combination_id', $pc->id)
    //             ->get()
    //             ->flatMap(fn($item) => $item->cut_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         $printSendQuantities = PrintSendData::where('product_combination_id', $pc->id)
    //             ->get()
    //             ->flatMap(fn($item) => $item->send_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         $printReceiveQuantities = PrintReceiveData::where('product_combination_id', $pc->id)
    //             ->get()
    //             ->flatMap(fn($item) => $item->receive_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         $lineInputQuantities = OutputFinishingData::where('product_combination_id', $pc->id)
    //             ->get()
    //             ->flatMap(fn($item) => $item->output_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         $finishPackingQuantities = FinishPackingData::where('product_combination_id', $pc->id)
    //             ->get()
    //             ->flatMap(fn($item) => $item->packing_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         foreach ($allSizes as $size) {
    //             $sizeName = strtolower($size->name);

    //             $cut = $cutQuantities[$sizeName] ?? 0;
    //             $printSent = $printSendQuantities[$sizeName] ?? 0;
    //             $printReceived = $printReceiveQuantities[$sizeName] ?? 0;
    //             $lineInput = $lineInputQuantities[$sizeName] ?? 0;
    //             $packed = $finishPackingQuantities[$sizeName] ?? 0;

    //             // Calculate stage balances for this size
    //             // Cutting: Total quantity cut for this PC and size
    //             $reportData[$key]['stage_balances']['cutting'][$sizeName] = $cut;

    //             // Print WIP: Items sent to print but not yet received back
    //             $reportData[$key]['stage_balances']['print_wip'][$sizeName] = max(0, $printSent - $printReceived);

    //             // Sewing WIP: Items received from print but not yet input to line
    //             $reportData[$key]['stage_balances']['sewing_wip'][$sizeName] = max(0, $printReceived - $lineInput);

    //             // Packing WIP: Items input to line but not yet packed
    //             $reportData[$key]['stage_balances']['packing_wip'][$sizeName] = max(0, $lineInput - $packed);

    //             // Accumulate totals for the current product combination
    //             $reportData[$key]['total_per_stage']['cutting'] += $reportData[$key]['stage_balances']['cutting'][$sizeName];
    //             $reportData[$key]['total_per_stage']['print_wip'] += $reportData[$key]['stage_balances']['print_wip'][$sizeName];
    //             $reportData[$key]['total_per_stage']['sewing_wip'] += $reportData[$key]['stage_balances']['sewing_wip'][$sizeName];
    //             $reportData[$key]['total_per_stage']['packing_wip'] += $reportData[$key]['stage_balances']['packing_wip'][$sizeName];
    //         }
    //     }

    //     return view('backend.library.finish_packing_data.reports.balance', [
    //         'reportData' => array_values($reportData), // Pass as array of values
    //         'allSizes' => $allSizes
    //     ]);
    // }



    // public function edit(FinishPackingData $finishPackingDatum)
    // {
    //     $finishPackingDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //     // Only valid sizes
    //     $validSizes = $allSizes->filter(function ($size) use ($finishPackingDatum) {
    //         return isset($finishPackingDatum->packing_quantities[$size->id]) ||
    //             isset($finishPackingDatum->packing_waste_quantities[$size->id]);
    //     });

    //     $allSizes = $validSizes->values();

    //     // Get max available quantities for this product combination
    //     $maxQuantities = $this->getMaxPackingQuantities($finishPackingDatum->productCombination);

    //     // Get order quantities from order_data table
    //     $poNumbers = explode(',', $finishPackingDatum->po_number);
    //     $orderQuantities = [];

    //     foreach ($poNumbers as $poNumber) {
    //         $orderData = OrderData::where('product_combination_id', $finishPackingDatum->product_combination_id)
    //             ->where('po_number', $poNumber)
    //             ->first();

    //         if ($orderData && $orderData->order_quantities) {
    //             foreach ($orderData->order_quantities as $sizeId => $qty) {
    //                 $orderQuantities[$sizeId] = ($orderQuantities[$sizeId] ?? 0) + $qty;
    //             }
    //         }
    //     }

    //     // Prepare size data with max available quantities and order quantities
    //     $sizeData = [];
    //     foreach ($allSizes as $size) {
    //         $packingQty = $finishPackingDatum->packing_quantities[$size->id] ?? 0;
    //         $wasteQty = $finishPackingDatum->packing_waste_quantities[$size->id] ?? 0;
    //         $maxAvailable = $maxQuantities[$size->id] ?? 0;
    //         $orderQty = $orderQuantities[$size->id] ?? 0;

    //         // Calculate the maximum allowed (available + current packing)
    //         $maxAllowed = $maxAvailable + $packingQty;

    //         $sizeData[] = [
    //             'id' => $size->id,
    //             'name' => $size->name,
    //             'packing_quantity' => $packingQty,
    //             'waste_quantity' => $wasteQty,
    //             'max_available' => $maxAvailable,
    //             'max_allowed' => $maxAllowed,
    //             'order_quantity' => $orderQty,
    //         ];
    //     }

    //     return view('backend.library.finish_packing_data.edit', compact('finishPackingDatum', 'sizeData'));
    // }


    // public function index(Request $request)
    // {
    //     $query = FinishPackingData::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

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

    //     $finishPackingData = $query->orderBy('date', 'desc')->paginate(10);
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //     return view('backend.library.finish_packing_data.index', compact('finishPackingData', 'allSizes'));
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
    //         // Get data for the selected PO number from OutputFinishingData
    //         $productCombinations = ProductCombination::whereHas('outputFinishingData', function ($query) use ($poNumber) {
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

    //             $availableQuantities = $this->getMaxPackingQuantities($pc);

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

    // private function getAvailablePoNumbers()
    // {
    //     $poNumbers = [];

    //     // Get PO numbers from OutputFinishingData
    //     $outputFinishingPoNumbers = OutputFinishingData::distinct()->pluck('po_number')->filter()->values();
    //     $poNumbers = array_merge($poNumbers, $outputFinishingPoNumbers->toArray());

    //     return array_unique($poNumbers);
    // }

    // public function getMaxPackingQuantities(ProductCombination $pc, $poNumbers = [])
    // {
    //     $maxQuantities = [];
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //     // Build query for output quantities with PO number filter
    //     $outputQuery = OutputFinishingData::where('product_combination_id', $pc->id);
    //     if (!empty($poNumbers)) {
    //         $outputQuery->where(function ($query) use ($poNumbers) {
    //             foreach ($poNumbers as $poNumber) {
    //                 $query->orWhere('po_number', 'like', '%' . $poNumber . '%');
    //             }
    //         });
    //     }

    //     // Get total output quantities for the specific PO numbers
    //     $outputQuantities = $outputQuery->get()
    //         ->flatMap(function ($item) {
    //             return $item->output_quantities;
    //         })
    //         ->groupBy(function ($value, $key) {
    //             return $key; // Use size ID as key
    //         })
    //         ->map(function ($group) {
    //             return $group->sum();
    //         })
    //         ->toArray();

    //     // Build query for packed quantities with PO number filter
    //     $packedQuery = FinishPackingData::where('product_combination_id', $pc->id);
    //     if (!empty($poNumbers)) {
    //         $packedQuery->where(function ($query) use ($poNumbers) {
    //             foreach ($poNumbers as $poNumber) {
    //                 $query->orWhere('po_number', 'like', '%' . $poNumber . '%');
    //             }
    //         });
    //     }

    //     // Get total packed quantities for the specific PO numbers
    //     $packedQuantities = $packedQuery->get()
    //         ->flatMap(function ($item) {
    //             return $item->packing_quantities;
    //         })
    //         ->groupBy(function ($value, $key) {
    //             return $key; // Use size ID as key
    //         })
    //         ->map(function ($group) {
    //             return $group->sum();
    //         })
    //         ->toArray();

    //     foreach ($allSizes as $size) {
    //         $output = $outputQuantities[$size->id] ?? 0;
    //         $packed = $packedQuantities[$size->id] ?? 0;
    //         $maxQuantities[$size->id] = max(0, $output - $packed);
    //     }

    //     return $maxQuantities;
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
    //         // Get data for the selected PO number from OutputFinishingData
    //         $productCombinations = ProductCombination::whereHas('outputFinishingData', function ($query) use ($poNumber) {
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

    //             // Pass the PO numbers to getMaxPackingQuantities
    //             $availableQuantities = $this->getMaxPackingQuantities($pc, $poNumbers);

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


    // public function index(Request $request)
    // {
    //     $query = FinishPackingData::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

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

    //     $finishPackingData = $query->orderBy('date', 'desc')->paginate(10);
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //     return view('backend.library.finish_packing_data.index', compact('finishPackingData', 'allSizes'));
    // }

    // public function create()
    // {
    //     $productCombinations = ProductCombination::whereHas('lineInputData')
    //         ->with('buyer', 'style', 'color')
    //         ->get();

    //     $sizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //     return view('backend.library.finish_packing_data.create', compact('productCombinations', 'sizes'));
    // }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'date' => 'required|date',
    //         'product_combination_id' => 'required|exists:product_combinations,id',
    //         'quantities.*' => 'nullable|integer|min:0',
    //     ]);

    //     $productCombination = ProductCombination::findOrFail($request->product_combination_id);
    //     $maxQuantities = $this->getMaxPackingQuantities($productCombination);

    //     $packingQuantities = [];
    //     $totalPackingQuantity = 0;
    //     $errors = [];

    //     foreach ($request->input('quantities', []) as $sizeId => $quantity) {
    //         $size = Size::where('is_active', 1)->find($sizeId);
    //         if ($size && $quantity > 0) {
    //             $sizeName = strtolower($size->name);
    //             $maxAllowed = $maxQuantities[$sizeName] ?? 0;

    //             if ($quantity > $maxAllowed) {
    //                 $errors["quantities.$sizeId"] = "Quantity for {$size->name} exceeds available limit ($maxAllowed)";
    //                 continue;
    //             }

    //             $packingQuantities[$size->name] = (int)$quantity;
    //             $totalPackingQuantity += (int)$quantity;
    //         }
    //     }

    //     if (!empty($errors)) {
    //         return redirect()->back()->withErrors($errors)->withInput();
    //     }

    //     FinishPackingData::create([
    //         'date' => $request->date,
    //         'product_combination_id' => $request->product_combination_id,
    //         'packing_quantities' => $packingQuantities,
    //         'total_packing_quantity' => $totalPackingQuantity,
    //     ]);

    //     return redirect()->route('finish_packing_data.index')->withMessage( 'Finish packing data added successfully.');
    // }

    // public function getMaxPackingQuantities(ProductCombination $pc)
    // {
    //     $maxQuantities = [];
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //     // Get total input quantities
    //     $inputQuantities = OutputFinishingData::where('product_combination_id', $pc->id)
    //         ->get()
    //         ->flatMap(fn($item) => $item->output_quantities)
    //         ->groupBy(fn($value, $key) => strtolower($key))
    //         ->map(fn($group) => $group->sum())
    //         ->toArray();

    //     // Get total packed quantities
    //     $packedQuantities = FinishPackingData::where('product_combination_id', $pc->id)
    //         ->get()
    //         ->flatMap(fn($item) => $item->packing_quantities)
    //         ->groupBy(fn($value, $key) => strtolower($key))
    //         ->map(fn($group) => $group->sum())
    //         ->toArray();

    //     foreach ($allSizes as $size) {
    //         $sizeName = strtolower($size->name);
    //         $input = $inputQuantities[$sizeName] ?? 0;
    //         $packed = $packedQuantities[$sizeName] ?? 0;
    //         $maxQuantities[$sizeName] = max(0, $input - $packed);
    //     }

    //     return $maxQuantities;
    // }

    // public function show(FinishPackingData $finishPackingDatum)
    // {
    //     return view('backend.library.finish_packing_data.show', compact('finishPackingDatum'));
    // }

    // public function edit(FinishPackingData $finishPackingDatum)
    // {
    //     $finishPackingDatum->load('productCombination.style', 'productCombination.color');
    //     $sizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $maxQuantities = $this->getMaxPackingQuantities($finishPackingDatum->productCombination);

    //     $sizeData = $sizes->map(function ($size) use ($finishPackingDatum, $maxQuantities) {
    //         $sizeName = strtolower($size->name);
    //         return [
    //             'id' => $size->id,
    //             'name' => $size->name,
    //             'max_allowed' => $maxQuantities[$sizeName] ?? 0,
    //             'current_quantity' => $finishPackingDatum->packing_quantities[$size->name] ?? 0
    //         ];
    //     });

    //     return view('backend.library.finish_packing_data.edit', [
    //         'finishPackingDatum' => $finishPackingDatum,
    //         'sizeData' => $sizeData
    //     ]);
    // }

    // public function update(Request $request, FinishPackingData $finishPackingDatum)
    // {
    //     $request->validate([
    //         'date' => 'required|date',
    //         'quantities.*' => 'nullable|integer|min:0',
    //     ]);

    //     $productCombination = $finishPackingDatum->productCombination;
    //     $maxQuantities = $this->getMaxPackingQuantities($productCombination);

    //     $packingQuantities = [];
    //     $totalPackingQuantity = 0;
    //     $errors = [];

    //     foreach ($request->input('quantities', []) as $sizeId => $quantity) {
    //         $size = Size::where('is_active', 1)->find($sizeId);
    //         if ($size && $quantity > 0) {
    //             $sizeName = strtolower($size->name);
    //             $maxAllowed = ($maxQuantities[$sizeName] ?? 0) + ($finishPackingDatum->packing_quantities[$size->name] ?? 0);

    //             if ($quantity > $maxAllowed) {
    //                 $errors["quantities.$sizeId"] = "Quantity for {$size->name} exceeds available limit ($maxAllowed)";
    //                 continue;
    //             }

    //             $packingQuantities[$size->name] = (int)$quantity;
    //             $totalPackingQuantity += (int)$quantity;
    //         }
    //     }

    //     if (!empty($errors)) {
    //         return redirect()->back()->withErrors($errors)->withInput();
    //     }

    //     $finishPackingDatum->update([
    //         'date' => $request->date,
    //         'packing_quantities' => $packingQuantities,
    //         'total_packing_quantity' => $totalPackingQuantity,
    //     ]);

    //     return redirect()->route('finish_packing_data.index')->withMessage( 'Finish packing data updated successfully.');
    // }

    // public function destroy(FinishPackingData $finishPackingDatum)
    // {
    //     $finishPackingDatum->delete();
    //     return redirect()->route('finish_packing_data.index')->withMessage( 'Finish packing data deleted successfully.');
    // }

    // // Reports
    // public function totalPackingReport(Request $request)
    // {
    //     $query = FinishPackingData::with('productCombination.style', 'productCombination.color');

    //     if ($request->filled('start_date') && $request->filled('end_date')) {
    //         $query->whereBetween('date', [$request->start_date, $request->end_date]);
    //     }

    //     $finishPackingData = $query->get();
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $reportData = [];

    //     foreach ($finishPackingData as $data) {
    //         $style = $data->productCombination->style->name;
    //         $color = $data->productCombination->color->name;
    //         $key = $style . '-' . $color;

    //         if (!isset($reportData[$key])) {
    //             $reportData[$key] = [
    //                 'style' => $style,
    //                 'color' => $color,
    //                 'sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
    //                 'total' => 0
    //             ];
    //         }

    //         foreach ($data->packing_quantities as $size => $qty) {
    //             $normalized = strtolower($size);
    //             if (array_key_exists($normalized, $reportData[$key]['sizes'])) {
    //                 $reportData[$key]['sizes'][$normalized] += $qty;
    //             }
    //         }
    //         $reportData[$key]['total'] += $data->total_packing_quantity;
    //     }

    //     return view('backend.library.finish_packing_data.reports.total_packing', [
    //         'reportData' => array_values($reportData),
    //         'allSizes' => $allSizes
    //     ]);
    // }

    // public function sewingWipReport(Request $request)
    // {
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $wipData = [];

    //     $productCombinations = ProductCombination::whereHas('lineInputData')
    //         ->with('style', 'color')
    //         ->get();

    //     foreach ($productCombinations as $pc) {
    //         $style = $pc->style->name;
    //         $color = $pc->color->name;
    //         $key = $style . '-' . $color;

    //         $wipData[$key] = [
    //             'style' => $style,
    //             'color' => $color,
    //             'sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
    //             'total' => 0
    //         ];

    //         // Get input quantities
    //         $inputQuantities = OutputFinishingData::where('product_combination_id', $pc->id)
    //             ->get()
    //             ->flatMap(fn($item) => $item->output_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         // Get packed quantities
    //         $packedQuantities = FinishPackingData::where('product_combination_id', $pc->id)
    //             ->get()
    //             ->flatMap(fn($item) => $item->packing_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         foreach ($allSizes as $size) {
    //             $sizeName = strtolower($size->name);
    //             $input = $inputQuantities[$sizeName] ?? 0;
    //             $packed = $packedQuantities[$sizeName] ?? 0;
    //             $wip = max(0, $input - $packed);

    //             $wipData[$key]['sizes'][$sizeName] = $wip;
    //             $wipData[$key]['total'] += $wip;
    //         }
    //     }

    //     return view('backend.library.finish_packing_data.reports.sewing_wip', [
    //         'wipData' => array_values($wipData),
    //         'allSizes' => $allSizes
    //     ]);
    // }

    // public function getAvailablePackingQuantities(ProductCombination $productCombination)
    // {
    //     $maxQuantities = $this->getMaxPackingQuantities($productCombination);
    //     $sizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //     return response()->json([
    //         'availableQuantities' => $maxQuantities,
    //         'sizes' => $sizes
    //     ]);
    // }

    public function totalPackingReport(Request $request)
    {
        $query = FinishPackingData::with('productCombination.style', 'productCombination.color');
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
                    ->orWhereHas('productCombination.style', function ($q2) use ($search) {
                        $q2->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('productCombination.color', function ($q2) use ($search) {
                        $q2->where('name', 'like', '%' . $search . '%');
                    });
            });
        }
        $finishPackingData = $query->get();
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $reportData = [];
        foreach ($finishPackingData as $data) {
            $style = $data->productCombination->style->name;
            $color = $data->productCombination->color->name;
            $key = $style . '-' . $color;
            if (!isset($reportData[$key])) {
                $reportData[$key] = [
                    'style' => $style,
                    'color' => $color,
                    'sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
                    'sizes_waste' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
                    'total' => 0,
                    'total_waste' => 0
                ];
            }
            foreach ($data->packing_quantities as $sizeId => $qty) {
                if (isset($reportData[$key]['sizes'][$sizeId])) {
                    $reportData[$key]['sizes'][$sizeId] += $qty;
                }
            }
            $reportData[$key]['total'] += $data->total_packing_quantity;
            foreach ($data->packing_waste_quantities as $sizeId => $qty) {
                if (isset($reportData[$key]['sizes_waste'][$sizeId])) {
                    $reportData[$key]['sizes_waste'][$sizeId] += $qty;
                }
            }
            $reportData[$key]['total_waste'] += $data->total_packing_waste_quantity;
        }
        // Get filter options
        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = FinishPackingData::distinct()->pluck('po_number')->filter()->values();
        return view('backend.library.finish_packing_data.reports.total_packing', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes,
            'allStyles' => $allStyles,
            'allColors' => $allColors,
            'distinctPoNumbers' => $distinctPoNumbers
        ]);
    }

    public function balanceReport(Request $request)
    {
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $reportData = [];

        // Get filter parameters
        $styleIds = $request->input('style_id', []);
        $colorIds = $request->input('color_id', []);
        $poNumbers = $request->input('po_number', []);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');

        // Base query for product combinations
        $productCombinationsQuery = ProductCombination::whereHas('lineInputData')
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
                })->orWhere(function ($q) use ($search) {
                    $q->whereHas('cuttingData', function ($q2) use ($search) {
                        $q2->where('po_number', 'like', '%' . $search . '%');
                    })->orWhereHas('printSendData', function ($q2) use ($search) {
                        $q2->where('po_number', 'like', '%' . $search . '%');
                    })->orWhereHas('printReceiveData', function ($q2) use ($search) {
                        $q2->where('po_number', 'like', '%' . $search . '%');
                    })->orWhereHas('lineInputData', function ($q2) use ($search) {
                        $q2->where('po_number', 'like', '%' . $search . '%');
                    })->orWhereHas('finishPackingData', function ($q2) use ($search) {
                        $q2->where('po_number', 'like', '%' . $search . '%');
                    });
                });
            });
        }

        $productCombinations = $productCombinationsQuery->get();

        foreach ($productCombinations as $pc) {
            $style = $pc->style->name;
            $color = $pc->color->name;
            $key = $pc->id; // Use product combination ID as key for uniqueness

            // Initialize data structure for this product combination
            $reportData[$key] = [
                'style' => $style,
                'color' => $color,
                'stage_balances' => [
                    'cutting' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
                    'print_wip' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
                    'sewing_wip' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
                    'packing_wip' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
                ],
                'total_per_stage' => [
                    'cutting' => 0,
                    'print_wip' => 0,
                    'sewing_wip' => 0,
                    'packing_wip' => 0,
                ]
            ];

            // Fetch all relevant quantities for this product combination with filters
            $cutQuery = CuttingData::where('product_combination_id', $pc->id);
            $printSendQuery = PrintSendData::where('product_combination_id', $pc->id);
            $printReceiveQuery = PrintReceiveData::where('product_combination_id', $pc->id);
            $lineInputQuery = OutputFinishingData::where('product_combination_id', $pc->id);
            $finishPackingQuery = FinishPackingData::where('product_combination_id', $pc->id);

            // Apply PO number filter
            if (!empty($poNumbers)) {
                $cutQuery->whereIn('po_number', $poNumbers);
                $printSendQuery->whereIn('po_number', $poNumbers);
                $printReceiveQuery->whereIn('po_number', $poNumbers);
                $lineInputQuery->whereIn('po_number', $poNumbers);
                $finishPackingQuery->whereIn('po_number', $poNumbers);
            }

            // Apply date filter
            if ($startDate && $endDate) {
                $cutQuery->whereBetween('date', [$startDate, $endDate]);
                $printSendQuery->whereBetween('date', [$startDate, $endDate]);
                $printReceiveQuery->whereBetween('date', [$startDate, $endDate]);
                $lineInputQuery->whereBetween('date', [$startDate, $endDate]);
                $finishPackingQuery->whereBetween('date', [$startDate, $endDate]);
            }

            $cutQuantities = $cutQuery->get()
                ->flatMap(fn($item) => $item->cut_quantities)
                ->groupBy(fn($value, $key) => $key)
                ->map(fn($group) => $group->sum())
                ->toArray();

            $printSendQuantities = $printSendQuery->get()
                ->flatMap(fn($item) => $item->send_quantities)
                ->groupBy(fn($value, $key) => $key)
                ->map(fn($group) => $group->sum())
                ->toArray();

            $printReceiveQuantities = $printReceiveQuery->get()
                ->flatMap(fn($item) => $item->receive_quantities)
                ->groupBy(fn($value, $key) => $key)
                ->map(fn($group) => $group->sum())
                ->toArray();

            $lineInputQuantities = $lineInputQuery->get()
                ->flatMap(fn($item) => $item->output_quantities)
                ->groupBy(fn($value, $key) => $key)
                ->map(fn($group) => $group->sum())
                ->toArray();

            $finishPackingQuantities = $finishPackingQuery->get()
                ->flatMap(fn($item) => $item->packing_quantities)
                ->groupBy(fn($value, $key) => $key)
                ->map(fn($group) => $group->sum())
                ->toArray();

            $packingWasteQuantities = $finishPackingQuery->get()
                ->flatMap(fn($item) => $item->packing_waste_quantities)
                ->groupBy(fn($value, $key) => $key)
                ->map(fn($group) => $group->sum())
                ->toArray();

            foreach ($allSizes as $size) {
                $sizeId = $size->id;

                $cut = $cutQuantities[$sizeId] ?? 0;
                $printSent = $printSendQuantities[$sizeId] ?? 0;
                $printReceived = $printReceiveQuantities[$sizeId] ?? 0;
                $lineInput = $lineInputQuantities[$sizeId] ?? 0;
                $packed = ($finishPackingQuantities[$sizeId] ?? 0) + ($packingWasteQuantities[$sizeId] ?? 0);

                // Calculate stage balances for this size
                $reportData[$key]['stage_balances']['cutting'][$sizeId] = $cut;
                $reportData[$key]['stage_balances']['print_wip'][$sizeId] = max(0, $printSent - $printReceived);
                $reportData[$key]['stage_balances']['sewing_wip'][$sizeId] = max(0, $printReceived - $lineInput);
                $reportData[$key]['stage_balances']['packing_wip'][$sizeId] = max(0, $lineInput - $packed);

                // Accumulate totals for the current product combination
                $reportData[$key]['total_per_stage']['cutting'] += $reportData[$key]['stage_balances']['cutting'][$sizeId];
                $reportData[$key]['total_per_stage']['print_wip'] += $reportData[$key]['stage_balances']['print_wip'][$sizeId];
                $reportData[$key]['total_per_stage']['sewing_wip'] += $reportData[$key]['stage_balances']['sewing_wip'][$sizeId];
                $reportData[$key]['total_per_stage']['packing_wip'] += $reportData[$key]['stage_balances']['packing_wip'][$sizeId];
            }

            // Remove if no data matches the filters
            if (
                $reportData[$key]['total_per_stage']['cutting'] == 0 &&
                $reportData[$key]['total_per_stage']['print_wip'] == 0 &&
                $reportData[$key]['total_per_stage']['sewing_wip'] == 0 &&
                $reportData[$key]['total_per_stage']['packing_wip'] == 0
            ) {
                unset($reportData[$key]);
            }
        }

        // Get filter options
        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = array_unique(
            array_merge(
                CuttingData::distinct()->pluck('po_number')->filter()->values()->toArray(),
                PrintSendData::distinct()->pluck('po_number')->filter()->values()->toArray(),
                PrintReceiveData::distinct()->pluck('po_number')->filter()->values()->toArray(),
                OutputFinishingData::distinct()->pluck('po_number')->filter()->values()->toArray(),
                FinishPackingData::distinct()->pluck('po_number')->filter()->values()->toArray()
            )
        );
        sort($distinctPoNumbers);

        return view('backend.library.finish_packing_data.reports.balance', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes,
            'allStyles' => $allStyles,
            'allColors' => $allColors,
            'distinctPoNumbers' => $distinctPoNumbers
        ]);
    }
}
