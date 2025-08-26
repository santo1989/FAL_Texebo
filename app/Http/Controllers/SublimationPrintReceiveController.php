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
                ->with('success', 'Sublimation Print/Receive data added successfully.');
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
            $receiveQuantities = [];
            $wasteQuantities = [];
            $totalReceiveQuantity = 0;
            $totalWasteQuantity = 0;

            // Process receive quantities
            foreach ($request->sublimation_print_receive_quantities as $sizeId => $quantity) {
                if ($quantity > 0) {
                    $receiveQuantities[$sizeId] = (int)$quantity;
                    $totalReceiveQuantity += (int)$quantity;
                }
            }

            // Process waste quantities
            foreach ($request->sublimation_print_receive_waste_quantities as $sizeId => $quantity) {
                if ($quantity > 0) {
                    $wasteQuantities[$sizeId] = (int)$quantity;
                    $totalWasteQuantity += (int)$quantity;
                }
            }

            $sublimationPrintReceiveDatum->update([
                'date' => $request->date,
                'sublimation_print_receive_quantities' => $receiveQuantities,
                'total_sublimation_print_receive_quantity' => $totalReceiveQuantity,
                'sublimation_print_receive_waste_quantities' => $wasteQuantities,
                'total_sublimation_print_receive_waste_quantity' => $totalWasteQuantity,
            ]);

            return redirect()->route('sublimation_print_receive_data.index')
                ->with('success', 'Sublimation Print/Receive data updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(SublimationPrintReceive $sublimationPrintReceiveDatum)
    {
        $sublimationPrintReceiveDatum->delete();

        return redirect()->route('sublimation_print_receive_data.index')
            ->with('success', 'Sublimation Print/Receive data deleted successfully.');
    }

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

                $availableQuantities = $this->getAvailableReceiveQuantities($productCombination)->getData()->availableQuantities;

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

    public function getAvailableReceiveQuantities(ProductCombination $productCombination)
    {
        $sizes = Size::where('is_active', 1)->get();
        $availableQuantities = [];

        // Create a mapping of size IDs to size names
        $sizeIdToName = [];
        foreach ($sizes as $size) {
            $sizeIdToName[$size->id] = $size->name;
        }

        // Sum sent quantities per size using size IDs
        $sentQuantities = SublimationPrintSend::where('product_combination_id', $productCombination->id)
            ->get()
            ->pluck('sublimation_print_send_quantities')
            ->reduce(function ($carry, $quantities) use ($sizeIdToName) {
                foreach ($quantities as $sizeId => $qty) {
                    $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                }
                return $carry;
            }, []);

        // Sum received quantities per size using size IDs
        $receivedQuantities = SublimationPrintReceive::where('product_combination_id', $productCombination->id)
            ->get()
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

// namespace App\Http\Controllers;

// use App\Models\CuttingData;
// use App\Models\ProductCombination;
// use App\Models\Size;
// use App\Models\sublimationPrintReceive;
// use App\Models\sublimationPrintSend;
// use Illuminate\Http\Request;

// class SublimationPrintReceiveController extends Controller
// {
//     public function index(Request $request)
//     {
//         $query = sublimationPrintReceive::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

//         if ($request->filled('search')) {
//             $search = $request->input('search');
//             $query->whereHas('productCombination.style', function ($q) use ($search) {
//                 $q->where('name', 'like', '%' . $search . '%');
//             })->orWhereHas('productCombination.color', function ($q) use ($search) {
//                 $q->where('name', 'like', '%' . $search . '%');
//             });
//         }
//         if ($request->filled('date')) {
//             $query->whereDate('date', $request->input('date'));
//         }

//         $printReceiveData = $query->orderBy('date', 'desc')->paginate(10);
//         $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();;

//         return view('backend.library.sublimation_print_receive_data.index', compact('printReceiveData', 'allSizes'));
//     }

//     public function create()
//     {
//         $productCombinations = ProductCombination::whereHas('printSends')
//             ->with('buyer', 'style', 'color')
//             ->get();

//         $sizes = Size::where('is_active', 1)->get();

//         return view('backend.library.sublimation_print_receive_data.create', compact('productCombinations', 'sizes'));
//     }

//     public function store(Request $request)
//     {
//         $request->validate([
//             'date' => 'required|date',
//             'product_combination_id' => 'required|exists:product_combinations,id',
//             'quantities.*' => 'nullable|integer|min:0',
//         ]);

//         $productCombination = ProductCombination::findOrFail($request->product_combination_id);
//         $availableQuantities = $this->getAvailableReceiveQuantities($productCombination)->getData()->availableQuantities;

//         $receiveQuantities = [];
//         $totalReceiveQuantity = 0;
//         $errors = [];

//         foreach ($request->input('quantities', []) as $sizeId => $quantity) {
//             $size = Size::find($sizeId);
//             if ($size && $quantity > 0) {
//                 $sizeName = strtolower($size->name);
//                 $available = $availableQuantities[$sizeName] ?? 0;
//                 if ($quantity > $available) {
//                     $errors["quantities.$sizeId"] = "Quantity for {$size->name} exceeds available to receive ($available)";
//                 } else {
//                     $receiveQuantities[$size->name] = (int)$quantity;
//                     $totalReceiveQuantity += (int)$quantity;
//                 }
//             }
//         }

//         if (!empty($errors)) {
//             return redirect()->back()->withErrors($errors)->withInput();
//         }

//         sublimationPrintReceive::create([
//             'date' => $request->date,
//             'product_combination_id' => $request->product_combination_id,
//             'sublimation_print_receive_quantities' => $receiveQuantities,
//             'total_receive_quantity' => $totalReceiveQuantity,
//         ]);

//         return redirect()->route('sublimation_print_receive_data.index')->with('success', 'Print/Receive data added successfully.');
//     }

//     public function show(sublimationPrintReceive $printReceiveDatum)
//     {
//         return view('backend.library.sublimation_print_receive_data.show', compact('printReceiveDatum'));
//     }

//     public function edit(sublimationPrintReceive $printReceiveDatum)
//     {
//         $printReceiveDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
//         $sizes = Size::where('is_active', 1)->get();
//         $availableQuantities = $this->getAvailableReceiveQuantities($printReceiveDatum->productCombination)->getData()->availableQuantities;

//         $sizeData = $sizes->map(function ($size) use ($printReceiveDatum, $availableQuantities) {
//             $sizeName = strtolower($size->name);
//             return [
//                 'id' => $size->id,
//                 'name' => $size->name,
//                 'available' => $availableQuantities[$sizeName] ?? 0,
//                 'current_quantity' => $printReceiveDatum->sublimation_print_receive_quantities[$size->name] ?? 0
//             ];
//         });

//         return view('backend.library.sublimation_print_receive_data.edit', [
//             'printReceiveDatum' => $printReceiveDatum,
//             'sizes' => $sizeData
//         ]);
//     }

//     public function update(Request $request, sublimationPrintReceive $printReceiveDatum)
//     {
//         $request->validate([
//             'date' => 'required|date',
//             'quantities.*' => 'nullable|integer|min:0',
//         ]);

//         $productCombination = $printReceiveDatum->productCombination;
//         $availableQuantities = $this->getAvailableReceiveQuantities($productCombination)->getData()->availableQuantities;

//         $receiveQuantities = [];
//         $totalReceiveQuantity = 0;
//         $errors = [];

//         foreach ($request->input('quantities', []) as $sizeId => $quantity) {
//             $size = Size::find($sizeId);
//             if ($size && $quantity > 0) {
//                 $sizeName = strtolower($size->name);
//                 $maxAllowed = ($availableQuantities[$sizeName] ?? 0) + ($printReceiveDatum->sublimation_print_receive_quantities[$size->name] ?? 0);
//                 if ($quantity > $maxAllowed) {
//                     $errors["quantities.$sizeId"] = "Quantity for {$size->name} exceeds available to receive ($maxAllowed)";
//                 } else {
//                     $receiveQuantities[$size->name] = (int)$quantity;
//                     $totalReceiveQuantity += (int)$quantity;
//                 }
//             }
//         }

//         if (!empty($errors)) {
//             return redirect()->back()->withErrors($errors)->withInput();
//         }

//         $printReceiveDatum->update([
//             'date' => $request->date,
//             'sublimation_print_receive_quantities' => $receiveQuantities,
//             'total_receive_quantity' => $totalReceiveQuantity,
//         ]);

//         return redirect()->route('sublimation_print_receive_data.index')->with('success', 'Print/Receive data updated successfully.');
//     }

//     public function destroy(sublimationPrintReceive $printReceiveDatum)
//     {
//         $printReceiveDatum->delete();
//         return redirect()->route('sublimation_print_receive_data.index')->with('success', 'Print/Receive data deleted successfully.');
//     }

//     public function getAvailableReceiveQuantities(ProductCombination $productCombination)
//     {
//         $sizes = Size::where('is_active', 1)->get();
//         $availableQuantities = [];

//         // Sum sent quantities per size
//         $sentQuantities = sublimationPrintSend::where('product_combination_id', $productCombination->id)
//             ->get()
//             ->pluck('sublimation_print_send_quantities')
//             ->reduce(function ($carry, $quantities) {
//                 foreach ($quantities as $size => $qty) {
//                     $normalized = strtolower($size);
//                     $carry[$normalized] = ($carry[$normalized] ?? 0) + $qty;
//                 }
//                 return $carry;
//             }, []);

//         // Sum received quantities per size
//         $receivedQuantities = sublimationPrintReceive::where('product_combination_id', $productCombination->id)
//             ->get()
//             ->pluck('sublimation_print_receive_quantities')
//             ->reduce(function ($carry, $quantities) {
//                 foreach ($quantities as $size => $qty) {
//                     $normalized = strtolower($size);
//                     $carry[$normalized] = ($carry[$normalized] ?? 0) + $qty;
//                 }
//                 return $carry;
//             }, []);

//         foreach ($sizes as $size) {
//             $sizeName = strtolower($size->name);
//             $sent = $sentQuantities[$sizeName] ?? 0;
//             $received = $receivedQuantities[$sizeName] ?? 0;
//             $availableQuantities[$sizeName] = max(0, $sent - $received);
//         }

//         return response()->json([
//             'availableQuantities' => $availableQuantities,
//             'sizes' => $sizes
//         ]);
//     }

//     // Existing report methods remain unchanged...
//     //     // Reports

//     public function totalPrintEmbReceiveReport(Request $request)
//     {
//         $query = sublimationPrintReceive::with('productCombination.style', 'productCombination.color');

//         if ($request->filled('start_date') && $request->filled('end_date')) {
//             $query->whereBetween('date', [$request->start_date, $request->end_date]);
//         }

//         $printReceiveData = $query->get();
//         $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();;
//         $reportData = [];

//         foreach ($printReceiveData as $data) {
//             $style = $data->productCombination->style->name;
//             $color = $data->productCombination->color->name;
//             $key = $style . '-' . $color;

//             if (!isset($reportData[$key])) {
//                 $reportData[$key] = [
//                     'style' => $style,
//                     'color' => $color,
//                     'sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
//                     'total' => 0
//                 ];
//             }

//             foreach ($data->sublimation_print_receive_quantities as $size => $qty) {
//                 $normalized = strtolower($size);
//                 if (array_key_exists($normalized, $reportData[$key]['sizes'])) {
//                     $reportData[$key]['sizes'][$normalized] += $qty;
//                 }
//             }
//             $reportData[$key]['total'] += $data->total_receive_quantity;
//         }

//         return view('backend.library.sublimation_print_receive_data.reports.total_receive', [
//             'reportData' => array_values($reportData),
//             'allSizes' => $allSizes
//         ]);
//     }

//     public function totalPrintEmbBalanceReport(Request $request)
//     {
//         $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();;
//         $balanceData = [];

//         // Get all product combinations that have either been sent or received for print/emb
//         $productCombinations = ProductCombination::whereHas('printSends')
//             ->orWhereHas('printReceives')
//             ->with('style', 'color')
//             ->get();

//         foreach ($productCombinations as $pc) {
//             $style = $pc->style->name;
//             $color = $pc->color->name;
//             $key = $style . '-' . $color;

//             // Initialize with default values
//             $balanceData[$key] = [
//                 'style' => $style,
//                 'color' => $color,
//                 'sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), ['sent' => 0, 'received' => 0, 'balance' => 0]),
//                 'total_sent' => 0,
//                 'total_received' => 0,
//                 'total_balance' => 0,
//             ];

//             // Aggregate sent quantities
//             $sentData = sublimationPrintSend::where('product_combination_id', $pc->id);
//             if ($request->filled('start_date') && $request->filled('end_date')) {
//                 $sentData->whereBetween('date', [$request->start_date, $request->end_date]);
//             }
//             $sentData = $sentData->get();

//             foreach ($sentData as $data) {
//                 foreach ($data->sublimation_print_send_quantities as $size => $qty) {
//                     $normalized = strtolower($size);
//                     if (isset($balanceData[$key]['sizes'][$normalized])) {
//                         $balanceData[$key]['sizes'][$normalized]['sent'] += $qty;
//                     }
//                 }
//                 $balanceData[$key]['total_sent'] += $data->total_send_quantity;
//             }

//             // Aggregate received quantities
//             $receiveData = sublimationPrintReceive::where('product_combination_id', $pc->id);
//             if ($request->filled('start_date') && $request->filled('end_date')) {
//                 $receiveData->whereBetween('date', [$request->start_date, $request->end_date]);
//             }
//             $receiveData = $receiveData->get();

//             foreach ($receiveData as $data) {
//                 foreach ($data->sublimation_print_receive_quantities as $size => $qty) {
//                     $normalized = strtolower($size);
//                     if (isset($balanceData[$key]['sizes'][$normalized])) {
//                         $balanceData[$key]['sizes'][$normalized]['received'] += $qty;
//                     }
//                 }
//                 $balanceData[$key]['total_received'] += $data->total_receive_quantity;
//             }

//             // Calculate balance per size and total balance
//             foreach ($balanceData[$key]['sizes'] as $sizeName => &$sizeData) {
//                 $sizeData['balance'] = $sizeData['sent'] - $sizeData['received'];
//             }
//             unset($sizeData); // Unset the reference to avoid unexpected behavior

//             $balanceData[$key]['total_balance'] = $balanceData[$key]['total_sent'] - $balanceData[$key]['total_received'];

//             // Remove if total balance is 0 or less (meaning everything sent has been received) unless filtered by date
//             if ($balanceData[$key]['total_balance'] <= 0 && (!$request->filled('start_date') && !$request->filled('end_date'))) {
//                 unset($balanceData[$key]);
//             }
//         }

//         return view('backend.library.sublimation_print_receive_data.reports.balance_quantity', [
//             'reportData' => array_values($balanceData),
//             'allSizes' => $allSizes
//         ]);
//     }

//     public function wipReport(Request $request)
//     {
//         // Get product combinations with print_embroidery = true
//         $combinations = ProductCombination::where('print_embroidery', true)
//             ->with('style', 'color')
//             ->get();

//         $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();;
//         $wipData = [];

//         foreach ($combinations as $pc) {
//             $totalSent = sublimationPrintSend::where('product_combination_id', $pc->id)
//                 ->sum('total_send_quantity');

//             // Get total received quantity for this product combination
//             $totalReceived = sublimationPrintReceive::where('product_combination_id', $pc->id)
//                 ->sum('total_receive_quantity');

//             // WIP is total sent - total received. It's the items still at print/emb.
//             // Only show if there's a positive balance (more sent than received)
//             if (($totalSent - $totalReceived) > 0) {
//                 $key = $pc->style->name . '-' . $pc->color->name;

//                 if (!isset($wipData[$key])) {
//                     $wipData[$key] = [
//                         'style' => $pc->style->name,
//                         'color' => $pc->color->name,
//                         'sizes' => [],
//                         'total_sent' => 0,
//                         'total_received' => 0,
//                         'waiting' => 0
//                     ];

//                     // Initialize sizes
//                     foreach ($allSizes as $size) {
//                         $wipData[$key]['sizes'][strtolower($size->name)] = [
//                             'sent' => 0,
//                             'received' => 0,
//                             'waiting' => 0
//                         ];
//                     }
//                 }

//                 $wipData[$key]['total_sent'] += $totalSent;
//                 $wipData[$key]['total_received'] += $totalReceived;
//                 $wipData[$key]['waiting'] += ($totalSent - $totalReceived);

//                 // Aggregate size quantities for sent
//                 $sendData = sublimationPrintSend::where('product_combination_id', $pc->id)->get();
//                 foreach ($sendData as $sd) {
//                     foreach ($sd->sublimation_print_send_quantities as $size => $qty) {
//                         $normalizedSize = strtolower($size);
//                         if (isset($wipData[$key]['sizes'][$normalizedSize])) {
//                             $wipData[$key]['sizes'][$normalizedSize]['sent'] += $qty;
//                         }
//                     }
//                 }

//                 // Aggregate size quantities for received
//                 $receiveData = sublimationPrintReceive::where('product_combination_id', $pc->id)->get();
//                 foreach ($receiveData as $rd) {
//                     foreach ($rd->sublimation_print_receive_quantities as $size => $qty) {
//                         $normalizedSize = strtolower($size);
//                         if (isset($wipData[$key]['sizes'][$normalizedSize])) {
//                             $wipData[$key]['sizes'][$normalizedSize]['received'] += $qty;
//                         }
//                     }
//                 }

//                 // Calculate waiting per size
//                 foreach ($wipData[$key]['sizes'] as $sizeName => &$data) {
//                     $data['waiting'] = $data['sent'] - $data['received'];
//                 }
//                 unset($data); // Unset the reference
//             }
//         }

//         return view('backend.library.print_send_data.reports.wip', [
//             'wipData' => array_values($wipData),
//             'allSizes' => $allSizes
//         ]);
//     }

//     public function readyToInputReport(Request $request)
//     {
//         $readyData = [];

//         // Product combinations with print_embroidery = false
//         $nonEmbCombinations = ProductCombination::where('print_embroidery', false)
//             ->with('style', 'color')
//             ->get();

//         foreach ($nonEmbCombinations as $pc) {
//             $totalCut = CuttingData::where('product_combination_id', $pc->id)->sum('total_cut_quantity');
//             $readyData[] = [
//                 'style' => $pc->style->name,
//                 'color' => $pc->color->name,
//                 'type' => 'No Print/Emb Needed',
//                 'total_cut' => $totalCut,
//                 'total_sent' => 0,
//                 'total_received' => 0
//             ];
//         }

//         // Product combinations with print_embroidery = true and completed (total sent == total received)
//         $embCombinations = ProductCombination::where('print_embroidery', true)
//             ->with('style', 'color')
//             ->get();

//         foreach ($embCombinations as $pc) {
//             $totalCut = CuttingData::where('product_combination_id', $pc->id)
//                 ->sum('total_cut_quantity');

//             $totalSent = sublimationPrintSend::where('product_combination_id', $pc->id)
//                 ->sum('total_send_quantity');

//             // Get total received quantity
//             $totalReceived = sublimationPrintReceive::where('product_combination_id', $pc->id)
//                 ->sum('total_receive_quantity');

//             // "Ready to input" means either no print/emb needed OR all sent items have been received.
//             if ($totalSent > 0 && $totalSent == $totalReceived) {
//                 $readyData[] = [
//                     'style' => $pc->style->name,
//                     'color' => $pc->color->name,
//                     'type' => 'Print/Emb Completed',
//                     'total_cut' => $totalCut,
//                     'total_sent' => $totalSent,
//                     'total_received' => $totalReceived
//                 ];
//             }
//         }

//         return view('backend.library.print_send_data.reports.ready', compact('readyData'));
//     }
   
// }
