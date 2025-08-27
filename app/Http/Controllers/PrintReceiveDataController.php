<?php

// namespace App\Http\Controllers;

// use App\Models\CuttingData;
// use App\Models\PrintReceiveData;
// use Illuminate\Http\Request;
// use App\Models\PrintSendData;
// use App\Models\ProductCombination;
// use App\Models\Size;
// use Illuminate\Support\Facades\DB; // Make sure this is imported

// class PrintReceiveDataController extends Controller
// {
//     public function index(Request $request)
//     {
//         $query = PrintReceiveData::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

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
//         $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();

//         return view('backend.library.print_receive_data.index', compact('printReceiveData', 'allSizes'));
//     }

//     public function create()
//     {
//         // Only show product combinations that have been sent for print/embroidery
//         $productCombinations = ProductCombination::whereHas('printSends')
//             ->with('buyer', 'style', 'color')
//             ->get();

//         $sizes = Size::where('is_active', 1)->get();

//         return view('backend.library.print_receive_data.create', compact('productCombinations', 'sizes'));
//     }

//     public function store(Request $request)
//     {
//         $request->validate([
//             'date' => 'required|date',
//             'product_combination_id' => 'required|exists:product_combinations,id',
//             'quantities.*' => 'nullable|integer|min:0',
//         ]);

//         $productCombination = ProductCombination::findOrFail($request->product_combination_id);

//         // Calculate total sent quantity for this product combination
//         $totalSent = PrintSendData::where('product_combination_id', $productCombination->id)
//             ->sum('total_send_quantity');

//         // Calculate total received quantity for this product combination (excluding current input)
//         $totalReceivedSoFar = PrintReceiveData::where('product_combination_id', $productCombination->id)
//             ->sum('total_receive_quantity');

//         $receiveQuantities = [];
//         $totalReceiveQuantity = 0;
//         $errors = [];

//         // Get sent quantities per size for this product combination
//         $sentQuantitiesPerSize = PrintSendData::where('product_combination_id', $productCombination->id)
//             ->get()
//             ->flatMap(fn($item) => $item->send_quantities)
//             ->groupBy(fn($value, $key) => strtolower($key))
//             ->map(fn($group) => $group->sum())
//             ->toArray();

//         // Get received quantities so far per size for this product combination
//         $receivedQuantitiesSoFarPerSize = PrintReceiveData::where('product_combination_id', $productCombination->id)
//             ->get()
//             ->flatMap(fn($item) => $item->receive_quantities)
//             ->groupBy(fn($value, $key) => strtolower($key))
//             ->map(fn($group) => $group->sum())
//             ->toArray();

//         foreach ($request->input('quantities', []) as $sizeId => $quantity) {
//             $size = Size::find($sizeId);
//             if ($size && $quantity > 0) {
//                 $sizeName = strtolower($size->name);

//                 $sentForSize = $sentQuantitiesPerSize[$sizeName] ?? 0;
//                 $receivedForSizeSoFar = $receivedQuantitiesSoFarPerSize[$sizeName] ?? 0;
//                 $availableToReceive = $sentForSize - $receivedForSizeSoFar;

//                 if ($quantity > $availableToReceive) {
//                     $errors["quantities.$sizeId"] = "Quantity for " . $size->name . " exceeds available to receive ($availableToReceive). Sent: $sentForSize, Received: $receivedForSizeSoFar";
//                     continue;
//                 }

//                 $receiveQuantities[$size->name] = (int)$quantity;
//                 $totalReceiveQuantity += (int)$quantity;
//             }
//         }

//         if (!empty($errors)) {
//             return redirect()->back()->withErrors($errors)->withInput();
//         }

//         // Validate total received quantity against total sent quantity
//         $maxAllowedTotalReceive = $totalSent - $totalReceivedSoFar;
//         if ($totalReceiveQuantity > $maxAllowedTotalReceive) {
//             return redirect()->back()
//                 ->withErrors(['total' => "Total receive quantity ($totalReceiveQuantity) exceeds total sent quantity ($maxAllowedTotalReceive available)."])
//                 ->withInput();
//         }

