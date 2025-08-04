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
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();

        return view('backend.library.print_receive_data.index', compact('printReceiveData', 'allSizes'));
    }

    public function create()
    {
        $productCombinations = ProductCombination::whereHas('printSends')
            ->with('buyer', 'style', 'color')
            ->get();

        $sizes = Size::where('is_active', 1)->get();

        return view('backend.library.print_receive_data.create', compact('productCombinations', 'sizes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'product_combination_id' => 'required|exists:product_combinations,id',
            'quantities.*' => 'nullable|integer|min:0',
        ]);

        $productCombination = ProductCombination::findOrFail($request->product_combination_id);
        $availableQuantities = $this->getAvailableReceiveQuantities($productCombination)->getData()->availableQuantities;

        $receiveQuantities = [];
        $totalReceiveQuantity = 0;
        $errors = [];

        foreach ($request->input('quantities', []) as $sizeId => $quantity) {
            $size = Size::find($sizeId);
            if ($size && $quantity > 0) {
                $sizeName = strtolower($size->name);
                $available = $availableQuantities[$sizeName] ?? 0;
                if ($quantity > $available) {
                    $errors["quantities.$sizeId"] = "Quantity for {$size->name} exceeds available to receive ($available)";
                } else {
                    $receiveQuantities[$size->name] = (int)$quantity;
                    $totalReceiveQuantity += (int)$quantity;
                }
            }
        }

        if (!empty($errors)) {
            return redirect()->back()->withErrors($errors)->withInput();
        }

        PrintReceiveData::create([
            'date' => $request->date,
            'product_combination_id' => $request->product_combination_id,
            'receive_quantities' => $receiveQuantities,
            'total_receive_quantity' => $totalReceiveQuantity,
        ]);

        return redirect()->route('print_receive_data.index')->with('success', 'Print/Receive data added successfully.');
    }

    public function show(PrintReceiveData $printReceiveDatum)
    {
        return view('backend.library.print_receive_data.show', compact('printReceiveDatum'));
    }

    public function edit(PrintReceiveData $printReceiveDatum)
    {
        $printReceiveDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $sizes = Size::where('is_active', 1)->get();
        $availableQuantities = $this->getAvailableReceiveQuantities($printReceiveDatum->productCombination)->getData()->availableQuantities;

        $sizeData = $sizes->map(function ($size) use ($printReceiveDatum, $availableQuantities) {
            $sizeName = strtolower($size->name);
            return [
                'id' => $size->id,
                'name' => $size->name,
                'available' => $availableQuantities[$sizeName] ?? 0,
                'current_quantity' => $printReceiveDatum->receive_quantities[$size->name] ?? 0
            ];
        });

        return view('backend.library.print_receive_data.edit', [
            'printReceiveDatum' => $printReceiveDatum,
            'sizes' => $sizeData
        ]);
    }

    public function update(Request $request, PrintReceiveData $printReceiveDatum)
    {
        $request->validate([
            'date' => 'required|date',
            'quantities.*' => 'nullable|integer|min:0',
        ]);

        $productCombination = $printReceiveDatum->productCombination;
        $availableQuantities = $this->getAvailableReceiveQuantities($productCombination)->getData()->availableQuantities;

        $receiveQuantities = [];
        $totalReceiveQuantity = 0;
        $errors = [];

        foreach ($request->input('quantities', []) as $sizeId => $quantity) {
            $size = Size::find($sizeId);
            if ($size && $quantity > 0) {
                $sizeName = strtolower($size->name);
                $maxAllowed = ($availableQuantities[$sizeName] ?? 0) + ($printReceiveDatum->receive_quantities[$size->name] ?? 0);
                if ($quantity > $maxAllowed) {
                    $errors["quantities.$sizeId"] = "Quantity for {$size->name} exceeds available to receive ($maxAllowed)";
                } else {
                    $receiveQuantities[$size->name] = (int)$quantity;
                    $totalReceiveQuantity += (int)$quantity;
                }
            }
        }

        if (!empty($errors)) {
            return redirect()->back()->withErrors($errors)->withInput();
        }

        $printReceiveDatum->update([
            'date' => $request->date,
            'receive_quantities' => $receiveQuantities,
            'total_receive_quantity' => $totalReceiveQuantity,
        ]);

        return redirect()->route('print_receive_data.index')->with('success', 'Print/Receive data updated successfully.');
    }

    public function destroy(PrintReceiveData $printReceiveDatum)
    {
        $printReceiveDatum->delete();
        return redirect()->route('print_receive_data.index')->with('success', 'Print/Receive data deleted successfully.');
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
                foreach ($quantities as $size => $qty) {
                    $normalized = strtolower($size);
                    $carry[$normalized] = ($carry[$normalized] ?? 0) + $qty;
                }
                return $carry;
            }, []);

        // Sum received quantities per size
        $receivedQuantities = PrintReceiveData::where('product_combination_id', $productCombination->id)
            ->get()
            ->pluck('receive_quantities')
            ->reduce(function ($carry, $quantities) {
                foreach ($quantities as $size => $qty) {
                    $normalized = strtolower($size);
                    $carry[$normalized] = ($carry[$normalized] ?? 0) + $qty;
                }
                return $carry;
            }, []);

        foreach ($sizes as $size) {
            $sizeName = strtolower($size->name);
            $sent = $sentQuantities[$sizeName] ?? 0;
            $received = $receivedQuantities[$sizeName] ?? 0;
            $availableQuantities[$sizeName] = max(0, $sent - $received);
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
            $reportData = [];

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

                foreach ($data->receive_quantities as $size => $qty) {
                    $normalized = strtolower($size);
                    if (array_key_exists($normalized, $reportData[$key]['sizes'])) {
                        $reportData[$key]['sizes'][$normalized] += $qty;
                    }
                }
                $reportData[$key]['total'] += $data->total_receive_quantity;
            }

            return view('backend.library.print_receive_data.reports.total_receive', [
                'reportData' => array_values($reportData),
                'allSizes' => $allSizes
            ]);
        }

        public function totalPrintEmbBalanceReport(Request $request)
        {
            $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
            $balanceData = [];

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
                $sentData = PrintSendData::where('product_combination_id', $pc->id);
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $sentData->whereBetween('date', [$request->start_date, $request->end_date]);
                }
                $sentData = $sentData->get();

                foreach ($sentData as $data) {
                    foreach ($data->send_quantities as $size => $qty) {
                        $normalized = strtolower($size);
                        if (isset($balanceData[$key]['sizes'][$normalized])) {
                            $balanceData[$key]['sizes'][$normalized]['sent'] += $qty;
                        }
                    }
                    $balanceData[$key]['total_sent'] += $data->total_send_quantity;
                }

                // Aggregate received quantities
                $receiveData = PrintReceiveData::where('product_combination_id', $pc->id);
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $receiveData->whereBetween('date', [$request->start_date, $request->end_date]);
                }
                $receiveData = $receiveData->get();

                foreach ($receiveData as $data) {
                    foreach ($data->receive_quantities as $size => $qty) {
                        $normalized = strtolower($size);
                        if (isset($balanceData[$key]['sizes'][$normalized])) {
                            $balanceData[$key]['sizes'][$normalized]['received'] += $qty;
                        }
                    }
                    $balanceData[$key]['total_received'] += $data->total_receive_quantity;
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

            return view('backend.library.print_receive_data.reports.balance_quantity', [
                'reportData' => array_values($balanceData),
                'allSizes' => $allSizes
            ]);
        }

        public function wipReport(Request $request)
        {
            // Get product combinations with print_embroidery = true
            $combinations = ProductCombination::where('print_embroidery', true)
                ->with('style', 'color')
                ->get();

            $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
            $wipData = [];

            foreach ($combinations as $pc) {
                $totalSent = PrintSendData::where('product_combination_id', $pc->id)
                    ->sum('total_send_quantity');

                // Get total received quantity for this product combination
                $totalReceived = PrintReceiveData::where('product_combination_id', $pc->id)
                    ->sum('total_receive_quantity');

                // WIP is total sent - total received. It's the items still at print/emb.
                // Only show if there's a positive balance (more sent than received)
                if (($totalSent - $totalReceived) > 0) {
                    $key = $pc->style->name . '-' . $pc->color->name;

                    if (!isset($wipData[$key])) {
                        $wipData[$key] = [
                            'style' => $pc->style->name,
                            'color' => $pc->color->name,
                            'sizes' => [],
                            'total_sent' => 0,
                            'total_received' => 0,
                            'waiting' => 0
                        ];

                        // Initialize sizes
                        foreach ($allSizes as $size) {
                            $wipData[$key]['sizes'][strtolower($size->name)] = [
                                'sent' => 0,
                                'received' => 0,
                                'waiting' => 0
                            ];
                        }
                    }

                    $wipData[$key]['total_sent'] += $totalSent;
                    $wipData[$key]['total_received'] += $totalReceived;
                    $wipData[$key]['waiting'] += ($totalSent - $totalReceived);

                    // Aggregate size quantities for sent
                    $sendData = PrintSendData::where('product_combination_id', $pc->id)->get();
                    foreach ($sendData as $sd) {
                        foreach ($sd->send_quantities as $size => $qty) {
                            $normalizedSize = strtolower($size);
                            if (isset($wipData[$key]['sizes'][$normalizedSize])) {
                                $wipData[$key]['sizes'][$normalizedSize]['sent'] += $qty;
                            }
                        }
                    }

                    // Aggregate size quantities for received
                    $receiveData = PrintReceiveData::where('product_combination_id', $pc->id)->get();
                    foreach ($receiveData as $rd) {
                        foreach ($rd->receive_quantities as $size => $qty) {
                            $normalizedSize = strtolower($size);
                            if (isset($wipData[$key]['sizes'][$normalizedSize])) {
                                $wipData[$key]['sizes'][$normalizedSize]['received'] += $qty;
                            }
                        }
                    }

                    // Calculate waiting per size
                    foreach ($wipData[$key]['sizes'] as $sizeName => &$data) {
                        $data['waiting'] = $data['sent'] - $data['received'];
                    }
                    unset($data); // Unset the reference
                }
            }

            return view('backend.library.print_send_data.reports.wip', [
                'wipData' => array_values($wipData),
                'allSizes' => $allSizes
            ]);
        }

        public function readyToInputReport(Request $request)
        {
            $readyData = [];

            // Product combinations with print_embroidery = false
            $nonEmbCombinations = ProductCombination::where('print_embroidery', false)
                ->with('style', 'color')
                ->get();

            foreach ($nonEmbCombinations as $pc) {
                $totalCut = CuttingData::where('product_combination_id', $pc->id)->sum('total_cut_quantity');
                $readyData[] = [
                    'style' => $pc->style->name,
                    'color' => $pc->color->name,
                    'type' => 'No Print/Emb Needed',
                    'total_cut' => $totalCut,
                    'total_sent' => 0,
                    'total_received' => 0
                ];
            }

            // Product combinations with print_embroidery = true and completed (total sent == total received)
            $embCombinations = ProductCombination::where('print_embroidery', true)
                ->with('style', 'color')
                ->get();

            foreach ($embCombinations as $pc) {
                $totalCut = CuttingData::where('product_combination_id', $pc->id)
                    ->sum('total_cut_quantity');

                $totalSent = PrintSendData::where('product_combination_id', $pc->id)
                    ->sum('total_send_quantity');

                // Get total received quantity
                $totalReceived = PrintReceiveData::where('product_combination_id', $pc->id)
                    ->sum('total_receive_quantity');

                // "Ready to input" means either no print/emb needed OR all sent items have been received.
                if ($totalSent > 0 && $totalSent == $totalReceived) {
                    $readyData[] = [
                        'style' => $pc->style->name,
                        'color' => $pc->color->name,
                        'type' => 'Print/Emb Completed',
                        'total_cut' => $totalCut,
                        'total_sent' => $totalSent,
                        'total_received' => $totalReceived
                    ];
                }
            }

            return view('backend.library.print_send_data.reports.ready', compact('readyData'));
        }
}
