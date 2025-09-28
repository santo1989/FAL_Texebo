<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\CuttingData;
use App\Models\PrintReceiveData;
use App\Models\PrintSendData;
use App\Models\ProductCombination;
use App\Models\Size;
use App\Models\Style;
use App\Models\sublimationPrintReceive;
use App\Models\sublimationPrintSend;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrintSendDataController extends Controller
{

    public function index(Request $request)
    {
        $query = PrintSendData::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

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

        $printSendData = $query->orderBy('date', 'desc')->paginate(10);
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Get filter options
        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = PrintSendData::distinct()->pluck('po_number')->filter()->values();

        return view('backend.library.print_send_data.index', compact(
            'printSendData',
            'allSizes',
            'allStyles',
            'allColors',
            'distinctPoNumbers'
        ));
    }
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'po_number' => 'required|array',
            'po_number.*' => 'required|string',
            'old_order' => 'required|in:yes,no',
            'rows' => 'required|array',
            'rows.*.product_combination_id' => 'required|exists:product_combinations,id',
            'rows.*.po_number' => 'required|string', // Add validation for row-level PO number
            'rows.*.send_quantities.*' => 'nullable|integer|min:0',
            'rows.*.send_waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        try {
            DB::beginTransaction();
            $validRowsProcessed = 0;

            foreach ($request->rows as $row) {
                // Check if this row has any available quantities (not all N/A)
                $hasAvailableQuantities = false;
                $hasValidQuantities = false;

                // Check send quantities
                if (isset($row['send_quantities'])) {
                    foreach ($row['send_quantities'] as $quantity) {
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

                $sendQuantities = [];
                $wasteQuantities = [];
                $totalSendQuantity = 0;
                $totalWasteQuantity = 0;

                // Process send quantities
                if (isset($row['send_quantities'])) {
                    foreach ($row['send_quantities'] as $sizeId => $quantity) {
                        if ($quantity !== null && (int)$quantity > 0) {
                            $size = Size::find($sizeId);
                            if ($size) {
                                $sendQuantities[$size->id] = (int)$quantity;
                                $totalSendQuantity += (int)$quantity;
                            }
                        }
                    }
                }

                // Process waste quantities
                if (isset($row['send_waste_quantities'])) {
                    foreach ($row['send_waste_quantities'] as $sizeId => $quantity) {
                        if ($quantity !== null && (int)$quantity > 0) {
                            $size = Size::find($sizeId);
                            if ($size) {
                                $wasteQuantities[$size->id] = (int)$quantity;
                                $totalWasteQuantity += (int)$quantity;
                            }
                        }
                    }
                }

                // Only create a record if there's at least one valid send or waste quantity
                if (!empty($sendQuantities) || !empty($wasteQuantities)) {
                    PrintSendData::create([
                        'date' => $request->date,
                        'product_combination_id' => $row['product_combination_id'],
                        'po_number' => $row['po_number'], // Use the row-level PO number
                        'old_order' => $request->old_order,
                        'send_quantities' => $sendQuantities,
                        'total_send_quantity' => $totalSendQuantity,
                        'send_waste_quantities' => $wasteQuantities,
                        'total_send_waste_quantity' => $totalWasteQuantity,
                    ]);

                    $validRowsProcessed++;
                }
            }

            DB::commit();

            if ($validRowsProcessed > 0) {
                return redirect()->route('print_send_data.index')
                    ->with('message', 'Print/Embroidery Send data added successfully.');
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

    public function show(PrintSendData $printSendDatum)
    {
        $printSendDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        return view('backend.library.print_send_data.show', compact('printSendDatum', 'allSizes'));
    }

    public function edit(PrintSendData $printSendDatum)
    {
        $printSendDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');

        // Get valid sizes for this product combination
        $validSizeIds = $printSendDatum->productCombination->size_ids;
        $validSizes = Size::whereIn('id', $validSizeIds)
            ->where('is_active', 1)
            ->orderBy('id', 'asc')
            ->get();

        return view('backend.library.print_send_data.edit', compact('printSendDatum', 'validSizes'));
    }

    public function update(Request $request, PrintSendData $printSendDatum)
    {
        $request->validate([
            'date' => 'required|date',
            'send_quantities.*' => 'nullable|integer|min:0',
            'send_waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        try {
            $sendQuantities = [];
            $wasteQuantities = [];
            $totalSendQuantity = 0;
            $totalWasteQuantity = 0;

            // Process send quantities
            foreach ($request->send_quantities as $sizeId => $quantity) {
                // Ensure quantity is not null and greater than 0
                if ($quantity !== null && (int)$quantity > 0) {
                    $sendQuantities[$sizeId] = (int)$quantity;
                    $totalSendQuantity += (int)$quantity;
                }
            }

            // Process waste quantities
            foreach ($request->send_waste_quantities as $sizeId => $quantity) {
                // Ensure quantity is not null and greater than 0
                if ($quantity !== null && (int)$quantity > 0) {
                    $wasteQuantities[$sizeId] = (int)$quantity;
                    $totalWasteQuantity += (int)$quantity;
                }
            }

            $printSendDatum->update([
                'date' => $request->date,
                'send_quantities' => $sendQuantities,
                'total_send_quantity' => $totalSendQuantity,
                'send_waste_quantities' => $wasteQuantities,
                'total_send_waste_quantity' => $totalWasteQuantity,
            ]);

            return redirect()->route('print_send_data.index')
                ->withMessage('Print/Embroidery Send data updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(PrintSendData $printSendDatum)
    {
        $printSendDatum->delete();

        return redirect()->route('print_send_data.index')
            ->withMessage('Print/Embroidery Send data deleted successfully.');
    }

    public function create()
    {
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Get distinct PO numbers from CuttingData where print_embroidery is true
        $distinctPoNumbers = CuttingData::whereHas('productCombination', function ($query) {
            $query->where('print_embroidery', true);
        })
            ->distinct()
            ->pluck('po_number')
            ->filter()
            ->values();

        return view('backend.library.print_send_data.create', compact('distinctPoNumbers', 'allSizes'));
    }

    public function find(Request $request)
    {
        $poNumbers = $request->input('po_numbers', []);

        if (empty($poNumbers)) {
            return response()->json([]);
        }

        $result = [];

        foreach ($poNumbers as $poNumber) {
            $cuttingData = CuttingData::where('po_number', 'like', '%' . $poNumber . '%')
                ->with(['productCombination.style', 'productCombination.color', 'productCombination.size'])
                ->whereHas('productCombination', function ($query) {
                    $query->where('print_embroidery', true);
                })
                ->get();

            $processedCombinationsForPo = [];

            foreach ($cuttingData as $cutting) {
                if (!$cutting->productCombination) {
                    continue;
                }

                $combinationKey = $cutting->productCombination->id . '-' .
                    $cutting->productCombination->style->name . '-' .
                    $cutting->productCombination->color->name;

                if (in_array($combinationKey, $processedCombinationsForPo)) {
                    continue;
                }

                $processedCombinationsForPo[] = $combinationKey;

                $availableQuantities = $this->getAvailableSendQuantities($cutting->productCombination, $poNumber)->getData()->availableQuantities;

                $result[$poNumber][] = [
                    'combination_id' => $cutting->productCombination->id,
                    'style' => $cutting->productCombination->style->name,
                    'color' => $cutting->productCombination->color->name,
                    'available_quantities' => $availableQuantities,
                    'size_ids' => $cutting->productCombination->sizes->pluck('id')->toArray()
                ];
            }
        }

        return response()->json($result);
    }

    public function getAvailableSendQuantities(ProductCombination $productCombination, $poNumber = null)
    {
        $sizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $availableQuantities = [];
        $sizeIdToName = $sizes->pluck('name', 'id')->toArray();

        // Get sent quantities from PrintSendData
        $sentQuery = PrintSendData::where('product_combination_id', $productCombination->id);
        if ($poNumber) {
            $sentQuery->where('po_number', 'like', '%' . $poNumber . '%');
        }

        $sentQuantities = $sentQuery->get()
            ->pluck('send_quantities')
            ->reduce(function ($carry, $quantities) {
                foreach ($quantities as $sizeId => $qty) {
                    $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                }
                return $carry;
            }, []);

        if ($productCombination->sublimation_print) {
            // Case 1: sublimation_print is true, use SublimationPrintReceive quantities
            $sublimationQuery = SublimationPrintReceive::where('product_combination_id', $productCombination->id);
            if ($poNumber) {
                $sublimationQuery->where('po_number', 'like', '%' . $poNumber . '%');
            }

            $sublimationQuantities = $sublimationQuery->get()
                ->pluck('sublimation_print_receive_quantities')
                ->reduce(function ($carry, $quantities) {
                    foreach ($quantities as $sizeId => $qty) {
                        $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                    }
                    return $carry;
                }, []);

            // Calculate available quantities: SublimationPrintReceive - PrintSendData
            foreach ($sizes as $size) {
                $sublimation = $sublimationQuantities[$size->id] ?? 0;
                $sent = $sentQuantities[$size->id] ?? 0;
                $availableQuantities[$size->name] = max(0, $sublimation - $sent);
            }
        } else if ($productCombination->print_embroidery) {
            // Case 2: sublimation_print is false, print_embroidery is true, use CuttingData quantities
            $cutQuery = CuttingData::where('product_combination_id', $productCombination->id);
            if ($poNumber) {
                $cutQuery->where('po_number', 'like', '%' . $poNumber . '%');
            }

            $cutQuantities = $cutQuery->get()
                ->pluck('cut_quantities')
                ->reduce(function ($carry, $quantities) {
                    foreach ($quantities as $sizeId => $qty) {
                        $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                    }
                    return $carry;
                }, []);

            // Calculate available quantities: CuttingData - PrintSendData
            foreach ($sizes as $size) {
                $cut = $cutQuantities[$size->id] ?? 0;
                $sent = $sentQuantities[$size->id] ?? 0;
                $availableQuantities[$size->name] = max(0, $cut - $sent);
            }
        } else {
            // Case 3: neither sublimation_print nor print_embroidery is true, return zero quantities
            foreach ($sizes as $size) {
                $availableQuantities[$size->name] = 0;
            }
        }

        return response()->json([
            'availableQuantities' => $availableQuantities,
            'sizes' => $sizes
        ]);
    }

    // Helper method to check if sublimation_print is true
    private function isSublimationPrint($productCombinationId)
    {
        return ProductCombination::where('id', $productCombinationId)
            ->where('sublimation_print', true)
            ->exists();
    }


    // Update the getAvailableSendQuantities method to accept PO number filtering
    // public function getAvailableSendQuantities(ProductCombination $productCombination, $poNumber = null)
    // {
    //     $sizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $availableQuantities = [];

    //     // Base query for cut quantities
    //     $cutQuery = CuttingData::where('product_combination_id', $productCombination->id);

    //     // Filter by PO number if provided
    //     if ($poNumber) {
    //         $cutQuery->where('po_number', 'like', '%' . $poNumber . '%');
    //     }

    //     // Get cut quantities
    //     $cutQuantities = $cutQuery->get()
    //         ->pluck('cut_quantities')
    //         ->reduce(function ($carry, $quantities) {
    //             foreach ($quantities as $sizeId => $qty) {
    //                 $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
    //             }
    //             return $carry;
    //         }, []);
            

    //     // Base query for sent quantities
    //     $sentQuery = PrintSendData::where('product_combination_id', $productCombination->id);

    //     // Filter by PO number if provided
    //     if ($poNumber) {
    //         $sentQuery->where('po_number', 'like', '%' . $poNumber . '%');
    //     }

    //     // Sum already sent quantities per size
    //     $sentQuantities = $sentQuery->get()
    //         ->pluck('send_quantities')
    //         ->reduce(function ($carry, $quantities) {
    //             foreach ($quantities as $sizeId => $qty) {
    //                 $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
    //             }
    //             return $carry;
    //         }, []);

    //     // Create a mapping of size IDs to size names
    //     $sizeIdToName = [];
    //     foreach ($sizes as $size) {
    //         $sizeIdToName[$size->id] = $size->name;
    //     }

    //     // Calculate available quantities
    //     foreach ($sizes as $size) {
    //         $cut = $cutQuantities[$size->id] ?? 0;
    //         $sent = $sentQuantities[$size->id] ?? 0;
    //         $availableQuantities[$size->name] = max(0, $cut - $sent);
    //     }

    //     return response()->json([
    //         'availableQuantities' => $availableQuantities,
    //         'sizes' => $sizes
    //     ]);
    // }

    // Update the find method to use PO-filtered available quantities
    // public function find(Request $request)
    // {
    //     $poNumbers = $request->input('po_numbers', []);

    //     if (empty($poNumbers)) {
    //         return response()->json([]);
    //     }

    //     $result = [];
    //     $processedCombinations = []; // Track processed combinations

    //     foreach ($poNumbers as $poNumber) {
    //         // Get cutting data for the selected PO number with print_embroidery = true
    //         $cuttingData = CuttingData::where('po_number', 'like', '%' . $poNumber . '%')
    //             ->with(['productCombination.style', 'productCombination.color', 'productCombination.size'])
    //             ->whereHas('productCombination', function ($query) {
    //                 $query->where('print_embroidery', true);
    //             })
    //             ->get();

    //         foreach ($cuttingData as $cutting) {
    //             if (!$cutting->productCombination) {
    //                 continue;
    //             }

    //             // Create a unique key for this combination
    //             $combinationKey = $cutting->productCombination->id . '-' .
    //                 $cutting->productCombination->style->name . '-' .
    //                 $cutting->productCombination->color->name;

    //             // Skip if we've already processed this combination
    //             if (in_array($combinationKey, $processedCombinations)) {
    //                 continue;
    //             }

    //             // Mark this combination as processed
    //             $processedCombinations[] = $combinationKey;

    //             // Get available quantities filtered by PO number
    //             $availableQuantities = $this->getAvailableSendQuantities($cutting->productCombination, $poNumber)->getData()->availableQuantities;

    //             $result[$poNumber][] = [
    //                 'combination_id' => $cutting->productCombination->id,
    //                 'style' => $cutting->productCombination->style->name,
    //                 'color' => $cutting->productCombination->color->name,
    //                 'available_quantities' => $availableQuantities,
    //                 'size_ids' => $cutting->productCombination->sizes->pluck('id')->toArray()
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
    //     // Remove the global processed combinations array
    //     // $processedCombinations = []; 

    //     foreach ($poNumbers as $poNumber) {
    //         // Get cutting data for the selected PO number with print_embroidery = true 
    //         $cuttingData = CuttingData::where('po_number', 'like', '%' . $poNumber . '%')
    //             ->with(['productCombination.style', 'productCombination.color', 'productCombination.size'])
    //             ->whereHas('productCombination', function ($query) {
    //                 $query->where('print_embroidery', true);
                    
    //             })
    //             ->get();

    //         // Create a processed combinations array PER PO NUMBER
    //         $processedCombinationsForPo = [];

    //         foreach ($cuttingData as $cutting) {
    //             if (!$cutting->productCombination) {
    //                 continue;
    //             }

    //             // Create a unique key for this combination WITHIN THIS PO
    //             $combinationKey = $cutting->productCombination->id . '-' .
    //                 $cutting->productCombination->style->name . '-' .
    //                 $cutting->productCombination->color->name;

    //             // Skip if we've already processed this combination FOR THIS PO
    //             if (in_array($combinationKey, $processedCombinationsForPo)) {
    //                 continue;
    //             }

    //             // Mark this combination as processed FOR THIS PO
    //             $processedCombinationsForPo[] = $combinationKey;

    //             // Get available quantities filtered by PO number
    //             $availableQuantities = $this->getAvailableSendQuantities($cutting->productCombination, $poNumber)->getData()->availableQuantities;

    //             $result[$poNumber][] = [
    //                 'combination_id' => $cutting->productCombination->id,
    //                 'style' => $cutting->productCombination->style->name,
    //                 'color' => $cutting->productCombination->color->name,
    //                 'available_quantities' => $availableQuantities,
    //                 'size_ids' => $cutting->productCombination->sizes->pluck('id')->toArray()
    //             ];
    //         }
    //     }

    //     return response()->json($result);
    // }

    // public function available($product_combination_id)
    // {
    //     $sizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $sizeMap = [];
    //     foreach ($sizes as $size) {
    //         $sizeMap[strtolower($size->name)] = $size->id;
    //     }

    //     $cutQuantities = CuttingData::where('product_combination_id', $product_combination_id)
    //         ->get()
    //         ->flatMap(function ($record) use ($sizeMap) {
    //             $quantities = [];
    //             foreach ($record->cut_quantities as $sizeName => $quantity) {
    //                 $normalized = strtolower(trim($sizeName));
    //                 if (isset($sizeMap[$normalized])) {
    //                     $sizeId = $sizeMap[$normalized];
    //                     $quantities[$sizeId] = $quantity;
    //                 }
    //             }
    //             return $quantities;
    //         })
    //         ->groupBy(function ($item, $sizeId) {
    //             return $sizeId;
    //         })
    //         ->map->sum();

    //     $sentQuantities = PrintSendData::where('product_combination_id', $product_combination_id)
    //         ->get()
    //         ->flatMap(function ($record) {
    //             return collect($record->send_quantities)
    //                 ->mapWithKeys(fn($qty, $sizeId) => [(int)$sizeId => $qty]);
    //         })
    //         ->groupBy('key')
    //         ->map->sum('value');

    //     $availableQuantities = [];
    //     foreach ($cutQuantities as $sizeId => $cutQty) {
    //         $sentQty = $sentQuantities->get($sizeId, 0);
    //         $availableQuantities[(string)$sizeId] = $cutQty - $sentQty;
    //     }

    //     //if sublimation print true and print true then capture data from SublimationPrintReceive also  
    //     if ($this->isSublimationPrint($product_combination_id)) {
    //         $sublimationData = SublimationPrintReceive::where('product_combination_id', $product_combination_id)
    //             ->get()
    //             ->flatMap(function ($record) {
    //                 return collect($record->receive_quantities)
    //                     ->mapWithKeys(fn($qty, $sizeId) => [(int)$sizeId => $qty]);
    //             })
    //             ->groupBy('key')
    //             ->map->sum('value');

    //         // Merge sublimation data into available quantities
    //         foreach ($sublimationData as $sizeId => $qty) {
    //             $availableQuantities[(string)$sizeId] = ($availableQuantities[(string)$sizeId] ?? 0) + $qty;
    //         }
    //     }

    //     return response()->json([
    //         'available' => array_sum($availableQuantities),
    //         'available_per_size' => $availableQuantities
    //     ]);
    // }

    // Reports

    public function totalPrintEmbSendReport(Request $request)
    {
        $query = PrintSendData::with('productCombination.style', 'productCombination.color');

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

        $printSendData = $query->get();
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $reportData = [];

        foreach ($printSendData as $data) {
            $style = $data->productCombination->style->name;
            $color = $data->productCombination->color->name;
            $key = $style . '-' . $color;

            if (!isset($reportData[$key])) {
                $reportData[$key] = [
                    'style' => $style,
                    'color' => $color,
                    'sizes' => [], // Will store an array of send and waste for each size
                    'total_send' => 0,
                    'total_waste' => 0,
                ];

                // Initialize send and waste for each size
                foreach ($allSizes as $size) {
                    $reportData[$key]['sizes'][$size->id] = ['send' => 0, 'waste' => 0];
                }
            }

            // Aggregate send quantities
            foreach ($data->send_quantities as $sizeId => $qty) {
                if (isset($reportData[$key]['sizes'][$sizeId])) {
                    $reportData[$key]['sizes'][$sizeId]['send'] += $qty;
                }
            }
            $reportData[$key]['total_send'] += $data->total_send_quantity;

            // Aggregate waste quantities
            foreach ($data->send_waste_quantities as $sizeId => $qty) {
                if (isset($reportData[$key]['sizes'][$sizeId])) {
                    $reportData[$key]['sizes'][$sizeId]['waste'] += $qty;
                }
            }
            $reportData[$key]['total_waste'] += $data->total_send_waste_quantity;
        }

        // Get filter options
        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = PrintSendData::distinct()->pluck('po_number')->filter()->values();

        return view('backend.library.print_send_data.reports.total', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes,
            'allStyles' => $allStyles,
            'allColors' => $allColors,
            'distinctPoNumbers' => $distinctPoNumbers
        ]);
    }

    public function wipReport(Request $request)
    {
        // Get filter parameters
        $styleIds = $request->input('style_id', []);
        $colorIds = $request->input('color_id', []);
        $poNumbers = $request->input('po_number', []);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');

        // Base query for product combinations with print_embroidery = true
        $combinationsQuery = ProductCombination::where('print_embroidery', true)
            ->with('style', 'color');

        // Apply style and color filters
        if (!empty($styleIds)) {
            $combinationsQuery->whereIn('style_id', $styleIds);
        }

        if (!empty($colorIds)) {
            $combinationsQuery->whereIn('color_id', $colorIds);
        }

        // Apply search filter
        if ($search) {
            $combinationsQuery->where(function ($q) use ($search) {
                $q->whereHas('style', function ($q2) use ($search) {
                    $q2->where('name', 'like', '%' . $search . '%');
                })->orWhereHas('color', function ($q2) use ($search) {
                    $q2->where('name', 'like', '%' . $search . '%');
                });
            });
        }

        $combinations = $combinationsQuery->get();
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $wipData = [];

        foreach ($combinations as $pc) {
            // Get all cut quantities per size for this product combination with filters
            $cuttingDataQuery = CuttingData::where('product_combination_id', $pc->id);

            // Apply PO number filter to cutting data
            if (!empty($poNumbers)) {
                $cuttingDataQuery->whereIn('po_number', $poNumbers);
            }

            // Apply date filter to cutting data
            if ($startDate && $endDate) {
                $cuttingDataQuery->whereBetween('created_at', [$startDate, $endDate]);
            }

            $cutQuantitiesPerSize = $cuttingDataQuery->get()
                ->pluck('cut_quantities')
                ->reduce(function ($carry, $quantities) {
                    foreach ($quantities as $sizeId => $qty) {
                        $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                    }
                    return $carry;
                }, []);

            // Get all sent quantities per size for this product combination with filters
            $sendDataQuery = PrintSendData::where('product_combination_id', $pc->id);

            // Apply PO number filter to send data
            if (!empty($poNumbers)) {
                $sendDataQuery->whereIn('po_number', $poNumbers);
            }

            // Apply date filter to send data
            if ($startDate && $endDate) {
                $sendDataQuery->whereBetween('date', [$startDate, $endDate]);
            }

            $sentQuantitiesPerSize = $sendDataQuery->get()
                ->pluck('send_quantities')
                ->reduce(function ($carry, $quantities) {
                    foreach ($quantities as $sizeId => $qty) {
                        $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                    }
                    return $carry;
                }, []);

            $totalCut = array_sum($cutQuantitiesPerSize);
            $totalSent = array_sum($sentQuantitiesPerSize);

            // Skip if no cutting data matches the filters
            if ($totalCut == 0) continue;

            // Only include in WIP if there's a positive waiting quantity
            if ($totalCut > $totalSent) {
                $key = $pc->style->name . '-' . $pc->color->name;

                if (!isset($wipData[$key])) {
                    $wipData[$key] = [
                        'style' => $pc->style->name,
                        'color' => $pc->color->name,
                        'sizes' => [], // Will store cut, sent, waiting for each size
                        'total_cut' => 0,
                        'total_sent' => 0,
                        'total_waiting' => 0
                    ];

                    // Initialize sizes
                    foreach ($allSizes as $size) {
                        $wipData[$key]['sizes'][$size->id] = [
                            'cut' => 0,
                            'sent' => 0,
                            'waiting' => 0
                        ];
                    }
                }

                // Aggregate size-specific data
                foreach ($allSizes as $size) {
                    $cut = $cutQuantitiesPerSize[$size->id] ?? 0;
                    $sent = $sentQuantitiesPerSize[$size->id] ?? 0;
                    $waiting = max(0, $cut - $sent); // Ensure waiting is not negative

                    $wipData[$key]['sizes'][$size->id]['cut'] += $cut;
                    $wipData[$key]['sizes'][$size->id]['sent'] += $sent;
                    $wipData[$key]['sizes'][$size->id]['waiting'] += $waiting;

                    $wipData[$key]['total_cut'] += $cut;
                    $wipData[$key]['total_sent'] += $sent;
                    $wipData[$key]['total_waiting'] += $waiting;
                }
            }
        }

        // Get filter options
        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = array_unique(
            array_merge(
                CuttingData::distinct()->pluck('po_number')->filter()->values()->toArray(),
                PrintSendData::distinct()->pluck('po_number')->filter()->values()->toArray()
            )
        );
        sort($distinctPoNumbers);

        return view('backend.library.print_send_data.reports.wip', [
            'wipData' => array_values($wipData),
            'allSizes' => $allSizes,
            'allStyles' => $allStyles,
            'allColors' => $allColors,
            'distinctPoNumbers' => $distinctPoNumbers
        ]);
    }

    // public function readyToInputReport(Request $request)
    // {
    //     $readyData = [];

    //     // Get filter parameters
    //     $styleIds = $request->input('style_id', []);
    //     $colorIds = $request->input('color_id', []);
    //     $poNumbers = $request->input('po_number', []);
    //     $startDate = $request->input('start_date');
    //     $endDate = $request->input('end_date');
    //     $search = $request->input('search');

    //     // Product combinations with print_embroidery = false (don't need print/emb)
    //     $nonEmbCombinationsQuery = ProductCombination::where('print_embroidery', false)
    //         ->with('style', 'color');

    //     // Apply style and color filters
    //     if (!empty($styleIds)) {
    //         $nonEmbCombinationsQuery->whereIn('style_id', $styleIds);
    //     }

    //     if (!empty($colorIds)) {
    //         $nonEmbCombinationsQuery->whereIn('color_id', $colorIds);
    //     }

    //     // Apply search filter
    //     if ($search) {
    //         $nonEmbCombinationsQuery->where(function ($q) use ($search) {
    //             $q->whereHas('style', function ($q2) use ($search) {
    //                 $q2->where('name', 'like', '%' . $search . '%');
    //             })->orWhereHas('color', function ($q2) use ($search) {
    //                 $q2->where('name', 'like', '%' . $search . '%');
    //             });
    //         });
    //     }

    //     $nonEmbCombinations = $nonEmbCombinationsQuery->get();

    //     foreach ($nonEmbCombinations as $pc) {
    //         $cuttingDataQuery = CuttingData::where('product_combination_id', $pc->id);

    //         // Apply PO number filter
    //         if (!empty($poNumbers)) {
    //             $cuttingDataQuery->whereIn('po_number', $poNumbers);
    //         }

    //         // Apply date filter
    //         if ($startDate && $endDate) {
    //             $cuttingDataQuery->whereBetween('created_at', [$startDate, $endDate]);
    //         }

    //         $totalCut = $cuttingDataQuery->sum('total_cut_quantity');

    //         // Skip if no cutting data matches the filters
    //         if ($totalCut == 0) continue;

    //         // If there's any cut quantity, it's ready to input
    //         if ($totalCut > 0) {
    //             $readyData[] = [
    //                 'style' => $pc->style->name,
    //                 'color' => $pc->color->name,
    //                 'po_number' => $cuttingDataQuery->pluck('po_number')->unique()->implode(', '),
    //                 'type' => 'No Print/Emb Needed',
    //                 'total_cut' => $totalCut,
    //                 'total_sent' => 0, // No sending for non-emb
    //                 'total_received' => 0, // No receiving for non-emb
    //                 'status' => 'Ready for Finishing',
    //             ];
    //         }
    //     }

    //     // Product combinations with print_embroidery = true that have completed the send process
    //     $embCombinationsQuery = ProductCombination::where('print_embroidery', true)
    //         ->with('style', 'color');

    //     // Apply style and color filters
    //     if (!empty($styleIds)) {
    //         $embCombinationsQuery->whereIn('style_id', $styleIds);
    //     }

    //     if (!empty($colorIds)) {
    //         $embCombinationsQuery->whereIn('color_id', $colorIds);
    //     }

    //     // Apply search filter
    //     if ($search) {
    //         $embCombinationsQuery->where(function ($q) use ($search) {
    //             $q->whereHas('style', function ($q2) use ($search) {
    //                 $q2->where('name', 'like', '%' . $search . '%');
    //             })->orWhereHas('color', function ($q2) use ($search) {
    //                 $q2->where('name', 'like', '%' . $search . '%');
    //             });
    //         });
    //     }

    //     $embCombinations = $embCombinationsQuery->get();

    //     foreach ($embCombinations as $pc) {
    //         $cuttingDataQuery = CuttingData::where('product_combination_id', $pc->id);
    //         $sendDataQuery = PrintSendData::where('product_combination_id', $pc->id);
    //         $receiveDataQuery = SublimationPrintReceive::where('product_combination_id', $pc->id);

    //         // Apply PO number filter
    //         if (!empty($poNumbers)) {
    //             $cuttingDataQuery->whereIn('po_number', $poNumbers);
    //             $sendDataQuery->whereIn('po_number', $poNumbers);
    //             $receiveDataQuery->whereIn('po_number', $poNumbers);
    //         }

    //         // Apply date filter
    //         if ($startDate && $endDate) {
    //             $cuttingDataQuery->whereBetween('created_at', [$startDate, $endDate]);
    //             $sendDataQuery->whereBetween('date', [$startDate, $endDate]);
    //             $receiveDataQuery->whereBetween('date', [$startDate, $endDate]);
    //         }

    //         $totalCut = $cuttingDataQuery->sum('total_cut_quantity');
    //         $totalSent = $sendDataQuery->sum('total_send_quantity');
    //         $totalReceived = $receiveDataQuery->sum('total_sublimation_print_receive_quantity');

    //         // Skip if no cutting data matches the filters
    //         if ($totalCut == 0) continue;

    //         // If total sent is equal to or greater than total cut (meaning all cut pieces for print/emb are sent out)
    //         if ($totalSent >= $totalCut && $totalCut > 0) {
    //             // Now, check if all sent items have been received back
    //             if ($totalReceived >= $totalSent) { // All sent items received, ready for finishing
    //                 $readyData[] = [
    //                     'style' => $pc->style->name,
    //                     'color' => $pc->color->name,
    //                     'po_number' => $sendDataQuery->pluck('po_number')->unique()->implode(', '),
    //                     'type' => 'Print/Emb Completed & Received',
    //                     'total_cut' => $totalCut,
    //                     'total_sent' => $totalSent,
    //                     'total_received' => $totalReceived,
    //                     'status' => 'Ready for Finishing',
    //                 ];
    //             }
    //         }
    //     }

    //     // Get filter options
    //     $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
    //     $allColors = Color::where('is_active', 1)->orderBy('name')->get();
    //     $distinctPoNumbers = array_unique(
    //         array_merge(
    //             CuttingData::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //             PrintSendData::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //             SublimationPrintReceive::distinct()->pluck('po_number')->filter()->values()->toArray()
    //         )
    //     );
    //     sort($distinctPoNumbers);

    //     return view('backend.library.print_send_data.reports.ready', [
    //         'readyData' => $readyData,
    //         'allStyles' => $allStyles,
    //         'allColors' => $allColors,
    //         'distinctPoNumbers' => $distinctPoNumbers
    //     ]);
    // }

    // public function readyToInputReport(Request $request)
    // {
    //     $readyData = [];

    //     // Get filter parameters
    //     $styleIds = $request->input('style_id', []);
    //     $colorIds = $request->input('color_id', []);
    //     $poNumbers = $request->input('po_number', []);
    //     $startDate = $request->input('start_date');
    //     $endDate = $request->input('end_date');
    //     $search = $request->input('search');

    //     // Case 1: No print/emb or sublimation needed (both flags false)
    //     $noPrintQuery = ProductCombination::where('print_embroidery', false)
    //         ->where('sublimation_print', false)
    //         ->with('style', 'color');

    //     if (!empty($styleIds)) {
    //         $noPrintQuery->whereIn('style_id', $styleIds);
    //     }

    //     if (!empty($colorIds)) {
    //         $noPrintQuery->whereIn('color_id', $colorIds);
    //     }

    //     if ($search) {
    //         $noPrintQuery->where(function ($q) use ($search) {
    //             $q->whereHas('style', function ($q2) use ($search) {
    //                 $q2->where('name', 'like', '%' . $search . '%');
    //             })->orWhereHas('color', function ($q2) use ($search) {
    //                 $q2->where('name', 'like', '%' . $search . '%');
    //             });
    //         });
    //     }

    //     $noPrintCombinations = $noPrintQuery->get();

    //     foreach ($noPrintCombinations as $pc) {
    //         $sizes = Size::whereIn('id', $pc->size_ids)->orderBy('name')->pluck('name', 'id')->toArray();

    //         $cuttingQuery = CuttingData::where('product_combination_id', $pc->id);

    //         if (!empty($poNumbers)) {
    //             $cuttingQuery->whereIn('po_number', $poNumbers);
    //         }

    //         if ($startDate && $endDate) {
    //             $cuttingQuery->whereBetween('date', [$startDate, $endDate]);
    //         }

    //         $pos = $cuttingQuery->pluck('po_number')->unique()->filter();

    //         foreach ($pos as $po) {
    //             $cuttings = $cuttingQuery->clone()->where('po_number', $po)->get();

    //             $sizeQuantities = [];

    //             foreach ($cuttings as $cutting) {
    //                 $quants = $cutting->cut_quantities ?? [];

    //                 foreach ($quants as $sizeId => $qty) {
    //                     $sizeName = $sizes[$sizeId] ?? $sizeId;
    //                     $sizeQuantities[$sizeName] = ($sizeQuantities[$sizeName] ?? 0) + (int)$qty;
    //                 }
    //             }

    //             $totalCut = array_sum($sizeQuantities);

    //             if ($totalCut > 0) {
    //                 $readyData[] = [
    //                     'style' => $pc->style->name,
    //                     'color' => $pc->color->name,
    //                     'po_number' => $po,
    //                     'type' => 'No Print/Emb Needed',
    //                     'total_cut' => $totalCut,
    //                     'total_sent' => 0,
    //                     'total_received' => 0,
    //                     'size_quantities' => $sizeQuantities,
    //                     'status' => 'Ready for Finishing',
    //                 ];
    //             }
    //         }
    //     }

    //     // Case 2: Sublimation print needed, no print/emb (sublimation true, emb false)
    //     $sublQuery = ProductCombination::where('print_embroidery', false)
    //         ->where('sublimation_print', true)
    //         ->with('style', 'color');

    //     if (!empty($styleIds)) {
    //         $sublQuery->whereIn('style_id', $styleIds);
    //     }

    //     if (!empty($colorIds)) {
    //         $sublQuery->whereIn('color_id', $colorIds);
    //     }

    //     if ($search) {
    //         $sublQuery->where(function ($q) use ($search) {
    //             $q->whereHas('style', function ($q2) use ($search) {
    //                 $q2->where('name', 'like', '%' . $search . '%');
    //             })->orWhereHas('color', function ($q2) use ($search) {
    //                 $q2->where('name', 'like', '%' . $search . '%');
    //             });
    //         });
    //     }

    //     $sublCombinations = $sublQuery->get();

    //     foreach ($sublCombinations as $pc) {
    //         $sizes = Size::whereIn('id', $pc->size_ids)->orderBy('name')->pluck('name', 'id')->toArray();

    //         $cuttingQuery = CuttingData::where('product_combination_id', $pc->id);
    //         $sendQuery = SublimationPrintSend::where('product_combination_id', $pc->id);
    //         $receiveQuery = SublimationPrintReceive::where('product_combination_id', $pc->id);

    //         if (!empty($poNumbers)) {
    //             $cuttingQuery->whereIn('po_number', $poNumbers);
    //             $sendQuery->whereIn('po_number', $poNumbers);
    //             $receiveQuery->whereIn('po_number', $poNumbers);
    //         }

    //         if ($startDate && $endDate) {
    //             $cuttingQuery->whereBetween('date', [$startDate, $endDate]);
    //             $sendQuery->whereBetween('date', [$startDate, $endDate]);
    //             $receiveQuery->whereBetween('date', [$startDate, $endDate]);
    //         }

    //         $pos = collect($cuttingQuery->pluck('po_number'))
    //             ->merge($sendQuery->pluck('po_number'))
    //             ->merge($receiveQuery->pluck('po_number'))
    //             ->unique()
    //             ->filter();

    //         foreach ($pos as $po) {
    //             $totalCut = $cuttingQuery->clone()->where('po_number', $po)->sum('total_cut_quantity');
    //             $totalSent = $sendQuery->clone()->where('po_number', $po)->sum('total_sublimation_print_send_quantity');
    //             $totalReceived = $receiveQuery->clone()->where('po_number', $po)->sum('total_sublimation_print_receive_quantity');

    //             if ($totalCut > 0 && $totalSent >= $totalCut && $totalReceived >= $totalSent) {
    //                 // Get size wise from receives
    //                 $receives = $receiveQuery->clone()->where('po_number', $po)->get();
    //                 $sizeQuantities = [];
    //                 foreach ($receives as $receive) {
    //                     $quants = $receive->sublimation_print_receive_quantities ?? [];
    //                     foreach ($quants as $sizeId => $qty) {
    //                         $sizeName = $sizes[$sizeId] ?? $sizeId;
    //                         $sizeQuantities[$sizeName] = ($sizeQuantities[$sizeName] ?? 0) + (int)$qty;
    //                     }
    //                 }

    //                 $readyData[] = [
    //                     'style' => $pc->style->name,
    //                     'color' => $pc->color->name,
    //                     'po_number' => $po,
    //                     'type' => 'Sublimation Print Completed & Received',
    //                     'total_cut' => $totalCut,
    //                     'total_sent' => $totalSent,
    //                     'total_received' => $totalReceived,
    //                     'size_quantities' => $sizeQuantities,
    //                     'status' => 'Ready for Finishing',
    //                 ];
    //             }
    //         }
    //     }

    //     // Case 3: Print/emb needed (emb true, regardless of sublimation)
    //     $embQuery = ProductCombination::where('print_embroidery', true)
    //         ->with('style', 'color');

    //     if (!empty($styleIds)) {
    //         $embQuery->whereIn('style_id', $styleIds);
    //     }

    //     if (!empty($colorIds)) {
    //         $embQuery->whereIn('color_id', $colorIds);
    //     }

    //     if ($search) {
    //         $embQuery->where(function ($q) use ($search) {
    //             $q->whereHas('style', function ($q2) use ($search) {
    //                 $q2->where('name', 'like', '%' . $search . '%');
    //             })->orWhereHas('color', function ($q2) use ($search) {
    //                 $q2->where('name', 'like', '%' . $search . '%');
    //             });
    //         });
    //     }

    //     $embCombinations = $embQuery->get();

    //     foreach ($embCombinations as $pc) {
    //         $sizes = Size::whereIn('id', $pc->size_ids)->orderBy('name')->pluck('name', 'id')->toArray();

    //         $cuttingQuery = CuttingData::where('product_combination_id', $pc->id);
    //         $sendQuery = PrintSendData::where('product_combination_id', $pc->id);
    //         $receiveQuery = PrintReceiveData::where('product_combination_id', $pc->id);

    //         if (!empty($poNumbers)) {
    //             $cuttingQuery->whereIn('po_number', $poNumbers);
    //             $sendQuery->whereIn('po_number', $poNumbers);
    //             $receiveQuery->whereIn('po_number', $poNumbers);
    //         }

    //         if ($startDate && $endDate) {
    //             $cuttingQuery->whereBetween('date', [$startDate, $endDate]);
    //             $sendQuery->whereBetween('date', [$startDate, $endDate]);
    //             $receiveQuery->whereBetween('date', [$startDate, $endDate]);
    //         }

    //         $pos = collect($cuttingQuery->pluck('po_number'))
    //             ->merge($sendQuery->pluck('po_number'))
    //             ->merge($receiveQuery->pluck('po_number'))
    //             ->unique()
    //             ->filter();

    //         foreach ($pos as $po) {
    //             $totalCut = $cuttingQuery->clone()->where('po_number', $po)->sum('total_cut_quantity');
    //             $totalSent = $sendQuery->clone()->where('po_number', $po)->sum('total_send_quantity');
    //             $totalReceived = $receiveQuery->clone()->where('po_number', $po)->sum('total_receive_quantity');

    //             if ($totalCut > 0 && $totalSent >= $totalCut && $totalReceived >= $totalSent) {
    //                 // Get size wise from receives
    //                 $receives = $receiveQuery->clone()->where('po_number', $po)->get();
    //                 $sizeQuantities = [];
    //                 foreach ($receives as $receive) {
    //                     $quants = $receive->receive_quantities ?? [];
    //                     foreach ($quants as $sizeId => $qty) {
    //                         $sizeName = $sizes[$sizeId] ?? $sizeId;
    //                         $sizeQuantities[$sizeName] = ($sizeQuantities[$sizeName] ?? 0) + (int)$qty;
    //                     }
    //                 }

    //                 $readyData[] = [
    //                     'style' => $pc->style->name,
    //                     'color' => $pc->color->name,
    //                     'po_number' => $po,
    //                     'type' => 'Print/Emb Completed & Received',
    //                     'total_cut' => $totalCut,
    //                     'total_sent' => $totalSent,
    //                     'total_received' => $totalReceived,
    //                     'size_quantities' => $sizeQuantities,
    //                     'status' => 'Ready for Finishing',
    //                 ];
    //             }
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
    //             SublimationPrintSend::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //             SublimationPrintReceive::distinct()->pluck('po_number')->filter()->values()->toArray()
    //         )
    //     );
    //     sort($distinctPoNumbers);

    //     return view('backend.library.print_send_data.reports.ready', [
    //         'readyData' => $readyData,
    //         'allStyles' => $allStyles,
    //         'allColors' => $allColors,
    //         'distinctPoNumbers' => $distinctPoNumbers
    //     ]);
    // }

    public function readyToInputReport(Request $request)
    {
        $allReportData = [];

        // Get filter parameters
        $styleIds = $request->input('style_id', []);
        $colorIds = $request->input('color_id', []);
        $poNumbers = $request->input('po_number', []);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');

        // Fetch all active sizes once to use for mapping
        $allSizes = Size::where('is_active', 1)->pluck('name', 'id')->toArray();

        // Base query for ProductCombinations
        $productCombinationsQuery = ProductCombination::with('style', 'color');

        // Apply style and color filters
        if (!empty($styleIds)) {
            $productCombinationsQuery->whereIn('style_id', $styleIds);
        }

        if (!empty($colorIds)) {
            $productCombinationsQuery->whereIn('color_id', $colorIds);
        }

        // Apply search filter for style and color names
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
            $currentData = [
                'style' => $pc->style->name,
                'color' => $pc->color->name,
                'po_number' => 'N/A',
                'type' => 'N/A',
                'total_cut' => 0,
                'total_sent' => 0,
                'total_received' => 0,
                'status' => 'Pending Cutting',
                'size_wise_cut' => [],
                'size_wise_sent' => [],
                'size_wise_received' => [],
            ];

            // Initialize queries for related data based on product_combination_id
            $cuttingDataQuery = CuttingData::where('product_combination_id', $pc->id);
            $sublimationPrintReceiveQuery = SublimationPrintReceive::where('product_combination_id', $pc->id);
            $printReceiveQuery = PrintReceiveData::where('product_combination_id', $pc->id);
            $sublimationPrintSendQuery = SublimationPrintSend::where('product_combination_id', $pc->id);
            $printSendQuery = PrintSendData::where('product_combination_id', $pc->id);


            // Apply PO number filter to all relevant data sources
            if (!empty($poNumbers)) {
                $cuttingDataQuery->whereIn('po_number', $poNumbers);
                $sublimationPrintReceiveQuery->whereIn('po_number', $poNumbers);
                $printReceiveQuery->whereIn('po_number', $poNumbers);
                $sublimationPrintSendQuery->whereIn('po_number', $poNumbers);
                $printSendQuery->whereIn('po_number', $poNumbers);
            }

            // Apply date filter to all relevant data sources
            if ($startDate && $endDate) {
                $cuttingDataQuery->whereBetween('created_at', [$startDate, $endDate]);
                $sublimationPrintReceiveQuery->whereBetween('date', [$startDate, $endDate]);
                $printReceiveQuery->whereBetween('date', [$startDate, $endDate]);
                $sublimationPrintSendQuery->whereBetween('date', [$startDate, $endDate]);
                $printSendQuery->whereBetween('date', [$startDate, $endDate]);
            }

            // Sum total quantities
            $totalCut = $cuttingDataQuery->sum('total_cut_quantity');
            $currentData['total_cut'] = $totalCut;

            // --- Aggregate Size-Wise Quantities ---
            $aggregatedCutQuantities = [];
            foreach ($cuttingDataQuery->get() as $data) {
                $cutQuantities = $data->cut_quantities;
                foreach ($cutQuantities as $sizeId => $quantity) {
                    $aggregatedCutQuantities[$sizeId] = ($aggregatedCutQuantities[$sizeId] ?? 0) + $quantity;
                }
            }
            // Map size IDs to names for cut quantities
            foreach ($aggregatedCutQuantities as $sizeId => $quantity) {
                if (isset($allSizes[$sizeId])) {
                    $currentData['size_wise_cut'][$allSizes[$sizeId]] = $quantity;
                }
            }


            // Collect unique PO numbers from all relevant sources for this combination
            $poNumbersForCombination = collect([]);
            $poNumbersForCombination = $poNumbersForCombination->merge($cuttingDataQuery->pluck('po_number'));
            $poNumbersForCombination = $poNumbersForCombination->merge($sublimationPrintReceiveQuery->pluck('po_number'));
            $poNumbersForCombination = $poNumbersForCombination->merge($printReceiveQuery->pluck('po_number'));
            $poNumbersForCombination = $poNumbersForCombination->merge($sublimationPrintSendQuery->pluck('po_number'));
            $poNumbersForCombination = $poNumbersForCombination->merge($printSendQuery->pluck('po_number'));

            $currentData['po_number'] = $poNumbersForCombination->filter()->unique()->implode(', ') ?: 'N/A';

            // Logic based on product_combinations table flags
            if ($pc->sublimation_print && !$pc->print_embroidery) {
                $currentData['type'] = 'Sublimation Print Only';

                // Sublimation Print Receive (received)
                $totalReceived = $sublimationPrintReceiveQuery->sum('total_sublimation_print_receive_quantity');
                $currentData['total_received'] = $totalReceived;

                $aggregatedReceivedQuantities = [];
                foreach ($sublimationPrintReceiveQuery->get() as $data) {
                    $receiveQuantities = $data->sublimation_print_receive_quantities;
                    foreach ($receiveQuantities as $sizeId => $quantity) {
                        $aggregatedReceivedQuantities[$sizeId] = ($aggregatedReceivedQuantities[$sizeId] ?? 0) + $quantity;
                    }
                }
                foreach ($aggregatedReceivedQuantities as $sizeId => $quantity) {
                    if (isset($allSizes[$sizeId])) {
                        $currentData['size_wise_received'][$allSizes[$sizeId]] = $quantity;
                    }
                }

                // Sublimation Print Send (sent)
                $totalSent = $sublimationPrintSendQuery->sum('total_sublimation_print_send_quantity');
                $currentData['total_sent'] = $totalSent;

                $aggregatedSentQuantities = [];
                foreach ($sublimationPrintSendQuery->get() as $data) {
                    $sendQuantities = $data->sublimation_print_send_quantities;
                    foreach ($sendQuantities as $sizeId => $quantity) {
                        $aggregatedSentQuantities[$sizeId] = ($aggregatedSentQuantities[$sizeId] ?? 0) + $quantity;
                    }
                }
                foreach ($aggregatedSentQuantities as $sizeId => $quantity) {
                    if (isset($allSizes[$sizeId])) {
                        $currentData['size_wise_sent'][$allSizes[$sizeId]] = $quantity;
                    }
                }

                if ($totalCut > 0 && $totalReceived >= $totalCut) {
                    $currentData['status'] = 'Ready for Finishing (Sublimation)';
                } elseif ($totalCut > 0 && $totalReceived < $totalCut) {
                    $currentData['status'] = 'Sublimation Printing in Progress';
                }
            } elseif ($pc->sublimation_print && $pc->print_embroidery) {
                $currentData['type'] = 'Sublimation & Print/Emb';

                // Print Receive (received)
                $totalReceived = $printReceiveQuery->sum('total_receive_quantity');
                $currentData['total_received'] = $totalReceived;

                $aggregatedReceivedQuantities = [];
                foreach ($printReceiveQuery->get() as $data) {
                    $receiveQuantities = $data->receive_quantities;
                    foreach ($receiveQuantities as $sizeId => $quantity) {
                        $aggregatedReceivedQuantities[$sizeId] = ($aggregatedReceivedQuantities[$sizeId] ?? 0) + $quantity;
                    }
                }
                foreach ($aggregatedReceivedQuantities as $sizeId => $quantity) {
                    if (isset($allSizes[$sizeId])) {
                        $currentData['size_wise_received'][$allSizes[$sizeId]] = $quantity;
                    }
                }

                // Print Send (sent)
                $totalSent = $printSendQuery->sum('total_send_quantity');
                $currentData['total_sent'] = $totalSent;

                $aggregatedSentQuantities = [];
                foreach ($printSendQuery->get() as $data) {
                    $sendQuantities = $data->send_quantities;
                    foreach ($sendQuantities as $sizeId => $quantity) {
                        $aggregatedSentQuantities[$sizeId] = ($aggregatedSentQuantities[$sizeId] ?? 0) + $quantity;
                    }
                }
                foreach ($aggregatedSentQuantities as $sizeId => $quantity) {
                    if (isset($allSizes[$sizeId])) {
                        $currentData['size_wise_sent'][$allSizes[$sizeId]] = $quantity;
                    }
                }

                if ($totalCut > 0 && $totalReceived >= $totalCut) {
                    $currentData['status'] = 'Ready for Finishing (Sublimation & Print/Emb)';
                } elseif ($totalCut > 0 && $totalReceived < $totalCut) {
                    $currentData['status'] = 'Print/Embroidery in Progress';
                }
            } elseif (!$pc->sublimation_print && $pc->print_embroidery) {
                $currentData['type'] = 'Print/Embroidery Only';

                // Print Receive (received)
                $totalReceived = $printReceiveQuery->sum('total_receive_quantity');
                $currentData['total_received'] = $totalReceived;

                $aggregatedReceivedQuantities = [];
                foreach ($printReceiveQuery->get() as $data) {
                    $receiveQuantities = $data->receive_quantities;
                    foreach ($receiveQuantities as $sizeId => $quantity) {
                        $aggregatedReceivedQuantities[$sizeId] = ($aggregatedReceivedQuantities[$sizeId] ?? 0) + $quantity;
                    }
                }
                foreach ($aggregatedReceivedQuantities as $sizeId => $quantity) {
                    if (isset($allSizes[$sizeId])) {
                        $currentData['size_wise_received'][$allSizes[$sizeId]] = $quantity;
                    }
                }

                // Print Send (sent)
                $totalSent = $printSendQuery->sum('total_send_quantity');
                $currentData['total_sent'] = $totalSent;

                $aggregatedSentQuantities = [];
                foreach ($printSendQuery->get() as $data) {
                    $sendQuantities = $data->send_quantities;
                    foreach ($sendQuantities as $sizeId => $quantity) {
                        $aggregatedSentQuantities[$sizeId] = ($aggregatedSentQuantities[$sizeId] ?? 0) + $quantity;
                    }
                }
                foreach ($aggregatedSentQuantities as $sizeId => $quantity) {
                    if (isset($allSizes[$sizeId])) {
                        $currentData['size_wise_sent'][$allSizes[$sizeId]] = $quantity;
                    }
                }

                if ($totalCut > 0 && $totalReceived >= $totalCut) {
                    $currentData['status'] = 'Ready for Finishing (Print/Emb)';
                } elseif ($totalCut > 0 && $totalReceived < $totalCut) {
                    $currentData['status'] = 'Print/Embroidery in Progress';
                }
            } elseif (!$pc->sublimation_print && !$pc->print_embroidery) {
                $currentData['type'] = 'No Print/Embroidery';
                // Total cut is already calculated
                if ($totalCut > 0) {
                    $currentData['status'] = 'Ready for Finishing';
                }
            }

            // Only add to report if there is some cutting data or related data exists
            if ($totalCut > 0 || $currentData['total_sent'] > 0 || $currentData['total_received'] > 0) {
                $allReportData[] = $currentData;
            }
        }

        // Get filter options (ensure they cover all possible PO numbers from all tables)
        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();

        $distinctPoNumbers = array_unique(
            array_merge(
                CuttingData::distinct()->pluck('po_number')->filter()->values()->toArray(),
                PrintSendData::distinct()->pluck('po_number')->filter()->values()->toArray(),
                SublimationPrintReceive::distinct()->pluck('po_number')->filter()->values()->toArray(),
                SublimationPrintSend::distinct()->pluck('po_number')->filter()->values()->toArray(),
                PrintReceiveData::distinct()->pluck('po_number')->filter()->values()->toArray()
            )
        );
        sort($distinctPoNumbers);

        return view('backend.library.print_send_data.reports.ready', [
            'readyData' => $allReportData,
            'allStyles' => $allStyles,
            'allColors' => $allColors,
            'distinctPoNumbers' => $distinctPoNumbers,
            'allSizes' => $allSizes, // Pass all sizes to the view for dynamic headers
        ]);
    }

   

}