//         PrintReceiveData::create([
//             'date' => $request->date,
//             'product_combination_id' => $request->product_combination_id,
//             'receive_quantities' => $receiveQuantities,
//             'total_receive_quantity' => $totalReceiveQuantity,
//         ]);

//         return redirect()->route('print_receive_data.index')->with('success', 'Print/Receive data added successfully.');
//     }

//     public function show(PrintReceiveData $printReceiveDatum)
//     {
//         return view('backend.library.print_receive_data.show', compact('printReceiveDatum'));
//     }

//     public function edit(PrintReceiveData $printReceiveDatum)
//     {
//         $printReceiveDatum->load(
//             'productCombination.buyer',
//             'productCombination.style',
//             'productCombination.color'
//         );

//         $productCombination = $printReceiveDatum->productCombination;

//         // Calculate total sent quantity for this product combination
//         $totalSent = PrintSendData::where('product_combination_id', $productCombination->id)
//             ->sum('total_send_quantity');

//         // Calculate total received quantity for this product combination (excluding current record)
//         $totalReceivedSoFar = PrintReceiveData::where('product_combination_id', $productCombination->id)
//             ->where('id', '!=', $printReceiveDatum->id)
//             ->sum('total_receive_quantity');

//         $availableToReceiveTotal = $totalSent - $totalReceivedSoFar;

//         // Get sent quantities per size for this product combination
//         $sentQuantitiesPerSize = PrintSendData::where('product_combination_id', $productCombination->id)
//             ->get()
//             ->flatMap(fn($item) => $item->send_quantities)
//             ->groupBy(fn($value, $key) => strtolower($key))
//             ->map(fn($group) => $group->sum())
//             ->toArray();

//         // Get received quantities so far per size for this product combination (excluding current record)
//         $receivedQuantitiesSoFarPerSize = PrintReceiveData::where('product_combination_id', $productCombination->id)
//             ->where('id', '!=', $printReceiveDatum->id)
//             ->get()
//             ->flatMap(fn($item) => $item->receive_quantities)
//             ->groupBy(fn($value, $key) => strtolower($key))
//             ->map(fn($group) => $group->sum())
//             ->toArray();

//         $sizes = Size::all()->map(function ($size) use ($printReceiveDatum, $sentQuantitiesPerSize, $receivedQuantitiesSoFarPerSize) {
//             $sizeName = strtolower($size->name);
//             $sent = $sentQuantitiesPerSize[$sizeName] ?? 0;
//             $received = $receivedQuantitiesSoFarPerSize[$sizeName] ?? 0;

//             return [
//                 'id' => $size->id,
//                 'name' => $size->name,
//                 'sent' => $sent,
//                 'received_so_far' => $received,
//                 'available_to_receive' => $sent - $received,
//                 'current_quantity' => $printReceiveDatum->receive_quantities[$size->name] ?? 0
//             ];
//         });

//         return view('backend.library.print_receive_data.edit', [
//             'printReceiveDatum' => $printReceiveDatum,
//             'sizes' => $sizes,
//             'totalSent' => $totalSent,
//             'totalReceivedSoFar' => $totalReceivedSoFar,
//             'availableToReceiveTotal' => $availableToReceiveTotal
//         ]);
//     }

//     public function update(Request $request, PrintReceiveData $printReceiveDatum)
//     {
//         $request->validate([
//             'date' => 'required|date',
//             'quantities.*' => 'nullable|integer|min:0',
//         ]);

//         $productCombination = $printReceiveDatum->productCombination;

//         // Calculate total sent quantity for this product combination
//         $totalSent = PrintSendData::where('product_combination_id', $productCombination->id)
//             ->sum('total_send_quantity');

