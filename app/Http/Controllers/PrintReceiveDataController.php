<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\CuttingData;
use App\Models\PrintReceiveData;
use App\Models\sublimationPrintReceive;
use App\Models\sublimationPrintSend;
use Illuminate\Http\Request;
use App\Models\PrintSendData;
use App\Models\ProductCombination;
use App\Models\Size;
use App\Models\Style;
use Illuminate\Support\Facades\DB;

class PrintReceiveDataController extends Controller
{
    public function index(Request $request)
    {
        $query = PrintReceiveData::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

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

        $printReceiveData = $query->orderBy('date', 'desc')->paginate(10);
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Get filter options
        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = PrintReceiveData::distinct()->pluck('po_number')->filter()->values();

        return view('backend.library.print_receive_data.index', compact(
            'printReceiveData',
            'allSizes',
            'allStyles',
            'allColors',
            'distinctPoNumbers'
        ));
    }

    public function create()
    {
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Get distinct PO numbers from PrintSendData
        $distinctPoNumbers = PrintSendData::distinct()
            ->pluck('po_number')
            ->filter()
            ->values();

        return view('backend.library.print_receive_data.create', compact('distinctPoNumbers', 'allSizes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'po_number' => 'required|array',
            'po_number.*' => 'required|string',
            'rows' => 'required|array',
            'rows.*.product_combination_id' => 'required|exists:product_combinations,id',
            'rows.*.po_number' => 'required|string', // Add validation for row-level PO number
            'rows.*.receive_quantities.*' => 'nullable|integer|min:0',
            'rows.*.receive_waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            $validRowsProcessed = 0;

            foreach ($request->rows as $row) {
                // Check if this row has any available quantities (not all N/A)
                $hasAvailableQuantities = false;
                $hasValidQuantities = false;

                // Check receive quantities
                if (isset($row['receive_quantities'])) {
                    foreach ($row['receive_quantities'] as $quantity) {
                        if ($quantity !== null && (int)$quantity > 0) {
                            $hasValidQuantities = true;
                            $hasAvailableQuantities = true;
                            break;
                        }
                    }
                }

                // Check waste quantities
                if (isset($row['receive_waste_quantities'])) {
                    foreach ($row['receive_waste_quantities'] as $quantity) {
                        if ($quantity !== null && (int)$quantity > 0) {
                            $hasValidQuantities = true;
                            $hasAvailableQuantities = true;
                            break;
                        }
                    }
                }

                // If no available quantities at all (all N/A), skip this row entirely
                if (!$hasAvailableQuantities) {
                    continue;
                }

                $receiveQuantities = [];
                $wasteQuantities = [];
                $totalReceiveQuantity = 0;
                $totalWasteQuantity = 0;

                // Process receive quantities
                if (isset($row['receive_quantities'])) {
                    foreach ($row['receive_quantities'] as $sizeId => $quantity) {
                        if ($quantity !== null && (int)$quantity > 0) {
                            $size = Size::find($sizeId);
                            if ($size) {
                                $receiveQuantities[$size->id] = (int)$quantity;
                                $totalReceiveQuantity += (int)$quantity;
                            }
                        }
                    }
                }

                // Process waste quantities
                if (isset($row['receive_waste_quantities'])) {
                    foreach ($row['receive_waste_quantities'] as $sizeId => $quantity) {
                        if ($quantity !== null && (int)$quantity > 0) {
                            $size = Size::find($sizeId);
                            if ($size) {
                                $wasteQuantities[$size->id] = (int)$quantity;
                                $totalWasteQuantity += (int)$quantity;
                            }
                        }
                    }
                }

                // Only create a record if there's at least one valid receive or waste quantity
                if (!empty($receiveQuantities) || !empty($wasteQuantities)) {
                    PrintReceiveData::create([
                        'date' => $request->date,
                        'product_combination_id' => $row['product_combination_id'],
                        'po_number' => $row['po_number'], // Use the row-level PO number
                        'receive_quantities' => $receiveQuantities,
                        'total_receive_quantity' => $totalReceiveQuantity,
                        'receive_waste_quantities' => $wasteQuantities,
                        'total_receive_waste_quantity' => $totalWasteQuantity,
                    ]);

                    $validRowsProcessed++;
                }
            }

            DB::commit();

            if ($validRowsProcessed > 0) {
                return redirect()->route('print_receive_data.index')
                    ->with('message', 'Print/Embroidery Receive data added successfully.');
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

    public function show(PrintReceiveData $printReceiveDatum)
    {
        $printReceiveDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        //only valid sizes
        $validSizes = $allSizes->filter(function ($size) use ($printReceiveDatum) {
            return isset($printReceiveDatum->receive_quantities[$size->id]) ||
                isset($printReceiveDatum->receive_waste_quantities[$size->id]);
        });

        $allSizes = $validSizes->values();

        return view('backend.library.print_receive_data.show', compact('printReceiveDatum', 'allSizes'));
    }


    public function update(Request $request, PrintReceiveData $printReceiveDatum)
    {
        $request->validate([
            'date' => 'required|date',
            'receive_quantities.*' => 'nullable|integer|min:0',
            'receive_waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        try {
            $receiveQuantities = [];
            $wasteQuantities = [];
            $totalReceiveQuantity = 0;
            $totalWasteQuantity = 0;

            // Process receive quantities
            foreach ($request->receive_quantities as $sizeId => $quantity) {
                if ($quantity !== null && (int)$quantity > 0) {
                    $receiveQuantities[$sizeId] = (int)$quantity;
                    $totalReceiveQuantity += (int)$quantity;
                }
            }

            // Process waste quantities
            foreach ($request->receive_waste_quantities as $sizeId => $quantity) {
                if ($quantity !== null && (int)$quantity > 0) {
                    $wasteQuantities[$sizeId] = (int)$quantity;
                    $totalWasteQuantity += (int)$quantity;
                }
            }

            $printReceiveDatum->update([
                'date' => $request->date,
                'receive_quantities' => $receiveQuantities,
                'total_receive_quantity' => $totalReceiveQuantity,
                'receive_waste_quantities' => $wasteQuantities,
                'total_receive_waste_quantity' => $totalWasteQuantity,
            ]);

            return redirect()->route('print_receive_data.index')
                ->withMessage('Print/Embroidery Receive data updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(PrintReceiveData $printReceiveDatum)
    {
        $printReceiveDatum->delete();

        return redirect()->route('print_receive_data.index')
            ->withMessage('Print/Embroidery Receive data deleted successfully.');
    }



    // Update the getAvailableReceiveQuantities method to accept PO number filtering

    public function getAvailableReceiveQuantities(ProductCombination $productCombination, $poNumber = null)
    {
        // dd($poNumber);
        $sizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $availableQuantities = [];

        // Base query for sent quantities
        $sentQuery = PrintSendData::where('product_combination_id', $productCombination->id);

        // Filter by PO number if provided
        if ($poNumber) {
            $sentQuery->where('po_number', 'like', '%' . $poNumber . '%');
        }

        // Sum sent quantities per size
        $sentQuantities = $sentQuery->get()
            ->pluck('send_quantities')
            ->reduce(function ($carry, $quantities) {
                foreach ($quantities as $sizeId => $qty) {
                    $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                }
                return $carry;
            }, []);

        // Base query for received quantities
        $receivedQuery = PrintReceiveData::where('product_combination_id', $productCombination->id);

        // Filter by PO number if provided
        if ($poNumber) {
            $receivedQuery->where('po_number', 'like', '%' . $poNumber . '%');
        }

        // Get all receive records
        $receiveRecords = $receivedQuery->get();

        // Sum received quantities per size (good quantities)
        $receivedGoodQuantities = $receiveRecords
            ->pluck('receive_quantities')
            ->reduce(function ($carry, $quantities) {
                foreach ($quantities as $sizeId => $qty) {
                    $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                }
                return $carry;
            }, []);

        // Sum waste quantities per size
        $receivedWasteQuantities = $receiveRecords
            ->pluck('receive_waste_quantities')
            ->reduce(function ($carry, $quantities) {
                foreach ($quantities as $sizeId => $qty) {
                    $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                }
                return $carry;
            }, []);

        // Combine good and waste quantities for total received
        $totalReceivedQuantities = [];
        foreach ($sizes as $size) {
            $good = $receivedGoodQuantities[$size->id] ?? 0;
            $waste = $receivedWasteQuantities[$size->id] ?? 0;
            $totalReceivedQuantities[$size->id] = $good + $waste;
        }

        // Calculate available quantities (sent - total received including waste)
        foreach ($sizes as $size) {
            $sent = $sentQuantities[$size->id] ?? 0;
            $received = $totalReceivedQuantities[$size->id] ?? 0;
            $availableQuantities[$size->id] = max(0, $sent - $received);
        }

        return response()->json([
            'availableQuantities' => $availableQuantities,
            'sizes' => $sizes
        ]);
    }

    // Update the find method to use PO-filtered available quantities
    // public function find(Request $request)
    // {
    //     $poNumbers = $request->input('po_numbers', []);

    //     if (empty($poNumbers)) {
    //         return response()->json([]);
    //     }

    //     $result = [];
    //     $processedCombinations = [];

    //     // dd($poNumbers);

    //     foreach ($poNumbers as $poNumber) {
    //         // Get print send data for the selected PO number
    //         $printSendData = PrintSendData::where('po_number', 'like', '%' . $poNumber . '%')
    //             ->with(['productCombination.style', 'productCombination.color'])
    //             ->get();

    //         foreach ($printSendData as $data) {
    //             if (!$data->productCombination) {
    //                 continue;
    //             }

    //             // Create a unique key for this combination
    //             $combinationKey = $data->productCombination->id . '-' .
    //                 $data->productCombination->style->name . '-' .
    //                 $data->productCombination->color->name;

    //             // Skip if we've already processed this combination
    //             if (in_array($combinationKey, $processedCombinations)) {
    //                 continue;
    //             }

    //             // Mark this combination as processed
    //             $processedCombinations[] = $combinationKey;

    //             // Get available quantities filtered by PO number
    //             $availableQuantities = $this->getAvailableReceiveQuantities($data->productCombination, $poNumber)->getData()->availableQuantities;

    //             $result[$poNumber][] = [
    //                 'combination_id' => $data->productCombination->id,
    //                 'style' => $data->productCombination->style->name,
    //                 'color' => $data->productCombination->color->name,
    //                 'available_quantities' => $availableQuantities,
    //                 'size_ids' => $data->productCombination->sizes->pluck('id')->toArray()
    //             ];
    //         }
    //     }

    //     // dd($result);

    //     return response()->json($result);
    // }

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
            // Get print send data for the selected PO number
            $printSendData = PrintSendData::where('po_number', 'like', '%' . $poNumber . '%')
                ->with(['productCombination.style', 'productCombination.color'])
                ->get();

            // Create a processed combinations array PER PO NUMBER
            $processedCombinationsForPo = [];

            foreach ($printSendData as $data) {
                if (!$data->productCombination) {
                    continue;
                }

                // Create a unique key for this combination WITHIN THIS PO
                $combinationKey = $data->productCombination->id . '-' .
                    $data->productCombination->style->name . '-' .
                    $data->productCombination->color->name;

                // Skip if we've already processed this combination FOR THIS PO
                if (in_array($combinationKey, $processedCombinationsForPo)) {
                    continue;
                }

                // Mark this combination as processed FOR THIS PO
                $processedCombinationsForPo[] = $combinationKey;

                // Get available quantities filtered by PO number
                $availableQuantities = $this->getAvailableReceiveQuantities($data->productCombination, $poNumber)->getData()->availableQuantities;

                $result[$poNumber][] = [
                    'combination_id' => $data->productCombination->id,
                    'style' => $data->productCombination->style->name,
                    'color' => $data->productCombination->color->name,
                    'available_quantities' => $availableQuantities,
                    'size_ids' => $data->productCombination->sizes->pluck('id')->toArray()
                ];
            }
        }

        return response()->json($result);
    }

    // Update the edit method to use PO-filtered available quantities
    public function edit(PrintReceiveData $printReceiveDatum)
    {
        $printReceiveDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Convert JSON objects to arrays if needed
        if (is_object($printReceiveDatum->receive_quantities)) {
            $printReceiveDatum->receive_quantities = (array) $printReceiveDatum->receive_quantities;
        }

        if (is_object($printReceiveDatum->receive_waste_quantities)) {
            $printReceiveDatum->receive_waste_quantities = (array) $printReceiveDatum->receive_waste_quantities;
        }

        // Only show sizes that have data
        $validSizes = $allSizes->filter(function ($size) use ($printReceiveDatum) {
            return isset($printReceiveDatum->receive_quantities[$size->id]) ||
                isset($printReceiveDatum->receive_waste_quantities[$size->id]);
        });

        $allSizes = $validSizes->values();

        // Get available quantities filtered by PO number
        // $availableResponse = $this->getAvailableReceiveQuantities($printReceiveDatum->productCombination, $printReceiveDatum->po_number);
        // $availableData = json_decode($availableResponse->content(), true);
        // $availableQuantities = $availableData['availableQuantities'] ?? [];

        // // Ensure availableQuantities is an array
        // if (is_object($availableQuantities)) {
        //     $availableQuantities = (array) $availableQuantities;
        // }

        $availableQuantities = $this->getAvailableReceiveQuantities($printReceiveDatum->productCombination, $printReceiveDatum->po_number)->getData()->availableQuantities;
        // dd($availableQuantities);
        if (is_object($availableQuantities)) {
            $availableQuantities = (array) $availableQuantities;
        }


        return view('backend.library.print_receive_data.edit', compact('printReceiveDatum', 'allSizes', 'availableQuantities'));
    }

    public function totalPrintEmbReceiveReport(Request $request)
    {
        $query = PrintReceiveData::with('productCombination.style', 'productCombination.color');

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

        $printReceiveData = $query->get();
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Create a map for size ID to size name and vice versa
        $sizeIdToName = $allSizes->pluck('name', 'id')->toArray();
        $sizeNameToId = $allSizes->pluck('id', 'name')->map(fn($id) => strtolower($allSizes->firstWhere('id', $id)->name))->toArray();

        $reportData = [];

        foreach ($printReceiveData as $data) {
            $style = $data->productCombination->style->name;
            $color = $data->productCombination->color->name;
            $key = $style . '-' . $color;

            if (!isset($reportData[$key])) {
                $reportData[$key] = [
                    'style' => $style,
                    'color' => $color,
                    // Initialize with size IDs as keys for quantities
                    'sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
                    'waste_sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
                    'total' => 0,
                    'total_waste' => 0
                ];
            }

            // Quantities are stored with size IDs as keys
            foreach ($data->receive_quantities as $sizeId => $qty) {
                if (array_key_exists($sizeId, $reportData[$key]['sizes'])) {
                    $reportData[$key]['sizes'][$sizeId] += $qty;
                }
            }
            $reportData[$key]['total'] += $data->total_receive_quantity;

            if ($data->receive_waste_quantities) {
                foreach ($data->receive_waste_quantities as $sizeId => $qty) {
                    if (array_key_exists($sizeId, $reportData[$key]['waste_sizes'])) {
                        $reportData[$key]['waste_sizes'][$sizeId] += $qty;
                    }
                }
            }
            $reportData[$key]['total_waste'] += $data->total_receive_waste_quantity ?? 0;
        }

        // Get filter options
        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = PrintReceiveData::distinct()->pluck('po_number')->filter()->values();

        return view('backend.library.print_receive_data.reports.total_receive', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes,
            'sizeIdToName' => $sizeIdToName,
            'allStyles' => $allStyles,
            'allColors' => $allColors,
            'distinctPoNumbers' => $distinctPoNumbers
        ]);
    }

    public function totalPrintEmbBalanceReport(Request $request)
    {
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $sizeIdToName = $allSizes->pluck('name', 'id')->toArray();

        // Get filter parameters
        $styleIds = $request->input('style_id', []);
        $colorIds = $request->input('color_id', []);
        $poNumbers = $request->input('po_number', []);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');

        $balanceData = [];

        // Base query for product combinations
        $productCombinationsQuery = ProductCombination::where(function ($q) {
            $q->whereHas('printSends')
                ->orWhereHas('printReceives');
        })->with('style', 'color');

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

            if (!isset($balanceData[$key])) {
                $balanceData[$key] = [
                    'style' => $style,
                    'color' => $color,
                    // Initialize with size IDs as keys
                    'sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), ['sent' => 0, 'received' => 0, 'balance' => 0, 'waste' => 0]),
                    'total_sent' => 0,
                    'total_received' => 0,
                    'total_waste' => 0,
                    'total_balance' => 0,
                ];
            }

            // Aggregate sent quantities with filters
            $sentDataQuery = PrintSendData::where('product_combination_id', $pc->id);

            // Apply PO number filter
            if (!empty($poNumbers)) {
                $sentDataQuery->whereIn('po_number', $poNumbers);
            }

            // Apply date filter
            if ($startDate && $endDate) {
                $sentDataQuery->whereBetween('date', [$startDate, $endDate]);
            }

            $sentData = $sentDataQuery->get();

            foreach ($sentData as $data) {
                foreach ($data->send_quantities as $sizeId => $qty) {
                    if (isset($balanceData[$key]['sizes'][$sizeId])) {
                        $balanceData[$key]['sizes'][$sizeId]['sent'] += $qty;
                    }
                }
                $balanceData[$key]['total_sent'] += $data->total_send_quantity;
            }

            // Aggregate received quantities and waste with filters
            $receiveDataQuery = PrintReceiveData::where('product_combination_id', $pc->id);

            // Apply PO number filter
            if (!empty($poNumbers)) {
                $receiveDataQuery->whereIn('po_number', $poNumbers);
            }

            // Apply date filter
            if ($startDate && $endDate) {
                $receiveDataQuery->whereBetween('date', [$startDate, $endDate]);
            }

            $receiveData = $receiveDataQuery->get();

            foreach ($receiveData as $data) {
                foreach ($data->receive_quantities as $sizeId => $qty) {
                    if (isset($balanceData[$key]['sizes'][$sizeId])) {
                        $balanceData[$key]['sizes'][$sizeId]['received'] += $qty;
                    }
                }
                $balanceData[$key]['total_received'] += $data->total_receive_quantity;

                if ($data->receive_waste_quantities) {
                    foreach ($data->receive_waste_quantities as $sizeId => $qty) {
                        if (isset($balanceData[$key]['sizes'][$sizeId])) {
                            $balanceData[$key]['sizes'][$sizeId]['waste'] += $qty;
                        }
                    }
                }
                $balanceData[$key]['total_waste'] += $data->total_receive_waste_quantity ?? 0;
            }

            // Calculate balance per size and total balance
            foreach ($balanceData[$key]['sizes'] as $sizeId => &$sizeData) {
                $sizeData['balance'] = $sizeData['sent'] - $sizeData['received'] - $sizeData['waste'];
            }
            unset($sizeData);

            $balanceData[$key]['total_balance'] = $balanceData[$key]['total_sent'] - $balanceData[$key]['total_received'] - $balanceData[$key]['total_waste'];

            // Remove if total balance is 0 or less unless filters are applied
            if ($balanceData[$key]['total_balance'] <= 0 && (!$startDate && !$endDate && empty($poNumbers) && empty($styleIds) && empty($colorIds) && !$search)) {
                unset($balanceData[$key]);
            }
        }

        // Get filter options
        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = array_unique(
            array_merge(
                PrintSendData::distinct()->pluck('po_number')->filter()->values()->toArray(),
                PrintReceiveData::distinct()->pluck('po_number')->filter()->values()->toArray()
            )
        );
        sort($distinctPoNumbers);

        return view('backend.library.print_receive_data.reports.balance_quantity', [
            'reportData' => array_values($balanceData),
            'allSizes' => $allSizes,
            'sizeIdToName' => $sizeIdToName,
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
        $sizeIdToName = $allSizes->pluck('name', 'id')->toArray();
        $wipData = [];

        foreach ($combinations as $pc) {
            // Apply filters to sent and received data
            $sentDataQuery = PrintSendData::where('product_combination_id', $pc->id);
            $receiveDataQuery = PrintReceiveData::where('product_combination_id', $pc->id);

            // Apply PO number filter
            if (!empty($poNumbers)) {
                $sentDataQuery->whereIn('po_number', $poNumbers);
                $receiveDataQuery->whereIn('po_number', $poNumbers);
            }

            // Apply date filter
            if ($startDate && $endDate) {
                $sentDataQuery->whereBetween('date', [$startDate, $endDate]);
                $receiveDataQuery->whereBetween('date', [$startDate, $endDate]);
            }

            $totalSent = $sentDataQuery->sum('total_send_quantity');
            $totalReceived = $receiveDataQuery->sum('total_receive_quantity');
            $totalReceivedWaste = $receiveDataQuery->sum('total_receive_waste_quantity');

            $currentWaiting = $totalSent - $totalReceived - $totalReceivedWaste;

            // Skip if no data matches the filters
            if ($totalSent == 0) continue;

            if ($currentWaiting > 0) {
                $key = $pc->style->name . '-' . $pc->color->name;

                if (!isset($wipData[$key])) {
                    $wipData[$key] = [
                        'style' => $pc->style->name,
                        'color' => $pc->color->name,
                        'sizes' => [], // Initialize with empty array, will be populated with size IDs
                        'total_sent' => 0,
                        'total_received' => 0,
                        'total_received_waste' => 0,
                        'waiting' => 0
                    ];

                    foreach ($allSizes as $size) {
                        $wipData[$key]['sizes'][$size->id] = [ // Use size ID as key
                            'sent' => 0,
                            'received' => 0,
                            'waste' => 0,
                            'waiting' => 0
                        ];
                    }
                }

                $wipData[$key]['total_sent'] += $totalSent;
                $wipData[$key]['total_received'] += $totalReceived;
                $wipData[$key]['total_received_waste'] += $totalReceivedWaste;
                $wipData[$key]['waiting'] += $currentWaiting;

                // Aggregate size quantities for sent
                $sendData = $sentDataQuery->get();
                foreach ($sendData as $sd) {
                    foreach ($sd->send_quantities as $sizeId => $qty) {
                        if (isset($wipData[$key]['sizes'][$sizeId])) {
                            $wipData[$key]['sizes'][$sizeId]['sent'] += $qty;
                        }
                    }
                }

                // Aggregate size quantities for received (good) and waste
                $receiveData = $receiveDataQuery->get();
                foreach ($receiveData as $rd) {
                    foreach ($rd->receive_quantities as $sizeId => $qty) {
                        if (isset($wipData[$key]['sizes'][$sizeId])) {
                            $wipData[$key]['sizes'][$sizeId]['received'] += $qty;
                        }
                    }
                    if ($rd->receive_waste_quantities) {
                        foreach ($rd->receive_waste_quantities as $sizeId => $qty) {
                            if (isset($wipData[$key]['sizes'][$sizeId])) {
                                $wipData[$key]['sizes'][$sizeId]['waste'] += $qty;
                            }
                        }
                    }
                }

                // Calculate waiting per size (sent - received - waste)
                foreach ($wipData[$key]['sizes'] as $sizeId => &$data) {
                    $data['waiting'] = $data['sent'] - $data['received'] - $data['waste'];
                }
                unset($data);
            }
        }

        // Get filter options
        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = array_unique(
            array_merge(
                PrintSendData::distinct()->pluck('po_number')->filter()->values()->toArray(),
                PrintReceiveData::distinct()->pluck('po_number')->filter()->values()->toArray()
            )
        );
        sort($distinctPoNumbers);

        return view('backend.library.print_receive_data.reports.wip', [
            'wipData' => array_values($wipData),
            'allSizes' => $allSizes,
            'sizeIdToName' => $sizeIdToName,
            'allStyles' => $allStyles,
            'allColors' => $allColors,
            'distinctPoNumbers' => $distinctPoNumbers
        ]);
    }

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

        return view('backend.library.print_receive_data.reports.ready', [
            'readyData' => $allReportData,
            'allStyles' => $allStyles,
            'allColors' => $allColors,
            'distinctPoNumbers' => $distinctPoNumbers,
            'allSizes' => $allSizes, // Pass all sizes to the view for dynamic headers
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

    //     // Product combinations with print_embroidery = false OR sublimation_print = false
    //     $nonEmbCombinationsQuery = ProductCombination::where(function ($query) {
    //         $query->where('print_embroidery', false)
    //             ->orWhere('sublimation_print', false);
    //     })->with('style', 'color');

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
    //         // Apply filters to cutting data
    //         $cuttingDataQuery = CuttingData::where('product_combination_id', $pc->id);

    //         // Apply PO number filter
    //         if (!empty($poNumbers)) {
    //             $cuttingDataQuery->whereIn('po_number', $poNumbers);
    //         }

    //         // Apply date filter
    //         if ($startDate && $endDate) {
    //             $cuttingDataQuery->whereBetween('created_at', [$startDate, $endDate]);
    //         }

    //         $cuttingData = $cuttingDataQuery->get();

    //         // Skip if no cutting data matches the filters
    //         if ($cuttingData->isEmpty()) continue;

    //         // Calculate total_cut dynamically by summing from 'cut_quantities' JSON
    //         $dynamicTotalCut = 0;
    //         foreach ($cuttingData as $cut) {
    //             foreach ($cut->cut_quantities as $qty) {
    //                 $dynamicTotalCut += $qty;
    //             }
    //         }

    //         // Skip if no cut quantities match the filters
    //         if ($dynamicTotalCut == 0) continue;

    //         $readyData[] = [
    //             'style' => $pc->style->name,
    //             'color' => $pc->color->name,
    //             'type' => 'No Print/Emb Needed',
    //             'total_cut' => $dynamicTotalCut, // Use dynamic total cut
    //             'total_sent' => 0,
    //             'total_received_good' => 0,
    //             'total_received_waste' => 0,
    //         ];
    //     }

    //     // Product combinations with print_embroidery = true OR sublimation_print = true
    //     $embCombinationsQuery = ProductCombination::where(function ($query) {
    //         $query->where('print_embroidery', true)
    //             ->orWhere('sublimation_print', true);
    //     })->with('style', 'color');

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
    //         // Apply filters to cutting data
    //         $cuttingDataQuery = CuttingData::where('product_combination_id', $pc->id);
    //         $printSendDataQuery = PrintSendData::where('product_combination_id', $pc->id);
    //         $printReceiveDataQuery = PrintReceiveData::where('product_combination_id', $pc->id);

    //         // Apply PO number filter
    //         if (!empty($poNumbers)) {
    //             $cuttingDataQuery->whereIn('po_number', $poNumbers);
    //             $printSendDataQuery->whereIn('po_number', $poNumbers);
    //             $printReceiveDataQuery->whereIn('po_number', $poNumbers);
    //         }

    //         // Apply date filter
    //         if ($startDate && $endDate) {
    //             $cuttingDataQuery->whereBetween('created_at', [$startDate, $endDate]);
    //             $printSendDataQuery->whereBetween('date', [$startDate, $endDate]);
    //             $printReceiveDataQuery->whereBetween('date', [$startDate, $endDate]);
    //         }

    //         $cuttingData = $cuttingDataQuery->get();
    //         $printSendData = $printSendDataQuery->get();
    //         $printReceiveData = $printReceiveDataQuery->get();

    //         // Skip if no cutting data matches the filters
    //         if ($cuttingData->isEmpty()) continue;

    //         // Calculate total_cut dynamically
    //         $dynamicTotalCut = 0;
    //         foreach ($cuttingData as $cut) {
    //             foreach ($cut->cut_quantities as $qty) {
    //                 $dynamicTotalCut += $qty;
    //             }
    //         }

    //         // Skip if no cut quantities match the filters
    //         if ($dynamicTotalCut == 0) continue;

    //         // Calculate total_sent dynamically by summing from 'send_quantities' JSON
    //         $dynamicTotalSent = 0;
    //         foreach ($printSendData as $send) {
    //             foreach ($send->send_quantities as $qty) {
    //                 $dynamicTotalSent += $qty;
    //             }
    //         }

    //         // Calculate total_received_good dynamically by summing from 'receive_quantities' JSON
    //         $dynamicTotalReceivedGood = 0;
    //         foreach ($printReceiveData as $receive) {
    //             foreach ($receive->receive_quantities as $qty) {
    //                 $dynamicTotalReceivedGood += $qty;
    //             }
    //         }

    //         // Calculate total_received_waste dynamically by summing from 'receive_waste_quantities' JSON
    //         $dynamicTotalReceivedWaste = 0;
    //         foreach ($printReceiveData as $receive) {
    //             if ($receive->receive_waste_quantities) {
    //                 foreach ($receive->receive_waste_quantities as $qty) {
    //                     $dynamicTotalReceivedWaste += $qty;
    //                 }
    //             }
    //         }

    //         // "Ready to input" means either no print/emb needed OR all sent items have been received (good quantity only).
    //         // Here, dynamicTotalSent must match dynamicTotalReceivedGood to be 'ready'.
    //         if ($dynamicTotalSent > 0 && $dynamicTotalSent == $dynamicTotalReceivedGood && $dynamicTotalReceivedWaste >= 0) {
    //             $readyData[] = [
    //                 'style' => $pc->style->name,
    //                 'color' => $pc->color->name,
    //                 'type' => 'Print/Emb Completed',
    //                 'total_cut' => $dynamicTotalCut,
    //                 'total_sent' => $dynamicTotalSent,
    //                 'total_received_good' => $dynamicTotalReceivedGood,
    //                 'total_received_waste' => $dynamicTotalReceivedWaste,
    //             ];
    //         }
    //     }

    //     // Get filter options
    //     $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
    //     $allColors = Color::where('is_active', 1)->orderBy('name')->get();
    //     $distinctPoNumbers = array_unique(
    //         array_merge(
    //             CuttingData::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //             PrintSendData::distinct()->pluck('po_number')->filter()->values()->toArray(),
    //             PrintReceiveData::distinct()->pluck('po_number')->filter()->values()->toArray()
    //         )
    //     );
    //     sort($distinctPoNumbers);

    //     return view('backend.library.print_receive_data.reports.ready', [
    //         'readyData' => $readyData,
    //         'allStyles' => $allStyles,
    //         'allColors' => $allColors,
    //         'distinctPoNumbers' => $distinctPoNumbers
    //     ]);
    // }





    // public function totalPrintEmbReceiveReport(Request $request)
    // {
    //     $query = PrintReceiveData::with('productCombination.style', 'productCombination.color');

    //     if ($request->filled('start_date') && $request->filled('end_date')) {
    //         $query->whereBetween('date', [$request->start_date, $request->end_date]);
    //     }

    //     $printReceiveData = $query->get();
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //     // Create a map for size ID to size name and vice versa
    //     $sizeIdToName = $allSizes->pluck('name', 'id')->toArray();
    //     $sizeNameToId = $allSizes->pluck('id', 'name')->map(fn($id) => strtolower($allSizes->firstWhere('id', $id)->name))->toArray(); // Ensure keys are lowercase size names

    //     $reportData = [];

    //     foreach ($printReceiveData as $data) {
    //         $style = $data->productCombination->style->name;
    //         $color = $data->productCombination->color->name;
    //         $key = $style . '-' . $color;

    //         if (!isset($reportData[$key])) {
    //             $reportData[$key] = [
    //                 'style' => $style,
    //                 'color' => $color,
    //                 // Initialize with size IDs as keys for quantities
    //                 'sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
    //                 'waste_sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
    //                 'total' => 0,
    //                 'total_waste' => 0
    //             ];
    //         }

    //         // Quantities are stored with size IDs as keys
    //         foreach ($data->receive_quantities as $sizeId => $qty) {
    //             if (array_key_exists($sizeId, $reportData[$key]['sizes'])) {
    //                 $reportData[$key]['sizes'][$sizeId] += $qty;
    //             }
    //         }
    //         $reportData[$key]['total'] += $data->total_receive_quantity;

    //         if ($data->receive_waste_quantities) {
    //             foreach ($data->receive_waste_quantities as $sizeId => $qty) {
    //                 if (array_key_exists($sizeId, $reportData[$key]['waste_sizes'])) {
    //                     $reportData[$key]['waste_sizes'][$sizeId] += $qty;
    //                 }
    //             }
    //         }
    //         $reportData[$key]['total_waste'] += $data->total_receive_waste_quantity ?? 0;
    //     }

    //     return view('backend.library.print_receive_data.reports.total_receive', [
    //         'reportData' => array_values($reportData),
    //         'allSizes' => $allSizes,
    //         'sizeIdToName' => $sizeIdToName, // Pass the map to the view
    //     ]);
    // }

    // public function totalPrintEmbBalanceReport(Request $request)
    // {
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $sizeIdToName = $allSizes->pluck('name', 'id')->toArray();
    //     $sizeNameToId = $allSizes->pluck('id', 'name')->map(fn($id) => strtolower($allSizes->firstWhere('id', $id)->name))->toArray();

    //     $balanceData = [];

    //     $productCombinations = ProductCombination::whereHas('printSends')
    //         ->orWhereHas('printReceives')
    //         ->with('style', 'color')
    //         ->get();

    //     foreach ($productCombinations as $pc) {
    //         $style = $pc->style->name;
    //         $color = $pc->color->name;
    //         $key = $style . '-' . $color;

    //         if (!isset($balanceData[$key])) {
    //             $balanceData[$key] = [
    //                 'style' => $style,
    //                 'color' => $color,
    //                 // Initialize with size IDs as keys
    //                 'sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), ['sent' => 0, 'received' => 0, 'balance' => 0, 'waste' => 0]),
    //                 'total_sent' => 0,
    //                 'total_received' => 0,
    //                 'total_waste' => 0,
    //                 'total_balance' => 0,
    //             ];
    //         }


    //         // Aggregate sent quantities
    //         $sentData = PrintSendData::where('product_combination_id', $pc->id);
    //         if ($request->filled('start_date') && $request->filled('end_date')) {
    //             $sentData->whereBetween('date', [$request->start_date, $request->end_date]);
    //         }
    //         $sentData = $sentData->get();

    //         foreach ($sentData as $data) {
    //             foreach ($data->send_quantities as $sizeId => $qty) { // Assuming send_quantities also uses size IDs
    //                 if (isset($balanceData[$key]['sizes'][$sizeId])) {
    //                     $balanceData[$key]['sizes'][$sizeId]['sent'] += $qty;
    //                 }
    //             }
    //             $balanceData[$key]['total_sent'] += $data->total_send_quantity;
    //         }

    //         // Aggregate received quantities and waste
    //         $receiveData = PrintReceiveData::where('product_combination_id', $pc->id);
    //         if ($request->filled('start_date') && $request->filled('end_date')) {
    //             $receiveData->whereBetween('date', [$request->start_date, $request->end_date]);
    //         }
    //         $receiveData = $receiveData->get();

    //         foreach ($receiveData as $data) {
    //             foreach ($data->receive_quantities as $sizeId => $qty) {
    //                 if (isset($balanceData[$key]['sizes'][$sizeId])) {
    //                     $balanceData[$key]['sizes'][$sizeId]['received'] += $qty;
    //                 }
    //             }
    //             $balanceData[$key]['total_received'] += $data->total_receive_quantity;

    //             if ($data->receive_waste_quantities) {
    //                 foreach ($data->receive_waste_quantities as $sizeId => $qty) {
    //                     if (isset($balanceData[$key]['sizes'][$sizeId])) {
    //                         $balanceData[$key]['sizes'][$sizeId]['waste'] += $qty;
    //                     }
    //                 }
    //             }
    //             $balanceData[$key]['total_waste'] += $data->total_receive_waste_quantity ?? 0;
    //         }

    //         // Calculate balance per size and total balance
    //         foreach ($balanceData[$key]['sizes'] as $sizeId => &$sizeData) {
    //             $sizeData['balance'] = $sizeData['sent'] - $sizeData['received'] - $sizeData['waste'];
    //         }
    //         unset($sizeData);

    //         $balanceData[$key]['total_balance'] = $balanceData[$key]['total_sent'] - $balanceData[$key]['total_received'] - $balanceData[$key]['total_waste'];

    //         if ($balanceData[$key]['total_balance'] <= 0 && (!$request->filled('start_date') && !$request->filled('end_date'))) {
    //             unset($balanceData[$key]);
    //         }
    //     }

    //     return view('backend.library.print_receive_data.reports.balance_quantity', [
    //         'reportData' => array_values($balanceData),
    //         'allSizes' => $allSizes,
    //         'sizeIdToName' => $sizeIdToName,
    //     ]);
    // }

    // public function wipReport(Request $request)
    // {
    //     $combinations = ProductCombination::where('print_embroidery', true)
    //         ->with('style', 'color')
    //         ->get();

    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $sizeIdToName = $allSizes->pluck('name', 'id')->toArray();
    //     $sizeNameToId = $allSizes->pluck('id', 'name')->map(fn($id) => strtolower($allSizes->firstWhere('id', $id)->name))->toArray();

    //     $wipData = [];

    //     foreach ($combinations as $pc) {
    //         $totalSent = PrintSendData::where('product_combination_id', $pc->id)
    //             ->sum('total_send_quantity');

    //         $totalReceived = PrintReceiveData::where('product_combination_id', $pc->id)
    //             ->sum('total_receive_quantity');

    //         $totalReceivedWaste = PrintReceiveData::where('product_combination_id', $pc->id)
    //             ->sum('total_receive_waste_quantity');

    //         $currentWaiting = $totalSent - $totalReceived - $totalReceivedWaste;

    //         if ($currentWaiting > 0) {
    //             $key = $pc->style->name . '-' . $pc->color->name;

    //             if (!isset($wipData[$key])) {
    //                 $wipData[$key] = [
    //                     'style' => $pc->style->name,
    //                     'color' => $pc->color->name,
    //                     'sizes' => [], // Initialize with empty array, will be populated with size IDs
    //                     'total_sent' => 0,
    //                     'total_received' => 0,
    //                     'total_received_waste' => 0,
    //                     'waiting' => 0
    //                 ];

    //                 foreach ($allSizes as $size) {
    //                     $wipData[$key]['sizes'][$size->id] = [ // Use size ID as key
    //                         'sent' => 0,
    //                         'received' => 0,
    //                         'waste' => 0,
    //                         'waiting' => 0
    //                     ];
    //                 }
    //             }

    //             $wipData[$key]['total_sent'] += $totalSent;
    //             $wipData[$key]['total_received'] += $totalReceived;
    //             $wipData[$key]['total_received_waste'] += $totalReceivedWaste;
    //             $wipData[$key]['waiting'] += $currentWaiting;

    //             // Aggregate size quantities for sent
    //             $sendData = PrintSendData::where('product_combination_id', $pc->id)->get();
    //             foreach ($sendData as $sd) {
    //                 foreach ($sd->send_quantities as $sizeId => $qty) { // Assuming send_quantities also uses size IDs
    //                     if (isset($wipData[$key]['sizes'][$sizeId])) {
    //                         $wipData[$key]['sizes'][$sizeId]['sent'] += $qty;
    //                     }
    //                 }
    //             }

    //             // Aggregate size quantities for received (good) and waste
    //             $receiveData = PrintReceiveData::where('product_combination_id', $pc->id)->get();
    //             foreach ($receiveData as $rd) {
    //                 foreach ($rd->receive_quantities as $sizeId => $qty) {
    //                     if (isset($wipData[$key]['sizes'][$sizeId])) {
    //                         $wipData[$key]['sizes'][$sizeId]['received'] += $qty;
    //                     }
    //                 }
    //                 if ($rd->receive_waste_quantities) {
    //                     foreach ($rd->receive_waste_quantities as $sizeId => $qty) {
    //                         if (isset($wipData[$key]['sizes'][$sizeId])) {
    //                             $wipData[$key]['sizes'][$sizeId]['waste'] += $qty;
    //                         }
    //                     }
    //                 }
    //             }

    //             // Calculate waiting per size (sent - received - waste)
    //             foreach ($wipData[$key]['sizes'] as $sizeId => &$data) {
    //                 $data['waiting'] = $data['sent'] - $data['received'] - $data['waste'];
    //             }
    //             unset($data);
    //         }
    //     }

    //     return view('backend.library.print_receive_data.reports.wip', [
    //         'wipData' => array_values($wipData),
    //         'allSizes' => $allSizes,
    //         'sizeIdToName' => $sizeIdToName,
    //     ]);
    // }

    // public function readyToInputReport(Request $request)
    // {
    //     $readyData = [];

    //     // Product combinations with print_embroidery = false OR sublimation_print = false
    //     $nonEmbCombinations = ProductCombination::where(function ($query) {
    //         $query->where('print_embroidery', false)
    //             ->orWhere('sublimation_print', false);
    //     })
    //         ->with('style', 'color')
    //         ->get();

    //     // dd($nonEmbCombinations);

    //     foreach ($nonEmbCombinations as $pc) {
    //         // Calculate total_cut dynamically by summing from 'cut_quantities' JSON
    //         $cuttingData = CuttingData::where('product_combination_id', $pc->id)->get();
    //         $dynamicTotalCut = 0;
    //         foreach ($cuttingData as $cut) {
    //             foreach ($cut->cut_quantities as $qty) {
    //                 $dynamicTotalCut += $qty;
    //             }
    //         }

    //         $readyData[] = [
    //             'style' => $pc->style->name,
    //             'color' => $pc->color->name,
    //             'type' => 'No Print/Emb Needed',
    //             'total_cut' => $dynamicTotalCut, // Use dynamic total cut
    //             'total_sent' => 0,
    //             'total_received_good' => 0,
    //             'total_received_waste' => 0,
    //         ];
    //     }

    //     // dd($readyData);

    //     // Product combinations with print_embroidery = true OR sublimation_print = true
    //     $embCombinations = ProductCombination::where(function ($query) {
    //         $query->where('print_embroidery', true)
    //             ->orWhere('sublimation_print', true);
    //     })
    //         ->with('style', 'color')
    //         ->get();

    //     foreach ($embCombinations as $pc) {
    //         // Calculate total_cut dynamically
    //         $cuttingData = CuttingData::where('product_combination_id', $pc->id)->get();
    //         $dynamicTotalCut = 0;
    //         foreach ($cuttingData as $cut) {
    //             foreach ($cut->cut_quantities as $qty) {
    //                 $dynamicTotalCut += $qty;
    //             }
    //         }

    //         // Calculate total_sent dynamically by summing from 'send_quantities' JSON
    //         $printSendData = PrintSendData::where('product_combination_id', $pc->id)->get();
    //         $dynamicTotalSent = 0;
    //         foreach ($printSendData as $send) {
    //             foreach ($send->send_quantities as $qty) {
    //                 $dynamicTotalSent += $qty;
    //             }
    //         }

    //         // Calculate total_received_good dynamically by summing from 'receive_quantities' JSON
    //         $printReceiveData = PrintReceiveData::where('product_combination_id', $pc->id)->get();
    //         $dynamicTotalReceivedGood = 0;
    //         foreach ($printReceiveData as $receive) {
    //             foreach ($receive->receive_quantities as $qty) {
    //                 $dynamicTotalReceivedGood += $qty;
    //             }
    //         }

    //         // Calculate total_received_waste dynamically by summing from 'receive_waste_quantities' JSON
    //         $dynamicTotalReceivedWaste = 0;
    //         foreach ($printReceiveData as $receive) {
    //             if ($receive->receive_waste_quantities) {
    //                 foreach ($receive->receive_waste_quantities as $qty) {
    //                     $dynamicTotalReceivedWaste += $qty;
    //                 }
    //             }
    //         }

    //         // "Ready to input" means either no print/emb needed OR all sent items have been received (good quantity only).
    //         // Here, dynamicTotalSent must match dynamicTotalReceivedGood to be 'ready'.
    //         if ($dynamicTotalSent > 0 && $dynamicTotalSent == $dynamicTotalReceivedGood && $dynamicTotalReceivedWaste >= 0) {
    //             $readyData[] = [
    //                 'style' => $pc->style->name,
    //                 'color' => $pc->color->name,
    //                 'type' => 'Print/Emb Completed',
    //                 'total_cut' => $dynamicTotalCut,
    //                 'total_sent' => $dynamicTotalSent,
    //                 'total_received_good' => $dynamicTotalReceivedGood,
    //                 'total_received_waste' => $dynamicTotalReceivedWaste,
    //             ];
    //         }
    //     }

    //     return view('backend.library.print_receive_data.reports.ready', compact('readyData'));
    // }


    // public function index(Request $request)
    // {
    //     $query = PrintReceiveData::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

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

    //     $printReceiveData = $query->orderBy('date', 'desc')->paginate(10);
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //     return view('backend.library.print_receive_data.index', compact('printReceiveData', 'allSizes'));
    // }

    // public function edit(PrintReceiveData $printReceiveDatum)
    // {
    //     $printReceiveDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //     //only valid sizes
    //     $validSizes = $allSizes->filter(function ($size) use ($printReceiveDatum) {
    //         return isset($printReceiveDatum->receive_quantities[$size->id]) ||
    //             isset($printReceiveDatum->receive_waste_quantities[$size->id]);
    //     });

    //     $allSizes = $validSizes->values();

    //     $availableQuantities = $this->getAvailableReceiveQuantities($printReceiveDatum->productCombination)->getData()->availableQuantities;

    //     // dd($availableQuantities);

    //     return view('backend.library.print_receive_data.edit', compact('printReceiveDatum', 'allSizes', 'availableQuantities'));
    // }


    // public function edit(PrintReceiveData $printReceiveDatum)
    // {
    //     $printReceiveDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //     // Convert JSON objects to arrays if needed
    //     if (is_object($printReceiveDatum->receive_quantities)) {
    //         $printReceiveDatum->receive_quantities = (array) $printReceiveDatum->receive_quantities;
    //     }

    //     if (is_object($printReceiveDatum->receive_waste_quantities)) {
    //         $printReceiveDatum->receive_waste_quantities = (array) $printReceiveDatum->receive_waste_quantities;
    //     }

    //     // Only show sizes that have data
    //     $validSizes = $allSizes->filter(function ($size) use ($printReceiveDatum) {
    //         return isset($printReceiveDatum->receive_quantities[$size->id]) ||
    //             isset($printReceiveDatum->receive_waste_quantities[$size->id]);
    //     });

    //     $allSizes = $validSizes->values();

    //     $availableQuantities = $this->getAvailableReceiveQuantities($printReceiveDatum->productCombination)->getData()->availableQuantities;

    //     // Ensure availableQuantities is an array
    //     if (is_object($availableQuantities)) {
    //         $availableQuantities = (array) $availableQuantities;
    //     }

    //     return view('backend.library.print_receive_data.edit', compact('printReceiveDatum', 'allSizes', 'availableQuantities'));
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
    //         // Get print send data for the selected PO number
    //         $printSendData = PrintSendData::where('po_number', 'like', '%' . $poNumber . '%')
    //             ->with(['productCombination.style', 'productCombination.color'])
    //             ->get();

    //         foreach ($printSendData as $data) {
    //             if (!$data->productCombination) {
    //                 continue;
    //             }

    //             // Create a unique key for this combination
    //             $combinationKey = $data->productCombination->id . '-' .
    //                 $data->productCombination->style->name . '-' .
    //                 $data->productCombination->color->name;

    //             // Skip if we've already processed this combination
    //             if (in_array($combinationKey, $processedCombinations)) {
    //                 continue;
    //             }

    //             // Mark this combination as processed
    //             $processedCombinations[] = $combinationKey;

    //             $availableQuantities = $this->getAvailableReceiveQuantities($data->productCombination)->getData()->availableQuantities;

    //             $result[$poNumber][] = [
    //                 'combination_id' => $data->productCombination->id,
    //                 'style' => $data->productCombination->style->name,
    //                 'color' => $data->productCombination->color->name,
    //                 'available_quantities' => $availableQuantities,
    //                 'size_ids' => $data->productCombination->sizes->pluck('id')->toArray()
    //             ];
    //         }
    //     }

    //     return response()->json($result);
    // }

    // public function getAvailableReceiveQuantities(ProductCombination $productCombination)
    // {
    //     $sizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $availableQuantities = [];

    //     // Sum sent quantities per size
    //     $sentQuantities = PrintSendData::where('product_combination_id', $productCombination->id)
    //         ->get()
    //         ->pluck('send_quantities')
    //         ->reduce(function ($carry, $quantities) {
    //             foreach ($quantities as $sizeId => $qty) {
    //                 $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
    //             }
    //             return $carry;
    //         }, []);

    //     // Sum received quantities per size
    //     $receivedQuantities = PrintReceiveData::where('product_combination_id', $productCombination->id)
    //         ->get()
    //         ->pluck('receive_quantities')
    //         ->reduce(function ($carry, $quantities) {
    //             foreach ($quantities as $sizeId => $qty) {
    //                 $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
    //             }
    //             return $carry;
    //         }, []);

    //     // Calculate available quantities
    //     foreach ($sizes as $size) {
    //         $sent = $sentQuantities[$size->id] ?? 0;
    //         $received = $receivedQuantities[$size->id] ?? 0;
    //         $availableQuantities[$size->id] = max(0, $sent - $received);
    //     }

    //     return response()->json([
    //         'availableQuantities' => $availableQuantities,
    //         'sizes' => $sizes
    //     ]);
    // }

    // Existing report methods remain unchanged...
    //     // Reports

}
