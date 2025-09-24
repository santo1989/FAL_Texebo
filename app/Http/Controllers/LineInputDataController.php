<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\CuttingData;
use App\Models\FinishPackingData;
use App\Models\LineInputData;
use App\Models\OrderData;
use App\Models\OutputFinishingData;
use App\Models\PrintReceiveData;
use App\Models\ProductCombination;
use App\Models\ShipmentData;
use App\Models\Size;
use App\Models\Style;
use App\Models\SublimationPrintReceive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LineInputDataController extends Controller
{

    public function index(Request $request)
    {
        $query = LineInputData::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

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

        $lineInputData = $query->orderBy('date', 'desc')->paginate(10);
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Get filter options
        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = LineInputData::distinct()->pluck('po_number')->filter()->values();

        return view('backend.library.line_input_data.index', compact(
            'lineInputData',
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

        return view('backend.library.line_input_data.create', compact('distinctPoNumbers', 'sizes'));
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'date' => 'required|date',
    //         'po_number' => 'required|array',
    //         'po_number.*' => 'required|string',
    //         'rows' => 'required|array',
    //         'rows.*.product_combination_id' => 'required|exists:product_combinations,id',
    //         'rows.*.input_quantities.*' => 'nullable|integer|min:0',
    //         'rows.*.input_waste_quantities.*' => 'nullable|integer|min:0',
    //     ]);

    //     try {
    //         DB::beginTransaction();

    //         foreach ($request->rows as $row) {
    //             $inputQuantities = [];
    //             $wasteQuantities = [];
    //             $totalInputQuantity = 0;
    //             $totalWasteQuantity = 0;

    //             // Process input quantities
    //             foreach ($row['input_quantities'] as $sizeId => $quantity) {
    //                 if ($quantity !== null && (int)$quantity > 0) {
    //                     $size = Size::find($sizeId);
    //                     if ($size) {
    //                         $inputQuantities[$size->id] = (int)$quantity;
    //                         $totalInputQuantity += (int)$quantity;
    //                     }
    //                 }
    //             }

    //             // Process waste quantities
    //             foreach ($row['input_waste_quantities'] as $sizeId => $quantity) {
    //                 if ($quantity !== null && (int)$quantity > 0) {
    //                     $size = Size::find($sizeId);
    //                     if ($size) {
    //                         $wasteQuantities[$size->id] = (int)$quantity;
    //                         $totalWasteQuantity += (int)$quantity;
    //                     }
    //                 }
    //             }

    //             // Only create a record if there's at least one valid input or waste quantity
    //             if (!empty($inputQuantities) || !empty($wasteQuantities)) {
    //                 LineInputData::create([
    //                     'date' => $request->date,
    //                     'product_combination_id' => $row['product_combination_id'],
    //                     'po_number' => $row['po_number'],
    //                     'input_quantities' => $inputQuantities,
    //                     'total_input_quantity' => $totalInputQuantity,
    //                     'input_waste_quantities' => $wasteQuantities,
    //                     'total_input_waste_quantity' => $totalWasteQuantity,
    //                 ]);
    //             }
    //         }

    //         DB::commit();

    //         return redirect()->route('line_input_data.index')
    //             ->withMessage('Line input data added successfully.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return redirect()->back()
    //             ->with('error', 'Error occurred: ' . $e->getMessage())
    //             ->withInput();
    //     }
    // }



    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'po_number' => 'required|array',
            'po_number.*' => 'required|string',
            'rows' => 'required|array',
            'rows.*.product_combination_id' => 'required|exists:product_combinations,id',
            'rows.*.po_number' => 'required|string', // Add validation for row-level PO number
            'rows.*.input_quantities.*' => 'nullable|integer|min:0',
            'rows.*.input_waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        try {
            DB::beginTransaction();
            $validRowsProcessed = 0;

            foreach ($request->rows as $row) {
                // Check if this row has any available quantities (not all N/A)
                $hasAvailableQuantities = false;
                $hasValidQuantities = false;

                // Check input quantities
                if (isset($row['input_quantities'])) {
                    foreach ($row['input_quantities'] as $quantity) {
                        if ($quantity !== null) {
                            $hasAvailableQuantities = true;
                            if ((int)$quantity > 0) {
                                $hasValidQuantities = true;
                            }
                        }
                    }
                }

                // If no available quantities at all (all N/A), skip this row entirely
                if (!$hasAvailableQuantities) {
                    continue;
                }

                $inputQuantities = [];
                $wasteQuantities = [];
                $totalInputQuantity = 0;
                $totalWasteQuantity = 0;

                // Process input quantities
                if (isset($row['input_quantities'])) {
                    foreach ($row['input_quantities'] as $sizeId => $quantity) {
                        if ($quantity !== null && (int)$quantity > 0) {
                            $size = Size::find($sizeId);
                            if ($size) {
                                $inputQuantities[$size->id] = (int)$quantity;
                                $totalInputQuantity += (int)$quantity;
                            }
                        }
                    }
                }

                // Process waste quantities
                if (isset($row['input_waste_quantities'])) {
                    foreach ($row['input_waste_quantities'] as $sizeId => $quantity) {
                        if ($quantity !== null && (int)$quantity > 0) {
                            $size = Size::find($sizeId);
                            if ($size) {
                                $wasteQuantities[$size->id] = (int)$quantity;
                                $totalWasteQuantity += (int)$quantity;
                            }
                        }
                    }
                }

                // Only create a record if there's at least one valid input or waste quantity
                if (!empty($inputQuantities) || !empty($wasteQuantities)) {
                    LineInputData::create([
                        'date' => $request->date,
                        'product_combination_id' => $row['product_combination_id'],
                        'po_number' => $row['po_number'], // Use the row-level PO number
                        'input_quantities' => $inputQuantities,
                        'total_input_quantity' => $totalInputQuantity,
                        'input_waste_quantities' => $wasteQuantities,
                        'total_input_waste_quantity' => $totalWasteQuantity,
                    ]);

                    $validRowsProcessed++;
                }
            }

            DB::commit();

            if ($validRowsProcessed > 0) {
                return redirect()->route('line_input_data.index')
                    ->withMessage( 'Line input data added successfully.');
            } else {
                return redirect()->back()
                    ->with('warning', 'No valid data to save. Please check your entries.')
                    ->withInput();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    public function show(LineInputData $lineInputDatum)
    {
        $lineInputDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Only valid sizes
        $validSizes = $allSizes->filter(function ($size) use ($lineInputDatum) {
            return isset($lineInputDatum->input_quantities[$size->id]) ||
                isset($lineInputDatum->input_waste_quantities[$size->id]);
        });

        $allSizes = $validSizes->values();

        return view('backend.library.line_input_data.show', compact('lineInputDatum', 'allSizes'));
    }

    // public function edit(LineInputData $lineInputDatum)
    // {
    //     $lineInputDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //     // Only valid sizes
    //     $validSizes = $allSizes->filter(function ($size) use ($lineInputDatum) {
    //         return isset($lineInputDatum->input_quantities[$size->id]) ||
    //             isset($lineInputDatum->input_waste_quantities[$size->id]);
    //     });

    //     $allSizes = $validSizes->values();

    //     // Get max available quantities for this product combination
    //     $maxQuantities = $this->getMaxInputQuantitiesByPo($lineInputDatum);

    //     // dd($maxQuantities);

    //     // Get order quantities from order_data table
    //     $poNumbers = explode(',', $lineInputDatum->po_number);
    //     $orderQuantities = [];

    //     foreach ($poNumbers as $poNumber) {
    //         $orderData = OrderData::where('product_combination_id', $lineInputDatum->product_combination_id)
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
    //         $inputQty = $lineInputDatum->input_quantities[$size->id] ?? 0;
    //         $wasteQty = $lineInputDatum->input_waste_quantities[$size->id] ?? 0;
    //         $maxAvailable = $maxQuantities[$size->id] ?? 0;
    //         $orderQty = $orderQuantities[$size->id] ?? 0;

    //         // Calculate the maximum allowed (available + current input)
    //         $maxAllowed = $maxAvailable + $inputQty;

    //         $sizeData[] = [
    //             'id' => $size->id,
    //             'name' => $size->name,
    //             'input_quantity' => $inputQty,
    //             'waste_quantity' => $wasteQty,
    //             'max_available' => $maxAvailable,
    //             'max_allowed' => $maxAllowed,
    //             'order_quantity' => $orderQty,
    //         ];
    //     }

    //     return view('backend.library.line_input_data.edit', compact('lineInputDatum', 'sizeData'));
    // }


    // public function update(Request $request, LineInputData $lineInputDatum)
    // {
    //     $request->validate([
    //         'date' => 'required|date',
    //         'input_quantities.*' => 'nullable|integer|min:0',
    //     ]);

    //     try {
    //         $inputQuantities = [];
    //         $totalInputQuantity = 0;

    //         // Process input quantities
    //         foreach ($request->input_quantities as $sizeId => $quantity) {
    //             if ($quantity !== null && (int)$quantity > 0) {
    //                 $inputQuantities[$sizeId] = (int)$quantity;
    //                 $totalInputQuantity += (int)$quantity;
    //             }
    //         }

    //         $lineInputDatum->update([
    //             'date' => $request->date,
    //             'input_quantities' => $inputQuantities,
    //             'total_input_quantity' => $totalInputQuantity,
    //         ]);

    //         return redirect()->route('line_input_data.index')
    //             ->withMessage('Line input data updated successfully.');
    //     } catch (\Exception $e) {
    //         return redirect()->back()
    //             ->with('error', 'Error occurred: ' . $e->getMessage())
    //             ->withInput();
    //     }
    // }


    public function update(Request $request, LineInputData $lineInputDatum)
    {
        $request->validate([
            'date' => 'required|date',
            'input_quantities.*' => 'nullable|integer|min:0',
        ]);

        // dd($request->all());

        try {
            $inputQuantities = [];
            $totalInputQuantity = 0;

            // Process input quantities
            foreach ($request->input_quantities as $sizeId => $quantity) {
                if ($quantity !== null && (int)$quantity > 0) {
                    $inputQuantities[$sizeId] = (int)$quantity;
                    $totalInputQuantity += (int)$quantity;
                }
            }

            $lineInputDatum->update([
                'date' => $request->date,
                'input_quantities' => $inputQuantities,
                'total_input_quantity' => $totalInputQuantity,
                'total_input_waste_quantity' => 0, // Reset waste quantity on update
                'input_waste_quantities' => [], // Reset waste quantities on update
            ]);

            return redirect()->route('line_input_data.index')
                ->withMessage( 'Line input data updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors( $e->getMessage())
                ->withInput();
        }
    }
    public function destroy(LineInputData $lineInputDatum)
    {
        //next stage data first delete then delete this
        $outputData = OutputFinishingData::whereIn('po_number', explode(',', $lineInputDatum->po_number))
            ->where('product_combination_id', $lineInputDatum->product_combination_id)
            ->get();
        if ($outputData->count() > 0) {
            $outputData->each->delete();
        }
        $finishpackingData = FinishPackingData::whereIn('po_number', explode(',', $lineInputDatum->po_number))
            ->where('product_combination_id', $lineInputDatum->product_combination_id)
            ->get();
        if ($finishpackingData->count() > 0) {
            $finishpackingData->each->delete();
        }
        $shipmentData = ShipmentData::whereIn('po_number', explode(',', $lineInputDatum->po_number))
            ->where('product_combination_id', $lineInputDatum->product_combination_id)
            ->get();
        if ($shipmentData->count() > 0) {
            $shipmentData->each->delete();
        }
        $lineInputDatum->delete();
        return redirect()->route('line_input_data.index')->withMessage('Line input data deleted successfully.');
    }

    // Reports

    public function getMaxInputQuantities(ProductCombination $pc)
    {
        $maxQuantities = [];
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();


        // Determine source based on product combination type
        if ($pc->print_embroidery && !$pc->sublimation_print) {
            // Only print_embroidery is true - from PrintReceiveData
            $sourceQuantities = PrintReceiveData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(function ($item) {
                    return $item->receive_quantities;
                })
                ->groupBy(function ($value, $key) {
                    return $key; // Use size ID as key
                })
                ->map(function ($group) {
                    return $group->sum();
                })
                ->toArray();
        } elseif (!$pc->print_embroidery && $pc->sublimation_print) {
            // Only sublimation_print is true - from SublimationPrintReceive
            $sourceQuantities = SublimationPrintReceive::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(function ($item) {
                    return $item->receive_quantities;
                })
                ->groupBy(function ($value, $key) {
                    return $key; // Use size ID as key
                })
                ->map(function ($group) {
                    return $group->sum();
                })
                ->toArray();
        } elseif ($pc->print_embroidery && $pc->sublimation_print) {
            // Both are true - from PrintReceiveData
            $sourceQuantities = PrintReceiveData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(function ($item) {
                    return $item->receive_quantities;
                })
                ->groupBy(function ($value, $key) {
                    return $key; // Use size ID as key
                })
                ->map(function ($group) {
                    return $group->sum();
                })
                ->toArray();
        } else {
            // Both are false - from CuttingData
            $sourceQuantities = CuttingData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(function ($item) {
                    return $item->cut_quantities;
                })
                ->groupBy(function ($value, $key) {
                    return $key; // Use size ID as key
                })
                ->map(function ($group) {
                    return $group->sum();
                })
                ->toArray();
        }

        $inputQuantities = LineInputData::where('product_combination_id', $pc->id)
            ->get()
            ->flatMap(function ($item) {
                return $item->input_quantities;
            })
            ->groupBy(function ($value, $key) {
                return $key; // Use size ID as key
            })
            ->map(function ($group) {
                return $group->sum();
            })
            ->toArray();

        foreach ($allSizes as $size) {
            $source = $sourceQuantities[$size->id] ?? 0;
            $input = $inputQuantities[$size->id] ?? 0;
            $maxQuantities[$size->id] = max(0, $source - $input);
        }

        return $maxQuantities;
    }
    public function getAvailableQuantities(ProductCombination $productCombination)
    {
        $maxQuantities = $this->getMaxInputQuantities($productCombination);
        $sizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        return response()->json([
            'availableQuantities' => $maxQuantities,
            'sizes' => $sizes
        ]);
    }

    private function getAvailablePoNumbers()
    {
        $poNumbers = [];

        // Get PO numbers from all relevant sources
        $cuttingPoNumbers = CuttingData::distinct()->pluck('po_number')->filter()->values();
        $printPoNumbers = PrintReceiveData::distinct()->pluck('po_number')->filter()->values();
        $sublimationPoNumbers = SublimationPrintReceive::distinct()->pluck('po_number')->filter()->values();

        $poNumbers = array_merge(
            $cuttingPoNumbers->toArray(),
            $printPoNumbers->toArray(),
            $sublimationPoNumbers->toArray()
        );

        return array_unique($poNumbers);
    }

    private function getMaxInputQuantitiesByPo(LineInputData $lineInputData)
    {
        $maxQuantities = [];
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Get PO numbers from the line input data
        $poNumbers = explode(',', $lineInputData->po_number);

        $sourceQuantities = [];
        $inputQuantities = [];

        // Calculate source quantities based on PO numbers (across all product combinations)
        foreach ($poNumbers as $poNumber) {
            // Get all product combinations that have data for this PO number
            $productCombinations = ProductCombination::where(function ($query) use ($poNumber) {
                $query->whereHas('cuttingData', function ($q) use ($poNumber) {
                    $q->where('po_number', 'like', '%' . $poNumber . '%');
                })
                    ->orWhereHas('printReceives', function ($q) use ($poNumber) {
                        $q->where('po_number', 'like', '%' . $poNumber . '%');
                    })
                    ->orWhereHas('sublimationPrintReceives', function ($q) use ($poNumber) {
                        $q->where('po_number', 'like', '%' . $poNumber . '%');
                    });
            })->get();

            foreach ($productCombinations as $pc) {
                // Determine source based on product combination type
                if ($pc->print_embroidery && !$pc->sublimation_print) {
                    // Print/Embroidery
                    $sourceData = PrintReceiveData::where('product_combination_id', $pc->id)
                        ->where('po_number', 'like', '%' . $poNumber . '%')
                        ->get();

                    foreach ($sourceData as $item) {
                        foreach ($item->receive_quantities as $sizeId => $quantity) {
                            $sourceQuantities[$sizeId] = ($sourceQuantities[$sizeId] ?? 0) + $quantity;
                        }
                    }
                } elseif (!$pc->print_embroidery && $pc->sublimation_print) {
                    // Sublimation
                    $sourceData = SublimationPrintReceive::where('product_combination_id', $pc->id)
                        ->where('po_number', 'like', '%' . $poNumber . '%')
                        ->get();

                    foreach ($sourceData as $item) {
                        foreach ($item->receive_quantities as $sizeId => $quantity) {
                            $sourceQuantities[$sizeId] = ($sourceQuantities[$sizeId] ?? 0) + $quantity;
                        }
                    }
                } elseif ($pc->print_embroidery && $pc->sublimation_print) {
                    // Both print and sublimation
                    $sourceData = PrintReceiveData::where('product_combination_id', $pc->id)
                        ->where('po_number', 'like', '%' . $poNumber . '%')
                        ->get();

                    foreach ($sourceData as $item) {
                        foreach ($item->receive_quantities as $sizeId => $quantity) {
                            $sourceQuantities[$sizeId] = ($sourceQuantities[$sizeId] ?? 0) + $quantity;
                        }
                    }
                } else {
                    // Cutting only
                    $sourceData = CuttingData::where('product_combination_id', $pc->id)
                        ->where('po_number', 'like', '%' . $poNumber . '%')
                        ->get();

                    foreach ($sourceData as $item) {
                        foreach ($item->cut_quantities as $sizeId => $quantity) {
                            $sourceQuantities[$sizeId] = ($sourceQuantities[$sizeId] ?? 0) + $quantity;
                        }
                    }
                }

                // Calculate input quantities for this PO (across all product combinations)
                $inputData = LineInputData::where('id', '!=', $lineInputData->id) // Exclude current record
                    ->where('po_number', 'like', '%' . $poNumber . '%')
                    ->get();

                foreach ($inputData as $item) {
                    foreach ($item->input_quantities as $sizeId => $quantity) {
                        $inputQuantities[$sizeId] = ($inputQuantities[$sizeId] ?? 0) + $quantity;
                    }
                }
            }
        }

        // Calculate max quantities for each size
        foreach ($allSizes as $size) {
            $source = $sourceQuantities[$size->id] ?? 0;
            $input = $inputQuantities[$size->id] ?? 0;
            $maxQuantities[$size->id] = max(0, $source - $input);
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
        // Remove the global processed combinations array
        // $processedCombinations = [];

        foreach ($poNumbers as $poNumber) {
            // Get all product combinations that have data in any of the sources for this PO number
            $productCombinations = ProductCombination::where(function ($query) use ($poNumber) {
                $query->whereHas('cuttingData', function ($q) use ($poNumber) {
                    $q->where('po_number', 'like', '%' . $poNumber . '%');
                })
                    ->orWhereHas('printReceives', function ($q) use ($poNumber) {
                        $q->where('po_number', 'like', '%' . $poNumber . '%');
                    })
                    ->orWhereHas('sublimationPrintReceives', function ($q) use ($poNumber) {
                        $q->where('po_number', 'like', '%' . $poNumber . '%');
                    });
            })
                ->with('style', 'color', 'size')
                ->get();

            // Create a processed combinations array PER PO NUMBER
            $processedCombinationsForPo = [];

            foreach ($productCombinations as $pc) {
                if (!$pc->style || !$pc->color) {
                    continue;
                }

                $combinationKey = $pc->id . '-' . $pc->style->name . '-' . $pc->color->name;

                // Skip if we've already processed this combination FOR THIS PO
                if (in_array($combinationKey, $processedCombinationsForPo)) {
                    continue;
                }

                // Mark this combination as processed FOR THIS PO
                $processedCombinationsForPo[] = $combinationKey;

                // Get order quantities for this PO and product combination
                $orderData = OrderData::where('product_combination_id', $pc->id)
                    ->where('po_number', $poNumber)
                    ->first();

                if (!$orderData) {
                    continue;
                }

                // Get available quantities from previous stages based on product combination type
                $availableQuantities = [];

                if ($pc->print_embroidery && !$pc->sublimation_print) {
                    // Print/Embroidery
                    PrintReceiveData::where('product_combination_id', $pc->id)
                        ->where('po_number', 'like', '%' . $poNumber . '%')
                        ->get()
                        ->each(function ($item) use (&$availableQuantities) {
                            foreach ($item->receive_quantities as $sizeId => $quantity) {
                                $availableQuantities[$sizeId] = ($availableQuantities[$sizeId] ?? 0) + $quantity;
                            }
                        });
                } elseif (!$pc->print_embroidery && $pc->sublimation_print) {
                    // Sublimation
                    SublimationPrintReceive::where('product_combination_id', $pc->id)
                        ->where('po_number', 'like', '%' . $poNumber . '%')
                        ->get()
                        ->each(function ($item) use (&$availableQuantities) {
                            foreach ($item->sublimation_print_receive_quantities as $sizeId => $quantity) {
                                $availableQuantities[$sizeId] = ($availableQuantities[$sizeId] ?? 0) + $quantity;
                            }
                        });
                } elseif ($pc->print_embroidery && $pc->sublimation_print) {
                    // Both print and sublimation
                    PrintReceiveData::where('product_combination_id', $pc->id)
                        ->where('po_number', 'like', '%' . $poNumber . '%')
                        ->get()
                        ->each(function ($item) use (&$availableQuantities) {
                            foreach ($item->receive_quantities as $sizeId => $quantity) {
                                $availableQuantities[$sizeId] = ($availableQuantities[$sizeId] ?? 0) + $quantity;
                            }
                        });

                    SublimationPrintReceive::where('product_combination_id', $pc->id)
                        ->where('po_number', 'like', '%' . $poNumber . '%')
                        ->get()
                        ->each(function ($item) use (&$availableQuantities) {
                            foreach ($item->sublimation_print_receive_quantities as $sizeId => $quantity) {
                                $availableQuantities[$sizeId] = ($availableQuantities[$sizeId] ?? 0) + $quantity;
                            }
                        });
                } else {
                    // Cutting only
                    CuttingData::where('product_combination_id', $pc->id)
                        ->where('po_number', 'like', '%' . $poNumber . '%')
                        ->get()
                        ->each(function ($item) use (&$availableQuantities) {
                            foreach ($item->cut_quantities as $sizeId => $quantity) {
                                $availableQuantities[$sizeId] = ($availableQuantities[$sizeId] ?? 0) + $quantity;
                            }
                        });
                }

                // Subtract already input quantities
                $inputQuantities = [];
                LineInputData::where('product_combination_id', $pc->id)
                    ->where('po_number', 'like', '%' . $poNumber . '%')
                    ->get()
                    ->each(function ($item) use (&$inputQuantities) {
                        foreach ($item->input_quantities as $sizeId => $quantity) {
                            $inputQuantities[$sizeId] = ($inputQuantities[$sizeId] ?? 0) + $quantity;
                        }
                    });

                foreach ($availableQuantities as $sizeId => &$qty) {
                    $inputQty = $inputQuantities[$sizeId] ?? 0;
                    $qty = max(0, $qty - $inputQty);
                }

                $result[$poNumber][] = [
                    'combination_id' => $pc->id,
                    'style' => $pc->style->name,
                    'color' => $pc->color->name,
                    'available_quantities' => $availableQuantities,
                    'order_quantities' => $orderData->order_quantities ?? [],
                    'size_ids' => array_keys($availableQuantities)
                ];
            }
        }

        return response()->json($result);
    }


    // public function inputBalanceReport(Request $request)
    // {
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $balanceData = [];

    //     // Get filter parameters
    //     $poNumbers = $request->input('po_number', []);
    //     $startDate = $request->input('start_date');
    //     $endDate = $request->input('end_date');

    //     // Get all PO numbers that have data
    //     $allPoNumbers = array_unique(array_merge(
    //         CuttingData::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //         PrintReceiveData::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //         SublimationPrintReceive::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //         LineInputData::distinct()->pluck('po_number')->filter()->values()->toArray()
    //     ));

    //     // Filter PO numbers if specified
    //     if (!empty($poNumbers)) {
    //         $allPoNumbers = array_intersect($allPoNumbers, $poNumbers);
    //     }

    //     foreach ($allPoNumbers as $poNumber) {
    //         // Initialize PO data
    //         $balanceData[$poNumber] = [
    //             'po_number' => $poNumber,
    //             'sizes' => [],
    //             'total_available' => 0,
    //             'total_input' => 0,
    //             'total_waste' => 0,
    //             'total_balance' => 0,
    //         ];

    //         // Initialize size data
    //         foreach ($allSizes as $size) {
    //             $balanceData[$poNumber]['sizes'][$size->id] = [
    //                 'name' => $size->name,
    //                 'available' => 0,
    //                 'input' => 0,
    //                 'waste' => 0,
    //                 'balance' => 0
    //             ];
    //         }

    //         // Calculate source quantities for this PO (across all product combinations)
    //         $sourceQuantities = [];

    //         // Cutting data
    //         $cuttingQuery = CuttingData::where('po_number', 'like', '%' . $poNumber . '%');
    //         if ($startDate && $endDate) {
    //             $cuttingQuery->whereBetween('created_at', [$startDate, $endDate]);
    //         }
    //         $cuttingData = $cuttingQuery->get();

    //         //null check and non-null continue
    //        if($cuttingData->isNotEmpty()) {

    //         foreach ($cuttingData as $item) {
    //             foreach ($item->cut_quantities as $sizeId => $quantity) {
    //                 $sourceQuantities[$sizeId] = ($sourceQuantities[$sizeId] ?? 0) + $quantity;
    //             }
    //         }
    //     }




    //         // Print data
    //         $printQuery = PrintReceiveData::where('po_number', 'like', '%' . $poNumber . '%');
    //         if ($startDate && $endDate) {
    //             $printQuery->whereBetween('date', [$startDate, $endDate]);
    //         }
    //         $printData = $printQuery->get();

    //         if($printData->isNotEmpty()) {

    //         foreach ($printData as $item) {
    //             foreach ($item->receive_quantities as $sizeId => $quantity) {
    //                 $sourceQuantities[$sizeId] = ($sourceQuantities[$sizeId] ?? 0) + $quantity;
    //             }
    //         }
    //     }

    //         // Sublimation data
    //         $sublimationQuery = SublimationPrintReceive::where('po_number', 'like', '%' . $poNumber . '%');
    //         if ($startDate && $endDate) {
    //             $sublimationQuery->whereBetween('date', [$startDate, $endDate]);
    //         }
    //         $sublimationData = $sublimationQuery->get();

    //         if($sublimationData->isNotEmpty()) {

    //         foreach ($sublimationData as $item) {
    //             foreach ($item->receive_quantities as $sizeId => $quantity) {
    //                 $sourceQuantities[$sizeId] = ($sourceQuantities[$sizeId] ?? 0) + $quantity;
    //             }
    //         }
    //     }

    //         // Calculate input quantities for this PO
    //         $inputQuery = LineInputData::where('po_number', 'like', '%' . $poNumber . '%');
    //         if ($startDate && $endDate) {
    //             $inputQuery->whereBetween('date', [$startDate, $endDate]);
    //         }
    //         $inputData = $inputQuery->get();
    //         if($inputData->isNotEmpty()) {


    //         $inputQuantities = [];
    //         $wasteQuantities = [];

    //         foreach ($inputData as $item) {
    //             foreach ($item->input_quantities as $sizeId => $quantity) {
    //                 $inputQuantities[$sizeId] = ($inputQuantities[$sizeId] ?? 0) + $quantity;
    //             }
    //             foreach ($item->input_waste_quantities ?? [] as $sizeId => $quantity) {
    //                 $wasteQuantities[$sizeId] = ($wasteQuantities[$sizeId] ?? 0) + $quantity;
    //             }

    //         }
    //     }

    //         // Populate the balance data
    //         foreach ($allSizes as $size) {
    //             $available = $sourceQuantities[$size->id] ?? 0;
    //             $input = $inputQuantities[$size->id] ?? 0;
    //             $waste = $wasteQuantities[$size->id] ?? 0;
    //             $balance = $available - $input - $waste;

    //             $balanceData[$poNumber]['sizes'][$size->id] = [
    //                 'name' => $size->name,
    //                 'available' => $available,
    //                 'input' => $input,
    //                 'waste' => $waste,
    //                 'balance' => $balance
    //             ];

    //             $balanceData[$poNumber]['total_available'] += $available;
    //             $balanceData[$poNumber]['total_input'] += $input;
    //             $balanceData[$poNumber]['total_waste'] += $waste;
    //             $balanceData[$poNumber]['total_balance'] += $balance;
    //         }

    //         // Remove PO if no data
    //         if (
    //             $balanceData[$poNumber]['total_available'] == 0 &&
    //             $balanceData[$poNumber]['total_input'] == 0 &&
    //             $balanceData[$poNumber]['total_waste'] == 0
    //         ) {
    //             unset($balanceData[$poNumber]);
    //         }
    //     }

    //     // Get filter options
    //     $distinctPoNumbers = array_unique(array_merge(
    //         CuttingData::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //         PrintReceiveData::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //         SublimationPrintReceive::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //         LineInputData::distinct()->pluck('po_number')->filter()->values()->toArray()
    //     ));
    //     sort($distinctPoNumbers);

    //     return view('backend.library.line_input_data.reports.input_balance', [
    //         'balanceData' => array_values($balanceData),
    //         'allSizes' => $allSizes,
    //         'distinctPoNumbers' => $distinctPoNumbers
    //     ]);
    // }


    // public function inputBalanceReport(Request $request)
    // {
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $balanceData = [];

    //     // Get filter parameters
    //     $poNumbers = $request->input('po_number', []);
    //     $startDate = $request->input('start_date');
    //     $endDate = $request->input('end_date');

    //     // Get all PO numbers that have data
    //     $allPoNumbers = array_unique(array_merge(
    //         CuttingData::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //         PrintReceiveData::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //         SublimationPrintReceive::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //         LineInputData::distinct()->pluck('po_number')->filter()->values()->toArray()
    //     ));

    //     // Filter PO numbers if specified
    //     if (!empty($poNumbers)) {
    //         $allPoNumbers = array_intersect($allPoNumbers, $poNumbers);
    //     }

    //     foreach ($allPoNumbers as $poNumber) {
    //         // Initialize PO data
    //         $balanceData[$poNumber] = [
    //             'po_number' => $poNumber,
    //             'sizes' => [],
    //             'total_available' => 0,
    //             'total_input' => 0,
    //             'total_waste' => 0,
    //             'total_balance' => 0,
    //         ];

    //         // Initialize size data
    //         foreach ($allSizes as $size) {
    //             $balanceData[$poNumber]['sizes'][$size->id] = [
    //                 'name' => $size->name,
    //                 'available' => 0,
    //                 'input' => 0,
    //                 'waste' => 0,
    //                 'balance' => 0
    //             ];
    //         }

    //         // Calculate source quantities for this PO (across all product combinations)
    //         $sourceQuantities = [];

    //         // Cutting data
    //         $cuttingQuery = CuttingData::where('po_number', 'like', '%' . $poNumber . '%');
    //         if ($startDate && $endDate) {
    //             $cuttingQuery->whereBetween('created_at', [$startDate, $endDate]);
    //         }
    //         $cuttingData = $cuttingQuery->get();

    //         if ($cuttingData->isNotEmpty()) {
    //             foreach ($cuttingData as $item) {
    //                 // Check if cut_quantities exists and is not null
    //                 if (!empty($item->cut_quantities)) {
    //                     foreach ($item->cut_quantities as $sizeId => $quantity) {
    //                         $sourceQuantities[$sizeId] = ($sourceQuantities[$sizeId] ?? 0) + $quantity;
    //                     }
    //                 }
    //             }
    //         }

    //         // Print data
    //         $printQuery = PrintReceiveData::where('po_number', 'like', '%' . $poNumber . '%');
    //         if ($startDate && $endDate) {
    //             $printQuery->whereBetween('date', [$startDate, $endDate]);
    //         }
    //         $printData = $printQuery->get();

    //         if ($printData->isNotEmpty()) {
    //             foreach ($printData as $item) {
    //                 // Check if receive_quantities exists and is not null
    //                 if (!empty($item->receive_quantities)) {
    //                     foreach ($item->receive_quantities as $sizeId => $quantity) {
    //                         $sourceQuantities[$sizeId] = ($sourceQuantities[$sizeId] ?? 0) + $quantity;
    //                     }
    //                 }
    //             }
    //         }

    //         // Sublimation data
    //         $sublimationQuery = SublimationPrintReceive::where('po_number', 'like', '%' . $poNumber . '%');
    //         if ($startDate && $endDate) {
    //             $sublimationQuery->whereBetween('date', [$startDate, $endDate]);
    //         }
    //         $sublimationData = $sublimationQuery->get();

    //         if ($sublimationData->isNotEmpty()) {
    //             foreach ($sublimationData as $item) {
    //                 // Check if receive_quantities exists and is not null
    //                 if (!empty($item->receive_quantities)) {
    //                     foreach ($item->receive_quantities as $sizeId => $quantity) {
    //                         $sourceQuantities[$sizeId] = ($sourceQuantities[$sizeId] ?? 0) + $quantity;
    //                     }
    //                 }
    //             }
    //         }

    //         // Calculate input quantities for this PO
    //         $inputQuery = LineInputData::where('po_number', 'like', '%' . $poNumber . '%');
    //         if ($startDate && $endDate) {
    //             $inputQuery->whereBetween('date', [$startDate, $endDate]);
    //         }
    //         $inputData = $inputQuery->get();

    //         // Initialize arrays outside the condition
    //         $inputQuantities = [];
    //         $wasteQuantities = [];

    //         if ($inputData->isNotEmpty()) {
    //             foreach ($inputData as $item) {
    //                 // Check if input_quantities exists and is not null
    //                 if (!empty($item->input_quantities)) {
    //                     foreach ($item->input_quantities as $sizeId => $quantity) {
    //                         $inputQuantities[$sizeId] = ($inputQuantities[$sizeId] ?? 0) + $quantity;
    //                     }
    //                 }

    //                 // Check if input_waste_quantities exists and is not null
    //                 if (!empty($item->input_waste_quantities)) {
    //                     foreach ($item->input_waste_quantities as $sizeId => $quantity) {
    //                         $wasteQuantities[$sizeId] = ($wasteQuantities[$sizeId] ?? 0) + $quantity;
    //                     }
    //                 }
    //             }
    //         }

    //         // Populate the balance data
    //         foreach ($allSizes as $size) {
    //             $available = $sourceQuantities[$size->id] ?? 0;
    //             $input = $inputQuantities[$size->id] ?? 0;
    //             $waste = $wasteQuantities[$size->id] ?? 0;
    //             $balance = $available - $input - $waste;

    //             $balanceData[$poNumber]['sizes'][$size->id] = [
    //                 'name' => $size->name,
    //                 'available' => $available,
    //                 'input' => $input,
    //                 'waste' => $waste,
    //                 'balance' => $balance
    //             ];

    //             $balanceData[$poNumber]['total_available'] += $available;
    //             $balanceData[$poNumber]['total_input'] += $input;
    //             $balanceData[$poNumber]['total_waste'] += $waste;
    //             $balanceData[$poNumber]['total_balance'] += $balance;
    //         }

    //         // Remove PO if no data
    //         if (
    //             $balanceData[$poNumber]['total_available'] == 0 &&
    //             $balanceData[$poNumber]['total_input'] == 0 &&
    //             $balanceData[$poNumber]['total_waste'] == 0
    //         ) {
    //             unset($balanceData[$poNumber]);
    //         }
    //     }

    //     // Get filter options
    //     $distinctPoNumbers = array_unique(array_merge(
    //         CuttingData::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //         PrintReceiveData::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //         SublimationPrintReceive::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //         LineInputData::distinct()->pluck('po_number')->filter()->values()->toArray()
    //     ));
    //     sort($distinctPoNumbers);

    //     return view('backend.library.line_input_data.reports.input_balance', [
    //         'balanceData' => array_values($balanceData),
    //         'allSizes' => $allSizes,
    //         'distinctPoNumbers' => $distinctPoNumbers
    //     ]);
    // }

    public function totalInputReport(Request $request)
    {
        $query = LineInputData::with('productCombination.style', 'productCombination.color');

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

        $lineInputData = $query->get();
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $reportData = [];

        // Initialize totals
        $totalInputBySize = array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0);
        $totalWasteBySize = array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0);
        $grandTotalInput = 0;
        $grandTotalWaste = 0;

        foreach ($lineInputData as $data) {
            $style = $data->productCombination->style->name;
            $color = $data->productCombination->color->name;
            $key = $style . '-' . $color;

            if (!isset($reportData[$key])) {
                $reportData[$key] = [
                    'style' => $style,
                    'color' => $color,
                    'sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
                    'waste_sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
                    'total' => 0,
                    'total_waste' => 0
                ];
            }

            foreach ($data->input_quantities as $sizeId => $qty) {
                $size = Size::find($sizeId);
                if ($size) {
                    $normalized = strtolower($size->name);
                    if (array_key_exists($normalized, $reportData[$key]['sizes'])) {
                        $reportData[$key]['sizes'][$normalized] += $qty;
                        $totalInputBySize[$normalized] += $qty;
                    }
                }
            }
            $reportData[$key]['total'] += $data->total_input_quantity;
            $grandTotalInput += $data->total_input_quantity;

            if ($data->input_waste_quantities) {
                foreach ($data->input_waste_quantities as $sizeId => $qty) {
                    $size = Size::find($sizeId);
                    if ($size) {
                        $normalized = strtolower($size->name);
                        if (array_key_exists($normalized, $reportData[$key]['waste_sizes'])) {
                            $reportData[$key]['waste_sizes'][$normalized] += $qty;
                            $totalWasteBySize[$normalized] += $qty;
                        }
                    }
                }
            }
            $reportData[$key]['total_waste'] += $data->total_input_waste_quantity ?? 0;
            $grandTotalWaste += $data->total_input_waste_quantity ?? 0;
        }

        // Get filter options
        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = LineInputData::distinct()->pluck('po_number')->filter()->values();

        return view('backend.library.line_input_data.reports.total_input', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes,
            'totalInputBySize' => $totalInputBySize,
            'totalWasteBySize' => $totalWasteBySize,
            'grandTotalInput' => $grandTotalInput,
            'grandTotalWaste' => $grandTotalWaste,
            'allStyles' => $allStyles,
            'allColors' => $allColors,
            'distinctPoNumbers' => $distinctPoNumbers
        ]);
    }



    // public function totalInputReport(Request $request)
    // {
    //     $query = LineInputData::with('productCombination.style', 'productCombination.color');

    //     if ($request->filled('start_date') && $request->filled('end_date')) {
    //         $query->whereBetween('date', [$request->start_date, $request->end_date]);
    //     }

    //     $lineInputData = $query->get();
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $reportData = [];

    //     // Initialize totals
    //     $totalInputBySize = array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0);
    //     $totalWasteBySize = array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0);
    //     $grandTotalInput = 0;
    //     $grandTotalWaste = 0;

    //     foreach ($lineInputData as $data) {
    //         $style = $data->productCombination->style->name;
    //         $color = $data->productCombination->color->name;
    //         $key = $style . '-' . $color;

    //         if (!isset($reportData[$key])) {
    //             $reportData[$key] = [
    //                 'style' => $style,
    //                 'color' => $color,
    //                 'sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
    //                 'waste_sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
    //                 'total' => 0,
    //                 'total_waste' => 0
    //             ];
    //         }

    //         foreach ($data->input_quantities as $sizeId => $qty) {
    //             $size = Size::find($sizeId);
    //             if ($size) {
    //                 $normalized = strtolower($size->name);
    //                 if (array_key_exists($normalized, $reportData[$key]['sizes'])) {
    //                     $reportData[$key]['sizes'][$normalized] += $qty;
    //                     $totalInputBySize[$normalized] += $qty;
    //                 }
    //             }
    //         }
    //         $reportData[$key]['total'] += $data->total_input_quantity;
    //         $grandTotalInput += $data->total_input_quantity;

    //         if ($data->input_waste_quantities) {
    //             foreach ($data->input_waste_quantities as $sizeId => $qty) {
    //                 $size = Size::find($sizeId);
    //                 if ($size) {
    //                     $normalized = strtolower($size->name);
    //                     if (array_key_exists($normalized, $reportData[$key]['waste_sizes'])) {
    //                         $reportData[$key]['waste_sizes'][$normalized] += $qty;
    //                         $totalWasteBySize[$normalized] += $qty;
    //                     }
    //                 }
    //             }
    //         }
    //         $reportData[$key]['total_waste'] += $data->total_input_waste_quantity ?? 0;
    //         $grandTotalWaste += $data->total_input_waste_quantity ?? 0;
    //     }

    //     return view('backend.library.line_input_data.reports.total_input', [
    //         'reportData' => array_values($reportData),
    //         'allSizes' => $allSizes,
    //         'totalInputBySize' => $totalInputBySize,
    //         'totalWasteBySize' => $totalWasteBySize,
    //         'grandTotalInput' => $grandTotalInput,
    //         'grandTotalWaste' => $grandTotalWaste
    //     ]);
    // }

    // public function inputBalanceReport(Request $request)
    // {
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $balanceData = [];

    //     $productCombinations = ProductCombination::whereHas('cuttingData')
    //         ->orWhereHas('printReceives')
    //         ->orWhereHas('sublimationPrintReceives')
    //         ->with('style', 'color')
    //         ->get();

    //     foreach ($productCombinations as $pc) {
    //         $style = $pc->style->name;
    //         $color = $pc->color->name;
    //         $key = $style . '-' . $color;

    //         // Initialize with all sizes
    //         $balanceData[$key] = [
    //             'style' => $style,
    //             'color' => $color,
    //             'sizes' => [],
    //             'total_available' => 0,
    //             'total_input' => 0,
    //             'total_waste' => 0,
    //             'total_balance' => 0,
    //         ];

    //         // Initialize size data for all sizes
    //         foreach ($allSizes as $size) {
    //             $balanceData[$key]['sizes'][$size->id] = [
    //                 'name' => $size->name,
    //                 'available' => 0,
    //                 'input' => 0,
    //                 'waste' => 0,
    //                 'balance' => 0
    //             ];
    //         }

    //         // Get available quantities based on product combination type
    //         if ($pc->print_embroidery && !$pc->sublimation_print) {
    //             // Only print_embroidery is true - from PrintReceiveData
    //             $receiveQuantities = PrintReceiveData::where('product_combination_id', $pc->id)
    //                 ->get()
    //                 ->flatMap(function ($item) {
    //                     return $item->receive_quantities;
    //                 })
    //                 ->groupBy(function ($value, $key) {
    //                     return $key; // Use size ID as key
    //                 })
    //                 ->map(function ($group) {
    //                     return $group->sum();
    //                 })
    //                 ->toArray();

    //             foreach ($receiveQuantities as $sizeId => $qty) {
    //                 if (isset($balanceData[$key]['sizes'][$sizeId])) {
    //                     $balanceData[$key]['sizes'][$sizeId]['available'] = $qty;
    //                     $balanceData[$key]['total_available'] += $qty;
    //                 }
    //             }
    //         } elseif (!$pc->print_embroidery && $pc->sublimation_print) {
    //             // Only sublimation_print is true - from SublimationPrintReceive
    //             $receiveQuantities = SublimationPrintReceive::where('product_combination_id', $pc->id)
    //                 ->get()
    //                 ->flatMap(function ($item) {
    //                     return $item->receive_quantities;
    //                 })
    //                 ->groupBy(function ($value, $key) {
    //                     return $key; // Use size ID as key
    //                 })
    //                 ->map(function ($group) {
    //                     return $group->sum();
    //                 })
    //                 ->toArray();

    //             foreach ($receiveQuantities as $sizeId => $qty) {
    //                 if (isset($balanceData[$key]['sizes'][$sizeId])) {
    //                     $balanceData[$key]['sizes'][$sizeId]['available'] = $qty;
    //                     $balanceData[$key]['total_available'] += $qty;
    //                 }
    //             }
    //         } elseif ($pc->print_embroidery && $pc->sublimation_print) {
    //             // Both are true - from PrintReceiveData
    //             $receiveQuantities = PrintReceiveData::where('product_combination_id', $pc->id)
    //                 ->get()
    //                 ->flatMap(function ($item) {
    //                     return $item->receive_quantities;
    //                 })
    //                 ->groupBy(function ($value, $key) {
    //                     return $key; // Use size ID as key
    //                 })
    //                 ->map(function ($group) {
    //                     return $group->sum();
    //                 })
    //                 ->toArray();

    //             foreach ($receiveQuantities as $sizeId => $qty) {
    //                 if (isset($balanceData[$key]['sizes'][$sizeId])) {
    //                     $balanceData[$key]['sizes'][$sizeId]['available'] = $qty;
    //                     $balanceData[$key]['total_available'] += $qty;
    //                 }
    //             }
    //         } else {
    //             // Both are false - from CuttingData
    //             $cutQuantities = CuttingData::where('product_combination_id', $pc->id)
    //                 ->get()
    //                 ->flatMap(function ($item) {
    //                     return $item->cut_quantities;
    //                 })
    //                 ->groupBy(function ($value, $key) {
    //                     return $key; // Use size ID as key
    //                 })
    //                 ->map(function ($group) {
    //                     return $group->sum();
    //                 })
    //                 ->toArray();

    //             foreach ($cutQuantities as $sizeId => $qty) {
    //                 if (isset($balanceData[$key]['sizes'][$sizeId])) {
    //                     $balanceData[$key]['sizes'][$sizeId]['available'] = $qty;
    //                     $balanceData[$key]['total_available'] += $qty;
    //                 }
    //             }
    //         }

    //         // Get input quantities
    //         $inputQuantities = LineInputData::where('product_combination_id', $pc->id)
    //             ->get()
    //             ->flatMap(function ($item) {
    //                 return $item->input_quantities;
    //             })
    //             ->groupBy(function ($value, $key) {
    //                 return $key; // Use size ID as key
    //             })
    //             ->map(function ($group) {
    //                 return $group->sum();
    //             })
    //             ->toArray();

    //         foreach ($inputQuantities as $sizeId => $qty) {
    //             if (isset($balanceData[$key]['sizes'][$sizeId])) {
    //                 $balanceData[$key]['sizes'][$sizeId]['input'] = $qty;
    //                 $balanceData[$key]['total_input'] += $qty;
    //             }
    //         }

    //         // Get waste quantities
    //         $wasteQuantities = LineInputData::where('product_combination_id', $pc->id)
    //             ->get()
    //             ->flatMap(function ($item) {
    //                 return $item->input_waste_quantities ?? [];
    //             })
    //             ->groupBy(function ($value, $key) {
    //                 return $key; // Use size ID as key
    //             })
    //             ->map(function ($group) {
    //                 return $group->sum();
    //             })
    //             ->toArray();

    //         foreach ($wasteQuantities as $sizeId => $qty) {
    //             if (isset($balanceData[$key]['sizes'][$sizeId])) {
    //                 $balanceData[$key]['sizes'][$sizeId]['waste'] = $qty;
    //                 $balanceData[$key]['total_waste'] += $qty;
    //             }
    //         }

    //         // Calculate balance for each size
    //         foreach ($balanceData[$key]['sizes'] as $sizeId => &$sizeData) {
    //             $sizeData['balance'] = $sizeData['available'] - $sizeData['input'] - $sizeData['waste'];
    //         }
    //         unset($sizeData);

    //         $balanceData[$key]['total_balance'] = $balanceData[$key]['total_available'] - $balanceData[$key]['total_input'] - $balanceData[$key]['total_waste'];
    //     }



    //     return view('backend.library.line_input_data.reports.input_balance', [
    //         'balanceData' => array_values($balanceData),
    //         'allSizes' => $allSizes
    //     ]);
    // }



    public function edit(LineInputData $lineInputDatum)
    {
        // Eager load necessary relationships for the current lineInputDatum
        $lineInputDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');

        // Get all active sizes, ordered by id
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Initialize to store the final available quantities for the current combination
        $availableQuantitiesForCurrentCombination = [];

        $pc = $lineInputDatum->productCombination;
        $poNumber = $lineInputDatum->po_number;

        if ($pc) {
            // Step 1: Get total quantities from the initial source (Cutting, Print, Sublimation)
            $initialSourceQuantities = [];

            // Determine the source(s) for available quantities based on product combination type
            if ($pc->print_embroidery && !$pc->sublimation_print) {
                // Print/Embroidery
                PrintReceiveData::where('product_combination_id', $pc->id)
                    ->where('po_number', $poNumber)
                    ->get()
                    ->each(function ($item) use (&$initialSourceQuantities) {
                        foreach ($item->receive_quantities ?? [] as $sizeId => $quantity) {
                            $initialSourceQuantities[$sizeId] = ($initialSourceQuantities[$sizeId] ?? 0) + $quantity;
                        }
                    });
            } elseif (!$pc->print_embroidery && $pc->sublimation_print) {
                // Sublimation
                SublimationPrintReceive::where('product_combination_id', $pc->id)
                    ->where('po_number', $poNumber)
                    ->get()
                    ->each(function ($item) use (&$initialSourceQuantities) {
                        foreach ($item->sublimation_print_receive_quantities ?? [] as $sizeId => $quantity) {
                            $initialSourceQuantities[$sizeId] = ($initialSourceQuantities[$sizeId] ?? 0) + $quantity;
                        }
                    });
            } elseif ($pc->print_embroidery && $pc->sublimation_print) {
                // Both print and sublimation
                PrintReceiveData::where('product_combination_id', $pc->id)
                    ->where('po_number', $poNumber)
                    ->get()
                    ->each(function ($item) use (&$initialSourceQuantities) {
                        foreach ($item->receive_quantities ?? [] as $sizeId => $quantity) {
                            $initialSourceQuantities[$sizeId] = ($initialSourceQuantities[$sizeId] ?? 0) + $quantity;
                        }
                    });

                SublimationPrintReceive::where('product_combination_id', $pc->id)
                    ->where('po_number', $poNumber)
                    ->get()
                    ->each(function ($item) use (&$initialSourceQuantities) {
                        foreach ($item->sublimation_print_receive_quantities ?? [] as $sizeId => $quantity) {
                            $initialSourceQuantities[$sizeId] = ($initialSourceQuantities[$sizeId] ?? 0) + $quantity;
                        }
                    });
            } else {
                // Cutting only (default or if no print/sublimation applies)
                CuttingData::where('product_combination_id', $pc->id)
                    ->where('po_number', $poNumber)
                    ->get()
                    ->each(function ($item) use (&$initialSourceQuantities) {
                        foreach ($item->cut_quantities ?? [] as $sizeId => $quantity) {
                            $initialSourceQuantities[$sizeId] = ($initialSourceQuantities[$sizeId] ?? 0) + $quantity;
                        }
                    });
            }

            // Step 2: Subtract quantities from ALL LineInputData entries for this combination and PO,
            // EXCEPT the current lineInputDatum being edited.
            // This gives us the "net available" quantity before considering the current lineInputDatum's own input.
            $alreadyInputQuantitiesFromOthers = [];
            LineInputData::where('product_combination_id', $pc->id)
                ->where('po_number', $poNumber)
                ->where('id', '!=', $lineInputDatum->id) // Exclude the current entry
                ->get()
                ->each(function ($item) use (&$alreadyInputQuantitiesFromOthers) {
                    foreach ($item->input_quantities ?? [] as $sizeId => $quantity) {
                        $alreadyInputQuantitiesFromOthers[$sizeId] = ($alreadyInputQuantitiesFromOthers[$sizeId] ?? 0) + $quantity;
                    }
                });

            // Calculate the max available quantities after excluding other LineInputData entries
            foreach ($initialSourceQuantities as $sizeId => $qty) {
                $inputQtyFromOthers = $alreadyInputQuantitiesFromOthers[$sizeId] ?? 0;
                $availableQuantitiesForCurrentCombination[$sizeId] = max(0, $qty - $inputQtyFromOthers);
            }
        }

        // --- Get Order Quantities for the current lineInputDatum's product combination and PO ---
        $orderQuantities = [];
        $orderData = OrderData::where('product_combination_id', $lineInputDatum->product_combination_id)
            ->where('po_number', $lineInputDatum->po_number)
            ->first();

        if ($orderData && $orderData->order_quantities) {
            foreach ($orderData->order_quantities as $sizeId => $qty) {
                $orderQuantities[$sizeId] = ($orderQuantities[$sizeId] ?? 0) + $qty;
            }
        }

        // --- Get Cutting Quantities for the current lineInputDatum's product combination and PO ---
        // This is a new requirement to display cutting quantity
        $cuttingQuantities = [];
        $cuttingData = CuttingData::where('product_combination_id', $lineInputDatum->product_combination_id)
            ->where('po_number', $lineInputDatum->po_number)
            ->get();

        foreach ($cuttingData as $item) {
            foreach ($item->cut_quantities ?? [] as $sizeId => $qty) {
                $cuttingQuantities[$sizeId] = ($cuttingQuantities[$sizeId] ?? 0) + $qty;
            }
        }

        // Prepare size data for the view
        $sizeData = [];
        foreach ($allSizes as $size) {
            $currentInputQty = $lineInputDatum->input_quantities[$size->id] ?? 0;
            $currentWasteQty = $lineInputDatum->input_waste_quantities[$size->id] ?? 0;

            $maxAvailableAfterOthers = $availableQuantitiesForCurrentCombination[$size->id] ?? 0;
            $orderQtyForSize = $orderQuantities[$size->id] ?? 0;
            $cuttingQtyForSize = $cuttingQuantities[$size->id] ?? 0;

            // The maximum allowed input for this specific size now is the currently available quantity
            // (after others' inputs are subtracted) PLUS the quantity already entered in THIS specific lineInputDatum.
            // This allows the user to reduce or keep their current input without being limited
            // by what others have input *after* their initial entry.
            $maxAllowedForInputField = $maxAvailableAfterOthers + $currentInputQty;

            $sizeData[] = [
                'id' => $size->id,
                'name' => $size->name,
                'current_input_quantity' => $currentInputQty,
                'current_waste_quantity' => $currentWasteQty,
                'max_available' => $maxAvailableAfterOthers, // Max available from previous stages, excluding *other* LineInputData entries
                'max_allowed_for_input_field' => $maxAllowedForInputField,
                'order_quantity' => $orderQtyForSize,
                'cutting_quantity' => $cuttingQtyForSize, // Added cutting quantity
            ];
        }

        // Log::info('Size Data for View:', $sizeData); // For debugging

        return view('backend.library.line_input_data.edit', compact('lineInputDatum', 'sizeData'));
    }

    public function inputBalanceReport(Request $request)
    {
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $balanceData = [];

        // Fetch filter data for dropdowns
        $allStyles = Style::orderBy('name')->get();
        $allColors = Color::orderBy('name')->get();

        // Get distinct PO numbers from relevant tables
        $distinctPoNumbers = [];
        $poNumbersFromCutting = CuttingData::distinct()->pluck('po_number')->filter()->toArray();
        $poNumbersFromPrint = PrintReceiveData::distinct()->pluck('po_number')->filter()->toArray();
        $poNumbersFromSublimation = SublimationPrintReceive::distinct()->pluck('po_number')->filter()->toArray();
        $poNumbersFromLineInput = LineInputData::distinct()->pluck('po_number')->filter()->toArray();

        $distinctPoNumbers = array_unique(array_merge(
            $poNumbersFromCutting,
            $poNumbersFromPrint,
            $poNumbersFromSublimation,
            $poNumbersFromLineInput
        ));
        sort($distinctPoNumbers); // Sort them alphabetically or numerically

        // Build the query for product combinations based on filters
        $productCombinationsQuery = ProductCombination::query();

        // Apply style filter
        if ($request->filled('style_id')) {
            $styleIds = (array) $request->input('style_id');
            $productCombinationsQuery->whereIn('style_id', $styleIds);
        }

        // Apply color filter
        if ($request->filled('color_id')) {
            $colorIds = (array) $request->input('color_id');
            $productCombinationsQuery->whereIn('color_id', $colorIds);
        }

        // Apply search filter (style, color, or PO number in related data)
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $productCombinationsQuery->where(function ($query) use ($searchTerm) {
                $query->whereHas('style', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', $searchTerm);
                })
                    ->orWhereHas('color', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', $searchTerm);
                    });
                // Add PO search here if needed, but it's handled by specific PO filter below
            });
        }

        // Ensure we only get combinations that actually have data in at least one source
        $productCombinationsQuery->where(function ($query) {
            $query->whereHas('cuttingData')
                ->orWhereHas('printReceives')
                ->orWhereHas('sublimationPrintReceives')
                ->orWhereHas('lineInputData'); // Also consider if it has input data
        });

        $productCombinations = $productCombinationsQuery->with('style', 'color')->get();

        // Collect all unique POs that are relevant to the filtered product combinations
        // and also match the PO filter from the request
        $relevantPoNumbers = [];
        $filteredPoNumbers = $request->filled('po_number') ? (array) $request->input('po_number') : [];

        foreach ($productCombinations as $pc) {
            $poNumbersForPc = [];

            // Collect POs from cutting data
            CuttingData::where('product_combination_id', $pc->id)
                ->when(!empty($filteredPoNumbers), function ($query) use ($filteredPoNumbers) {
                    $query->whereIn('po_number', $filteredPoNumbers);
                })
                ->pluck('po_number')
                ->filter()
                ->each(function ($po) use (&$poNumbersForPc) {
                    $poNumbersForPc[] = $po;
                });

            // Collect POs from print receives
            PrintReceiveData::where('product_combination_id', $pc->id)
                ->when(!empty($filteredPoNumbers), function ($query) use ($filteredPoNumbers) {
                    $query->whereIn('po_number', $filteredPoNumbers);
                })
                ->pluck('po_number')
                ->filter()
                ->each(function ($po) use (&$poNumbersForPc) {
                    $poNumbersForPc[] = $po;
                });

            // Collect POs from sublimation receives
            SublimationPrintReceive::where('product_combination_id', $pc->id)
                ->when(!empty($filteredPoNumbers), function ($query) use ($filteredPoNumbers) {
                    $query->whereIn('po_number', $filteredPoNumbers);
                })
                ->pluck('po_number')
                ->filter()
                ->each(function ($po) use (&$poNumbersForPc) {
                    $poNumbersForPc[] = $po;
                });

            // Collect POs from line input data
            LineInputData::where('product_combination_id', $pc->id)
                ->when(!empty($filteredPoNumbers), function ($query) use ($filteredPoNumbers) {
                    $query->whereIn('po_number', $filteredPoNumbers);
                })
                ->pluck('po_number')
                ->filter()
                ->each(function ($po) use (&$poNumbersForPc) {
                    $poNumbersForPc[] = $po;
                });

            // Add unique POs for this product combination to the global relevant list
            $relevantPoNumbers[$pc->id] = array_unique($poNumbersForPc);
        }

        // If specific PO numbers are requested and no relevant POs were found,
        // or if the search criteria yielded no product combinations, return empty.
        if ((!empty($filteredPoNumbers) && empty(array_filter($relevantPoNumbers))) || $productCombinations->isEmpty()) {
            return view('backend.library.line_input_data.reports.input_balance', [
                'balanceData' => [],
                'allSizes' => $allSizes,
                'allStyles' => $allStyles,
                'allColors' => $allColors,
                'distinctPoNumbers' => $distinctPoNumbers,
            ]);
        }


        foreach ($productCombinations as $pc) {
            if (!$pc->style || !$pc->color) {
                continue; // Skip if style or color relationship is missing
            }

            // Iterate through each relevant PO number for this product combination
            foreach ($relevantPoNumbers[$pc->id] as $poNumber) {
                $key = $pc->id . '-' . $poNumber; // Unique key for pc_id and po_number

                $balanceData[$key] = [
                    'po_number' => $poNumber, // Added PO number
                    'style' => $pc->style->name,
                    'color' => $pc->color->name,
                    'sizes' => [],
                    'total_available' => 0,
                    'total_input' => 0,
                    'total_waste' => 0,
                    'total_balance' => 0,
                ];

                // Initialize size data for all sizes for the current PC-PO combination
                foreach ($allSizes as $size) {
                    $balanceData[$key]['sizes'][$size->id] = [
                        'name' => $size->name,
                        'available' => 0,
                        'input' => 0,
                        'waste' => 0,
                        'balance' => 0
                    ];
                }

                // Get AVAILABLE quantities based on product combination type and specific PO
                $availableQuantities = [];
                if ($pc->print_embroidery && !$pc->sublimation_print) {
                    // Only print_embroidery is true - from PrintReceiveData
                    PrintReceiveData::where('product_combination_id', $pc->id)
                        ->where('po_number', $poNumber)
                        ->get()
                        ->each(function ($item) use (&$availableQuantities) {
                            foreach ($item->receive_quantities ?? [] as $sizeId => $qty) {
                                $availableQuantities[$sizeId] = ($availableQuantities[$sizeId] ?? 0) + $qty;
                            }
                        });
                } elseif (!$pc->print_embroidery && $pc->sublimation_print) {
                    // Only sublimation_print is true - from SublimationPrintReceive
                    SublimationPrintReceive::where('product_combination_id', $pc->id)
                        ->where('po_number', $poNumber)
                        ->get()
                        ->each(function ($item) use (&$availableQuantities) {
                            foreach ($item->sublimation_print_receive_quantities ?? [] as $sizeId => $qty) {
                                $availableQuantities[$sizeId] = ($availableQuantities[$sizeId] ?? 0) + $qty;
                            }
                        });
                } elseif ($pc->print_embroidery && $pc->sublimation_print) {
                    // Both are true - sum from PrintReceiveData and SublimationPrintReceive
                    PrintReceiveData::where('product_combination_id', $pc->id)
                        ->where('po_number', $poNumber)
                        ->get()
                        ->each(function ($item) use (&$availableQuantities) {
                            foreach ($item->receive_quantities ?? [] as $sizeId => $qty) {
                                $availableQuantities[$sizeId] = ($availableQuantities[$sizeId] ?? 0) + $qty;
                            }
                        });
                    SublimationPrintReceive::where('product_combination_id', $pc->id)
                        ->where('po_number', $poNumber)
                        ->get()
                        ->each(function ($item) use (&$availableQuantities) {
                            foreach ($item->sublimation_print_receive_quantities ?? [] as $sizeId => $qty) {
                                $availableQuantities[$sizeId] = ($availableQuantities[$sizeId] ?? 0) + $qty;
                            }
                        });
                } else {
                    // Both are false - from CuttingData
                    CuttingData::where('product_combination_id', $pc->id)
                        ->where('po_number', $poNumber)
                        ->get()
                        ->each(function ($item) use (&$availableQuantities) {
                            foreach ($item->cut_quantities ?? [] as $sizeId => $qty) {
                                $availableQuantities[$sizeId] = ($availableQuantities[$sizeId] ?? 0) + $qty;
                            }
                        });
                }

                // Get INPUT quantities for the specific PO and date range
                $inputQuantities = [];
                $wasteQuantities = [];
                $lineInputs = LineInputData::where('product_combination_id', $pc->id)
                    ->where('po_number', $poNumber)
                    ->when($request->filled('start_date'), function ($query) use ($request) {
                        $query->whereDate('date', '>=', $request->input('start_date'));
                    })
                    ->when($request->filled('end_date'), function ($query) use ($request) {
                        $query->whereDate('date', '<=', $request->input('end_date'));
                    })
                    ->get();

                foreach ($lineInputs as $item) {
                    foreach ($item->input_quantities ?? [] as $sizeId => $qty) {
                        $inputQuantities[$sizeId] = ($inputQuantities[$sizeId] ?? 0) + $qty;
                    }
                    foreach ($item->input_waste_quantities ?? [] as $sizeId => $qty) {
                        $wasteQuantities[$sizeId] = ($wasteQuantities[$sizeId] ?? 0) + $qty;
                    }
                }

                // Update balance data for each size
                foreach ($allSizes as $size) {
                    $currentAvailable = $availableQuantities[$size->id] ?? 0;
                    $currentInput = $inputQuantities[$size->id] ?? 0;
                    $currentWaste = $wasteQuantities[$size->id] ?? 0;

                    $balanceData[$key]['sizes'][$size->id]['available'] = $currentAvailable;
                    $balanceData[$key]['sizes'][$size->id]['input'] = $currentInput;
                    $balanceData[$key]['sizes'][$size->id]['waste'] = $currentWaste; // Store waste per size
                    $balanceData[$key]['sizes'][$size->id]['balance'] = $currentAvailable - $currentInput - $currentWaste;

                    $balanceData[$key]['total_available'] += $currentAvailable;
                    $balanceData[$key]['total_input'] += $currentInput;
                    $balanceData[$key]['total_waste'] += $currentWaste; // Accumulate total waste
                }

                $balanceData[$key]['total_balance'] = $balanceData[$key]['total_available'] - $balanceData[$key]['total_input'] - $balanceData[$key]['total_waste'];
            }
        }

        // Remove entries where total_available and total_input are both zero,
        // unless there's a balance (which implies available was there then consumed)
        // Or if you want to show all combinations even if no input/available, remove this filter.
        $filteredBalanceData = array_filter($balanceData, function ($data) {
            return $data['total_available'] > 0 || $data['total_input'] > 0 || $data['total_waste'] > 0;
        });


        return view('backend.library.line_input_data.reports.input_balance', [
            'balanceData' => array_values($filteredBalanceData),
            'allSizes' => $allSizes,
            'allStyles' => $allStyles, // Pass to view for filters
            'allColors' => $allColors, // Pass to view for filters
            'distinctPoNumbers' => $distinctPoNumbers, // Pass to view for filters
        ]);
    }

}