//         // Calculate total received quantity for this product combination (excluding current record)
//         $totalReceivedSoFar = PrintReceiveData::where('product_combination_id', $productCombination->id)
//             ->where('id', '!=', $printReceiveDatum->id)
//             ->sum('total_receive_quantity');

//         $receiveQuantities = [];
//         $totalReceiveQuantity = 0;
//         $errors = [];

//         // Get sent quantities per size for this product combination
//         $sentQuantitiesPerSize = PrintSendData::where('product_combination_id', $productCombination->id)
//             ->get()
//             ->flatMap(fn($item) => $item->send_quantities)
//             ->groupBy(fn($value, $key) => strtolower($key))
//             ->map(fn($group) => $group->sum())
//             ->toArray();

//         // Get received quantities so far per size for this product combination (excluding current record)
//         $receivedQuantitiesSoFarPerSize = PrintReceiveData::where('product_combination_id', $productCombination->id)
//             ->where('id', '!=', $printReceiveDatum->id)
//             ->get()
//             ->flatMap(fn($item) => $item->receive_quantities)
//             ->groupBy(fn($value, $key) => strtolower($key))
//             ->map(fn($group) => $group->sum())
//             ->toArray();

//         foreach ($request->input('quantities', []) as $sizeId => $quantity) {
//             $size = Size::find($sizeId);
//             if ($size && $quantity > 0) {
//                 $sizeName = strtolower($size->name);

//                 $sentForSize = $sentQuantitiesPerSize[$sizeName] ?? 0;
//                 $receivedForSizeSoFar = $receivedQuantitiesSoFarPerSize[$sizeName] ?? 0;
//                 $availableToReceive = $sentForSize - $receivedForSizeSoFar;

//                 if ($quantity > $availableToReceive) {
//                     $errors["quantities.$sizeId"] = "Quantity for " . $size->name . " exceeds available to receive ($availableToReceive). Sent: $sentForSize, Received: $receivedForSizeSoFar";
//                     continue;
//                 }

//                 $receiveQuantities[$size->name] = (int)$quantity;
//                 $totalReceiveQuantity += (int)$quantity;
//             }
//         }

//         if (!empty($errors)) {
//             return redirect()->back()->withErrors($errors)->withInput();
//         }

//         // Validate total received quantity against total sent quantity
//         $maxAllowedTotalReceive = $totalSent - $totalReceivedSoFar;
//         if ($totalReceiveQuantity > $maxAllowedTotalReceive) {
//             return redirect()->back()
//                 ->withErrors(['total' => "Total receive quantity ($totalReceiveQuantity) exceeds total sent quantity ($maxAllowedTotalReceive available)."])
//                 ->withInput();
//         }

//         $printReceiveDatum->update([
//             'date' => $request->date,
//             'receive_quantities' => $receiveQuantities,
//             'total_receive_quantity' => $totalReceiveQuantity,
//         ]);

//         return redirect()->route('print_receive_data.index')
//             ->with('success', 'Print/Receive data updated successfully.');
//     }

//     public function destroy(PrintReceiveData $printReceiveDatum)
//     {
//         $printReceiveDatum->delete();

//         return redirect()->route('print_receive_data.index')
//             ->with('success', 'Print/Receive data deleted successfully.');
//     }

//     // Reports

//     public function totalPrintEmbReceiveReport(Request $request)
//     {
//         $query = PrintReceiveData::with('productCombination.style', 'productCombination.color');

//         if ($request->filled('start_date') && $request->filled('end_date')) {
//             $query->whereBetween('date', [$request->start_date, $request->end_date]);
//         }

//         $printReceiveData = $query->get();
//         $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
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

//             foreach ($data->receive_quantities as $size => $qty) {
//                 $normalized = strtolower($size);
//                 if (array_key_exists($normalized, $reportData[$key]['sizes'])) {
//                     $reportData[$key]['sizes'][$normalized] += $qty;
//                 }
//             }
//             $reportData[$key]['total'] += $data->total_receive_quantity;
//         }

