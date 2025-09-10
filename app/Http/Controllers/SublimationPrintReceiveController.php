<?php



namespace App\Http\Controllers;

use App\Models\CuttingData;
use App\Models\ProductCombination;
use App\Models\Size;
use App\Models\SublimationPrintReceive;
use App\Models\SublimationPrintSend;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SublimationPrintReceiveController extends Controller
{
    public function index(Request $request)
    {
        $query = SublimationPrintReceive::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

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

        $printReceiveData = $query->orderBy('date', 'desc')->paginate(10);
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();;

        return view('backend.library.sublimation_print_receive_data.index', compact('printReceiveData', 'allSizes'));
    }

    public function create()
    {
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Get distinct PO numbers from SublimationPrintSend
        $distinctPoNumbers = SublimationPrintSend::distinct()
            ->pluck('po_number')
            ->filter()
            ->values();

        return view('backend.library.sublimation_print_receive_data.create', compact('distinctPoNumbers', 'allSizes'));
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
            'rows.*.sublimation_print_receive_quantities.*' => 'nullable|integer|min:0',
            'rows.*.sublimation_print_receive_waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->rows as $row) {
                $receiveQuantities = [];
                $wasteQuantities = [];
                $totalReceiveQuantity = 0;
                $totalWasteQuantity = 0;

                //skip if no quantities provided
                if (empty(array_filter($row['sublimation_print_receive_quantities'])) && empty(array_filter($row['sublimation_print_receive_waste_quantities']))) {
                    continue;
                }

                // Process receive quantities
                foreach ($row['sublimation_print_receive_quantities'] as $sizeId => $quantity) {
                    if ($quantity > 0) {
                        $size = Size::find($sizeId);
                        $receiveQuantities[$size->id] = (int)$quantity;
                        $totalReceiveQuantity += (int)$quantity;
                    }
                }

                // Process waste quantities
                foreach ($row['sublimation_print_receive_waste_quantities'] as $sizeId => $quantity) {
                    if ($quantity > 0) {
                        $size = Size::find($sizeId);
                        $wasteQuantities[$size->id] = (int)$quantity;
                        $totalWasteQuantity += (int)$quantity;
                    }
                }

                SublimationPrintReceive::create([
                    'date' => $request->date,
                    'product_combination_id' => $row['product_combination_id'],
                    'po_number' => implode(',', $request->po_number),
                    'old_order' => $request->old_order,
                    'sublimation_print_receive_quantities' => $receiveQuantities,
                    'total_sublimation_print_receive_quantity' => $totalReceiveQuantity,
                    'sublimation_print_receive_waste_quantities' => $wasteQuantities,
                    'total_sublimation_print_receive_waste_quantity' => $totalWasteQuantity,
                ]);
            }

            DB::commit();

            return redirect()->route('sublimation_print_receive_data.index')
                ->withMessage('Sublimation Print/Receive data added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(SublimationPrintReceive $sublimationPrintReceiveDatum)
    {
        $sublimationPrintReceiveDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();;

        return view('backend.library.sublimation_print_receive_data.show', compact('sublimationPrintReceiveDatum', 'allSizes'));
    }

    public function edit(SublimationPrintReceive $sublimationPrintReceiveDatum)
    {
        $sublimationPrintReceiveDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();;

        return view('backend.library.sublimation_print_receive_data.edit', compact('sublimationPrintReceiveDatum', 'allSizes'));
    }

    public function update(Request $request, SublimationPrintReceive $sublimationPrintReceiveDatum)
    {
        $request->validate([
            'date' => 'required|date',
            'sublimation_print_receive_quantities.*' => 'nullable|integer|min:0',
            'sublimation_print_receive_waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            $receiveQuantities = [];
            $wasteQuantities = [];
            $totalReceiveQuantity = 0;
            $totalWasteQuantity = 0;

            // Get all corresponding send data records for this PO and product combination
            $sendData = SublimationPrintSend::where('po_number', $sublimationPrintReceiveDatum->po_number)
                ->where('product_combination_id', $sublimationPrintReceiveDatum->product_combination_id)
                ->get();

            if ($sendData->isEmpty()) {
                throw new \Exception('No send data found for this PO and product combination');
            }

            // Sum all sent quantities
            $sentQuantities = [];
            foreach ($sendData as $send) {
                foreach ($send->sublimation_print_send_quantities as $sizeId => $qty) {
                    $sentQuantities[$sizeId] = ($sentQuantities[$sizeId] ?? 0) + $qty;
                }
            }

            // Calculate already received quantities for this product combination and PO (excluding current record)
            $alreadyReceived = SublimationPrintReceive::where('po_number', $sublimationPrintReceiveDatum->po_number)
                ->where('product_combination_id', $sublimationPrintReceiveDatum->product_combination_id)
                ->where('id', '!=', $sublimationPrintReceiveDatum->id)
                ->get()
                ->reduce(function ($carry, $receive) {
                    foreach ($receive->sublimation_print_receive_quantities as $sizeId => $qty) {
                        $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                    }
                    return $carry;
                }, []);

            // Process receive quantities with validation
            foreach ($request->sublimation_print_receive_quantities as $sizeId => $quantity) {
                $quantity = (int)$quantity;
                if ($quantity > 0) {
                    $sentQty = $sentQuantities[$sizeId] ?? 0;
                    $alreadyReceivedQty = $alreadyReceived[$sizeId] ?? 0;
                    $availableQty = max(0, $sentQty - $alreadyReceivedQty);

                    // The maximum allowed for this size is the available quantity
                    if ($quantity > $availableQty) {
                        $sizeName = Size::find($sizeId)->name;
                        throw new \Exception("Receive quantity for size $sizeName cannot exceed $availableQty");
                    }

                    $receiveQuantities[$sizeId] = $quantity;
                    $totalReceiveQuantity += $quantity;
                }
            }

            // Process waste quantities
            foreach ($request->sublimation_print_receive_waste_quantities as $sizeId => $quantity) {
                $quantity = (int)$quantity;
                if ($quantity > 0) {
                    $wasteQuantities[$sizeId] = $quantity;
                    $totalWasteQuantity += $quantity;
                }
            }

            $sublimationPrintReceiveDatum->update([
                'date' => $request->date,
                'sublimation_print_receive_quantities' => $receiveQuantities,
                'total_sublimation_print_receive_quantity' => $totalReceiveQuantity,
                'sublimation_print_receive_waste_quantities' => $wasteQuantities,
                'total_sublimation_print_receive_waste_quantity' => $totalWasteQuantity,
            ]);

            DB::commit();

            return redirect()->route('sublimation_print_receive_data.index')
                ->withMessage('Sublimation Print/Receive data updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(SublimationPrintReceive $sublimationPrintReceiveDatum)
    {
        $sublimationPrintReceiveDatum->delete();

        return redirect()->route('sublimation_print_receive_data.index')
            ->withMessage('Sublimation Print/Receive data deleted successfully.');
    }

    // public function find(Request $request)
    // {
    //     $poNumbers = $request->input('po_numbers', []);

    //     if (empty($poNumbers)) {
    //         return response()->json([]);
    //     }

    //     // Get print send data for the selected PO numbers
    //     $printSendData = SublimationPrintSend::whereIn('po_number', $poNumbers)
    //         ->with(['productCombination.style', 'productCombination.color', 'productCombination.size'])
    //         ->get()
    //         ->groupBy('po_number');

    //     $result = [];

    //     foreach ($printSendData as $poNumber => $printSendRecords) {
    //         $result[$poNumber] = [];

    //         // Group records by product_combination_id
    //         $groupedByCombination = $printSendRecords->groupBy('product_combination_id');

    //         foreach ($groupedByCombination as $combinationId => $records) {
    //             // Get the product combination from the first record
    //             $productCombination = $records->first()->productCombination;

    //             if (!$productCombination) {
    //                 continue;
    //             }

    //             $availableQuantities = $this->getAvailableReceiveQuantities($productCombination)->getData()->availableQuantities;

    //             $result[$poNumber][] = [
    //                 'combination_id' => $productCombination->id,
    //                 'style' => $productCombination->style->name,
    //                 'color' => $productCombination->color->name,
    //                 'available_quantities' => $availableQuantities,
    //                 'size_ids' => $productCombination->sizes->pluck('id')->toArray()
    //             ];
    //         }
    //     }

    //     return response()->json($result);
    // }

    // public function getAvailableReceiveQuantities(ProductCombination $productCombination)
    // {
    //     $sizes = Size::where('is_active', 1)->get();
    //     $availableQuantities = [];

    //     // Create a mapping of size IDs to size names
    //     $sizeIdToName = [];
    //     foreach ($sizes as $size) {
    //         $sizeIdToName[$size->id] = $size->name;
    //     }

    //     // Sum sent quantities per size using size IDs
    //     $sentQuantities = SublimationPrintSend::where('product_combination_id', $productCombination->id)
    //         ->get()
    //         ->pluck('sublimation_print_send_quantities')
    //         ->reduce(function ($carry, $quantities) use ($sizeIdToName) {
    //             foreach ($quantities as $sizeId => $qty) {
    //                 $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
    //             }
    //             return $carry;
    //         }, []);

    //     // Sum received quantities per size using size IDs
    //     $receivedQuantities = SublimationPrintReceive::where('product_combination_id', $productCombination->id)
    //         ->get()
    //         ->pluck('sublimation_print_receive_quantities')
    //         ->reduce(function ($carry, $quantities) use ($sizeIdToName) {
    //             foreach ($quantities as $sizeId => $qty) {
    //                 $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
    //             }
    //             return $carry;
    //         }, []);

    //     // Calculate available quantities
    //     foreach ($sizes as $size) {
    //         $sent = $sentQuantities[$size->id] ?? 0;
    //         $received = $receivedQuantities[$size->id] ?? 0;
    //         $availableQuantities[$size->name] = max(0, $sent - $received);
    //     }

    //     return response()->json([
    //         'availableQuantities' => $availableQuantities,
    //         'sizes' => $sizes
    //     ]);
    // }

    // Update the getAvailableReceiveQuantities method to accept PO numbers
    public function find(Request $request)
    {
        $poNumbers = $request->input('po_numbers', []);

        if (empty($poNumbers)) {
            return response()->json([]);
        }

        // Get print send data for the selected PO numbers
        $printSendData = SublimationPrintSend::whereIn('po_number', $poNumbers)
            ->with(['productCombination.style', 'productCombination.color', 'productCombination.size'])
            ->get()
            ->groupBy('po_number');

        $result = [];

        foreach ($printSendData as $poNumber => $printSendRecords) {
            $result[$poNumber] = [];

            // Group records by product_combination_id
            $groupedByCombination = $printSendRecords->groupBy('product_combination_id');

            foreach ($groupedByCombination as $combinationId => $records) {
                // Get the product combination from the first record
                $productCombination = $records->first()->productCombination;

                if (!$productCombination) {
                    continue;
                }

                // Get available quantities filtered by PO number
                $availableQuantities = $this->getAvailableReceiveQuantities($productCombination, $poNumber)->getData()->availableQuantities;

                $result[$poNumber][] = [
                    'combination_id' => $productCombination->id,
                    'style' => $productCombination->style->name,
                    'color' => $productCombination->color->name,
                    'available_quantities' => $availableQuantities,
                    'size_ids' => $productCombination->sizes->pluck('id')->toArray()
                ];
            }
        }

        return response()->json($result);
    }

    public function getAvailableReceiveQuantities(ProductCombination $productCombination, $poNumber = null)
    {
        $sizes = Size::where('is_active', 1)->get();
        $availableQuantities = [];

        // Create a mapping of size IDs to size names
        $sizeIdToName = [];
        foreach ($sizes as $size) {
            $sizeIdToName[$size->id] = $size->name;
        }

        // Base query for sent quantities
        $sentQuery = SublimationPrintSend::where('product_combination_id', $productCombination->id);

        // Filter by PO number if provided
        if ($poNumber) {
            $sentQuery->where('po_number', $poNumber);
        }

        // Sum sent quantities per size using size IDs
        $sentQuantities = $sentQuery->get()
            ->pluck('sublimation_print_send_quantities')
            ->reduce(function ($carry, $quantities) use ($sizeIdToName) {
                foreach ($quantities as $sizeId => $qty) {
                    $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                }
                return $carry;
            }, []);

        // Base query for received quantities
        $receivedQuery = SublimationPrintReceive::where('product_combination_id', $productCombination->id);

        // Filter by PO number if provided
        if ($poNumber) {
            $receivedQuery->where('po_number', 'like', '%' . $poNumber . '%');
        }

        // Sum received quantities per size using size IDs
        $receivedQuantities = $receivedQuery->get()
            ->pluck('sublimation_print_receive_quantities')
            ->reduce(function ($carry, $quantities) use ($sizeIdToName) {
                foreach ($quantities as $sizeId => $qty) {
                    $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                }
                return $carry;
            }, []);

        // Calculate available quantities
        foreach ($sizes as $size) {
            $sent = $sentQuantities[$size->id] ?? 0;
            $received = $receivedQuantities[$size->id] ?? 0;
            $availableQuantities[$size->name] = max(0, $sent - $received);
        }

        return response()->json([
            'availableQuantities' => $availableQuantities,
            'sizes' => $sizes
        ]);
    }

    // Reports

    public function totalPrintEmbReceiveReport(Request $request)
    {
        $query = SublimationPrintReceive::with('productCombination.style', 'productCombination.color');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $printReceiveData = $query->get();
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();;
        $reportData = [];

        // Create a mapping of size IDs to size names
        $sizeIdToName = [];
        foreach ($allSizes as $size) {
            $sizeIdToName[$size->id] = $size->name;
        }

        foreach ($printReceiveData as $data) {
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

            // Convert size IDs to names for display
            foreach ($data->sublimation_print_receive_quantities as $sizeId => $qty) {
                $sizeName = $sizeIdToName[$sizeId] ?? null;
                if ($sizeName) {
                    $normalized = strtolower($sizeName);
                    if (array_key_exists($normalized, $reportData[$key]['sizes'])) {
                        $reportData[$key]['sizes'][$normalized] += $qty;
                    }
                }
            }
            $reportData[$key]['total'] += $data->total_sublimation_print_receive_quantity;
        }

        return view('backend.library.sublimation_print_receive_data.reports.total_receive', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes
        ]);
    }

    public function totalPrintEmbBalanceReport(Request $request)
    {
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();;
        $balanceData = [];

        // Create a mapping of size IDs to size names
        $sizeIdToName = [];
        foreach ($allSizes as $size) {
            $sizeIdToName[$size->id] = $size->name;
        }

        // Get all product combinations that have either been sent or received for print/emb
        $productCombinations = ProductCombination::whereHas('printSends')
            ->orWhereHas('printReceives')
            ->with('style', 'color')
            ->get();

        foreach ($productCombinations as $pc) {
            $style = $pc->style->name;
            $color = $pc->color->name;
            $key = $style . '-' . $color;

            // Initialize with default values
            $balanceData[$key] = [
                'style' => $style,
                'color' => $color,
                'sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), ['sent' => 0, 'received' => 0, 'balance' => 0]),
                'total_sent' => 0,
                'total_received' => 0,
                'total_balance' => 0,
            ];

            // Aggregate sent quantities
            $sentData = SublimationPrintSend::where('product_combination_id', $pc->id);
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $sentData->whereBetween('date', [$request->start_date, $request->end_date]);
            }
            $sentData = $sentData->get();

            foreach ($sentData as $data) {
                foreach ($data->sublimation_print_send_quantities as $sizeId => $qty) {
                    $sizeName = $sizeIdToName[$sizeId] ?? null;
                    if ($sizeName) {
                        $normalized = strtolower($sizeName);
                        if (isset($balanceData[$key]['sizes'][$normalized])) {
                            $balanceData[$key]['sizes'][$normalized]['sent'] += $qty;
                        }
                    }
                }
                $balanceData[$key]['total_sent'] += $data->total_sublimation_print_send_quantity;
            }

            // Aggregate received quantities
            $receiveData = SublimationPrintReceive::where('product_combination_id', $pc->id);
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $receiveData->whereBetween('date', [$request->start_date, $request->end_date]);
            }
            $receiveData = $receiveData->get();

            foreach ($receiveData as $data) {
                foreach ($data->sublimation_print_receive_quantities as $sizeId => $qty) {
                    $sizeName = $sizeIdToName[$sizeId] ?? null;
                    if ($sizeName) {
                        $normalized = strtolower($sizeName);
                        if (isset($balanceData[$key]['sizes'][$normalized])) {
                            $balanceData[$key]['sizes'][$normalized]['received'] += $qty;
                        }
                    }
                }
                $balanceData[$key]['total_received'] += $data->total_sublimation_print_receive_quantity;
            }

            // Calculate balance per size and total balance
            foreach ($balanceData[$key]['sizes'] as $sizeName => &$sizeData) {
                $sizeData['balance'] = $sizeData['sent'] - $sizeData['received'];
            }
            unset($sizeData); // Unset the reference to avoid unexpected behavior

            $balanceData[$key]['total_balance'] = $balanceData[$key]['total_sent'] - $balanceData[$key]['total_received'];

            // Remove if total balance is 0 or less (meaning everything sent has been received) unless filtered by date
            if ($balanceData[$key]['total_balance'] <= 0 && (!$request->filled('start_date') && !$request->filled('end_date'))) {
                unset($balanceData[$key]);
            }
        }

        return view('backend.library.sublimation_print_receive_data.reports.balance_quantity', [
            'reportData' => array_values($balanceData),
            'allSizes' => $allSizes
        ]);
    }

    // Helper method to get size name by ID
    private function getSizeNameById($sizeId)
    {
        $size = Size::find($sizeId);
        return $size ? $size->name : null;
    }

    // Helper method to get size ID by name
    private function getSizeIdByName($sizeName)
    {
        $size = Size::where('name', $sizeName)->first();
        return $size ? $size->id : null;
    }
}