//         return view('backend.library.print_receive_data.reports.total_receive', [
//             'reportData' => array_values($reportData),
//             'allSizes' => $allSizes
//         ]);
//     }

//     public function totalPrintEmbBalanceReport(Request $request)
//     {
//         $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
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
//             $sentData = PrintSendData::where('product_combination_id', $pc->id);
//             if ($request->filled('start_date') && $request->filled('end_date')) {
//                 $sentData->whereBetween('date', [$request->start_date, $request->end_date]);
//             }
//             $sentData = $sentData->get();

//             foreach ($sentData as $data) {
//                 foreach ($data->send_quantities as $size => $qty) {
//                     $normalized = strtolower($size);
//                     if (isset($balanceData[$key]['sizes'][$normalized])) {
//                         $balanceData[$key]['sizes'][$normalized]['sent'] += $qty;
//                     }
//                 }
//                 $balanceData[$key]['total_sent'] += $data->total_send_quantity;
//             }

//             // Aggregate received quantities
//             $receiveData = PrintReceiveData::where('product_combination_id', $pc->id);
//             if ($request->filled('start_date') && $request->filled('end_date')) {
//                 $receiveData->whereBetween('date', [$request->start_date, $request->end_date]);
//             }
//             $receiveData = $receiveData->get();

//             foreach ($receiveData as $data) {
//                 foreach ($data->receive_quantities as $size => $qty) {
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

//         return view('backend.library.print_receive_data.reports.balance_quantity', [
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

//         $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
//         $wipData = [];

//         foreach ($combinations as $pc) {
//             $totalSent = PrintSendData::where('product_combination_id', $pc->id)
//                 ->sum('total_send_quantity');

//             // Get total received quantity for this product combination
//             $totalReceived = PrintReceiveData::where('product_combination_id', $pc->id)
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
//                 $sendData = PrintSendData::where('product_combination_id', $pc->id)->get();
//                 foreach ($sendData as $sd) {
//                     foreach ($sd->send_quantities as $size => $qty) {
//                         $normalizedSize = strtolower($size);
//                         if (isset($wipData[$key]['sizes'][$normalizedSize])) {
//                             $wipData[$key]['sizes'][$normalizedSize]['sent'] += $qty;
//                         }
//                     }
//                 }

//                 // Aggregate size quantities for received
//                 $receiveData = PrintReceiveData::where('product_combination_id', $pc->id)->get();
//                 foreach ($receiveData as $rd) {
//                     foreach ($rd->receive_quantities as $size => $qty) {
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

//             $totalSent = PrintSendData::where('product_combination_id', $pc->id)
//                 ->sum('total_send_quantity');

//             // Get total received quantity
//             $totalReceived = PrintReceiveData::where('product_combination_id', $pc->id)
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



namespace App\Http\Controllers;

use App\Models\CuttingData;
use App\Models\PrintReceiveData;
use Illuminate\Http\Request;
use App\Models\PrintSendData;
use App\Models\ProductCombination;
use App\Models\Size;
use Illuminate\Support\Facades\DB;

class PrintReceiveDataController extends Controller
{
    public function index(Request $request)
    {
        $query = PrintReceiveData::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

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
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        return view('backend.library.print_receive_data.index', compact('printReceiveData', 'allSizes'));
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
            'rows.*.receive_quantities.*' => 'nullable|integer|min:0',
            'rows.*.receive_waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->rows as $row) {
                $receiveQuantities = [];
                $wasteQuantities = [];
                $totalReceiveQuantity = 0;
                $totalWasteQuantity = 0;

                // Process receive quantities
                foreach ($row['receive_quantities'] as $sizeId => $quantity) {
                    if ($quantity !== null && (int)$quantity > 0) {
                        $size = Size::find($sizeId);
                        if ($size) {
                            $receiveQuantities[$size->id] = (int)$quantity;
                            $totalReceiveQuantity += (int)$quantity;
                        }
                    }
                }

                // Process waste quantities
                foreach ($row['receive_waste_quantities'] as $sizeId => $quantity) {
                    if ($quantity !== null && (int)$quantity > 0) {
                        $size = Size::find($sizeId);
                        if ($size) {
                            $wasteQuantities[$size->id] = (int)$quantity;
                            $totalWasteQuantity += (int)$quantity;
                        }
                    }
                }

                // Only create a record if there's at least one valid receive or waste quantity
                if (!empty($receiveQuantities) || !empty($wasteQuantities)) {
                    PrintReceiveData::create([
                        'date' => $request->date,
                        'product_combination_id' => $row['product_combination_id'],
                        'po_number' => implode(',', $request->po_number),
                        'receive_quantities' => $receiveQuantities,
                        'total_receive_quantity' => $totalReceiveQuantity,
                        'receive_waste_quantities' => $wasteQuantities,
                        'total_receive_waste_quantity' => $totalWasteQuantity,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('print_receive_data.index')
                ->with('success', 'Print/Embroidery Receive data added successfully.');
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

    public function edit(PrintReceiveData $printReceiveDatum)
    {
        $printReceiveDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        //only valid sizes
        $validSizes = $allSizes->filter(function ($size) use ($printReceiveDatum) {
            return isset($printReceiveDatum->receive_quantities[$size->id]) ||
                isset($printReceiveDatum->receive_waste_quantities[$size->id]);
        });

        $allSizes = $validSizes->values();

        return view('backend.library.print_receive_data.edit', compact('printReceiveDatum', 'allSizes'));
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
                ->with('success', 'Print/Embroidery Receive data updated successfully.');
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
            ->with('success', 'Print/Embroidery Receive data deleted successfully.');
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
            // Get print send data for the selected PO number
            $printSendData = PrintSendData::where('po_number', 'like', '%' . $poNumber . '%')
                ->with(['productCombination.style', 'productCombination.color'])
                ->get();

            foreach ($printSendData as $data) {
                if (!$data->productCombination) {
                    continue;
                }

                // Create a unique key for this combination
                $combinationKey = $data->productCombination->id . '-' .
                    $data->productCombination->style->name . '-' .
                    $data->productCombination->color->name;

                // Skip if we've already processed this combination
                if (in_array($combinationKey, $processedCombinations)) {
                    continue;
                }

                // Mark this combination as processed
                $processedCombinations[] = $combinationKey;

                $availableQuantities = $this->getAvailableReceiveQuantities($data->productCombination)->getData()->availableQuantities;

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

    public function getAvailableReceiveQuantities(ProductCombination $productCombination)
    {
        $sizes = Size::where('is_active', 1)->get();
        $availableQuantities = [];

        // Sum sent quantities per size
        $sentQuantities = PrintSendData::where('product_combination_id', $productCombination->id)
            ->get()
            ->pluck('send_quantities')
            ->reduce(function ($carry, $quantities) {
                foreach ($quantities as $sizeId => $qty) {
                    $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                }
                return $carry;
            }, []);

        // Sum received quantities per size
        $receivedQuantities = PrintReceiveData::where('product_combination_id', $productCombination->id)
            ->get()
            ->pluck('receive_quantities')
            ->reduce(function ($carry, $quantities) {
                foreach ($quantities as $sizeId => $qty) {
                    $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                }
                return $carry;
            }, []);

        // Calculate available quantities
        foreach ($sizes as $size) {
            $sent = $sentQuantities[$size->id] ?? 0;
            $received = $receivedQuantities[$size->id] ?? 0;
            $availableQuantities[$size->id] = max(0, $sent - $received);
        }

        return response()->json([
            'availableQuantities' => $availableQuantities,
            'sizes' => $sizes
        ]);
    }

    // Existing report methods remain unchanged...
    //     // Reports

    public function totalPrintEmbReceiveReport(Request $request)
    {
        $query = PrintReceiveData::with('productCombination.style', 'productCombination.color');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $printReceiveData = $query->get();
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();

        // Create a map for size ID to size name and vice versa
        $sizeIdToName = $allSizes->pluck('name', 'id')->toArray();
        $sizeNameToId = $allSizes->pluck('id', 'name')->map(fn($id) => strtolower($allSizes->firstWhere('id', $id)->name))->toArray(); // Ensure keys are lowercase size names

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

        return view('backend.library.print_receive_data.reports.total_receive', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes,
            'sizeIdToName' => $sizeIdToName, // Pass the map to the view
        ]);
    }

    public function totalPrintEmbBalanceReport(Request $request)
    {
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $sizeIdToName = $allSizes->pluck('name', 'id')->toArray();
        $sizeNameToId = $allSizes->pluck('id', 'name')->map(fn($id) => strtolower($allSizes->firstWhere('id', $id)->name))->toArray();

        $balanceData = [];

        $productCombinations = ProductCombination::whereHas('printSends')
            ->orWhereHas('printReceives')
            ->with('style', 'color')
            ->get();

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


            // Aggregate sent quantities
            $sentData = PrintSendData::where('product_combination_id', $pc->id);
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $sentData->whereBetween('date', [$request->start_date, $request->end_date]);
            }
            $sentData = $sentData->get();

            foreach ($sentData as $data) {
                foreach ($data->send_quantities as $sizeId => $qty) { // Assuming send_quantities also uses size IDs
                    if (isset($balanceData[$key]['sizes'][$sizeId])) {
                        $balanceData[$key]['sizes'][$sizeId]['sent'] += $qty;
                    }
                }
                $balanceData[$key]['total_sent'] += $data->total_send_quantity;
            }

            // Aggregate received quantities and waste
            $receiveData = PrintReceiveData::where('product_combination_id', $pc->id);
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $receiveData->whereBetween('date', [$request->start_date, $request->end_date]);
            }
            $receiveData = $receiveData->get();

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

            if ($balanceData[$key]['total_balance'] <= 0 && (!$request->filled('start_date') && !$request->filled('end_date'))) {
                unset($balanceData[$key]);
            }
        }

        return view('backend.library.print_receive_data.reports.balance_quantity', [
            'reportData' => array_values($balanceData),
            'allSizes' => $allSizes,
            'sizeIdToName' => $sizeIdToName,
        ]);
    }

    public function wipReport(Request $request)
    {
        $combinations = ProductCombination::where('print_embroidery', true)
            ->with('style', 'color')
            ->get();

        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $sizeIdToName = $allSizes->pluck('name', 'id')->toArray();
        $sizeNameToId = $allSizes->pluck('id', 'name')->map(fn($id) => strtolower($allSizes->firstWhere('id', $id)->name))->toArray();

        $wipData = [];

        foreach ($combinations as $pc) {
            $totalSent = PrintSendData::where('product_combination_id', $pc->id)
                ->sum('total_send_quantity');

            $totalReceived = PrintReceiveData::where('product_combination_id', $pc->id)
                ->sum('total_receive_quantity');

            $totalReceivedWaste = PrintReceiveData::where('product_combination_id', $pc->id)
                ->sum('total_receive_waste_quantity');

            $currentWaiting = $totalSent - $totalReceived - $totalReceivedWaste;

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
                $sendData = PrintSendData::where('product_combination_id', $pc->id)->get();
                foreach ($sendData as $sd) {
                    foreach ($sd->send_quantities as $sizeId => $qty) { // Assuming send_quantities also uses size IDs
                        if (isset($wipData[$key]['sizes'][$sizeId])) {
                            $wipData[$key]['sizes'][$sizeId]['sent'] += $qty;
                        }
                    }
                }

                // Aggregate size quantities for received (good) and waste
                $receiveData = PrintReceiveData::where('product_combination_id', $pc->id)->get();
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

        return view('backend.library.print_receive_data.reports.wip', [
            'wipData' => array_values($wipData),
            'allSizes' => $allSizes,
            'sizeIdToName' => $sizeIdToName,
        ]);
    }

    public function readyToInputReport(Request $request)
    {
        $readyData = [];

        // Product combinations with print_embroidery = false OR sublimation_print = false
        $nonEmbCombinations = ProductCombination::where(function ($query) {
            $query->where('print_embroidery', false)
                ->orWhere('sublimation_print', false);
        })
            ->with('style', 'color')
            ->get();

            // dd($nonEmbCombinations);

        foreach ($nonEmbCombinations as $pc) {
            // Calculate total_cut dynamically by summing from 'cut_quantities' JSON
            $cuttingData = CuttingData::where('product_combination_id', $pc->id)->get();
            $dynamicTotalCut = 0;
            foreach ($cuttingData as $cut) {
                foreach ($cut->cut_quantities as $qty) {
                    $dynamicTotalCut += $qty;
                }
            }

            $readyData[] = [
                'style' => $pc->style->name,
                'color' => $pc->color->name,
                'type' => 'No Print/Emb Needed',
                'total_cut' => $dynamicTotalCut, // Use dynamic total cut
                'total_sent' => 0,
                'total_received_good' => 0,
                'total_received_waste' => 0,
            ];
        }

        // dd($readyData);

        // Product combinations with print_embroidery = true OR sublimation_print = true
        $embCombinations = ProductCombination::where(function ($query) {
            $query->where('print_embroidery', true)
                ->orWhere('sublimation_print', true);
        })
            ->with('style', 'color')
            ->get();

        foreach ($embCombinations as $pc) {
            // Calculate total_cut dynamically
            $cuttingData = CuttingData::where('product_combination_id', $pc->id)->get();
            $dynamicTotalCut = 0;
            foreach ($cuttingData as $cut) {
                foreach ($cut->cut_quantities as $qty) {
                    $dynamicTotalCut += $qty;
                }
            }

            // Calculate total_sent dynamically by summing from 'send_quantities' JSON
            $printSendData = PrintSendData::where('product_combination_id', $pc->id)->get();
            $dynamicTotalSent = 0;
            foreach ($printSendData as $send) {
                foreach ($send->send_quantities as $qty) {
                    $dynamicTotalSent += $qty;
                }
            }

            // Calculate total_received_good dynamically by summing from 'receive_quantities' JSON
            $printReceiveData = PrintReceiveData::where('product_combination_id', $pc->id)->get();
            $dynamicTotalReceivedGood = 0;
            foreach ($printReceiveData as $receive) {
                foreach ($receive->receive_quantities as $qty) {
                    $dynamicTotalReceivedGood += $qty;
                }
            }

            // Calculate total_received_waste dynamically by summing from 'receive_waste_quantities' JSON
            $dynamicTotalReceivedWaste = 0;
            foreach ($printReceiveData as $receive) {
                if ($receive->receive_waste_quantities) {
                    foreach ($receive->receive_waste_quantities as $qty) {
                        $dynamicTotalReceivedWaste += $qty;
                    }
                }
            }

            // "Ready to input" means either no print/emb needed OR all sent items have been received (good quantity only).
            // Here, dynamicTotalSent must match dynamicTotalReceivedGood to be 'ready'.
            if ($dynamicTotalSent > 0 && $dynamicTotalSent == $dynamicTotalReceivedGood && $dynamicTotalReceivedWaste >= 0) {
                $readyData[] = [
                    'style' => $pc->style->name,
                    'color' => $pc->color->name,
                    'type' => 'Print/Emb Completed',
                    'total_cut' => $dynamicTotalCut,
                    'total_sent' => $dynamicTotalSent,
                    'total_received_good' => $dynamicTotalReceivedGood,
                    'total_received_waste' => $dynamicTotalReceivedWaste,
                ];
            }
        }

        return view('backend.library.print_receive_data.reports.ready', compact('readyData'));
    }
}
