<?php

namespace App\Http\Controllers;

use App\Models\CuttingData;
use App\Models\PrintSendData;
use App\Models\ProductCombination;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrintSendDataController extends Controller
{
    public function index(Request $request)
    {
        $query = PrintSendData::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        
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

        $printSendData = $query->orderBy('date', 'desc')->paginate(10);
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();

        return view('backend.library.print_send_data.index', compact('printSendData', 'allSizes'));
    }

    public function create()
    {
        // Only show product combinations with print_embroidery = true
        $productCombinations = ProductCombination::where('print_embroidery', true)
            ->with('buyer', 'style', 'color')
            ->get();
            
        $sizes = Size::where('is_active', 1)->get();

        return view('backend.library.print_send_data.create', compact('productCombinations', 'sizes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'product_combination_id' => 'required|exists:product_combinations,id',
            'quantities.*' => 'nullable|integer|min:0',
        ]);

        $productCombination = ProductCombination::findOrFail($request->product_combination_id);
        
        // Calculate maximum allowed quantity
        $totalCut = CuttingData::where('product_combination_id', $productCombination->id)
            ->sum('total_cut_quantity');
            
        $totalSent = PrintSendData::where('product_combination_id', $productCombination->id)
            ->sum('total_send_quantity');
            
        $maxAllowed = $totalCut - $totalSent;

        $sendQuantities = [];
        $totalSendQuantity = 0;
        $allowedSizeIds = $productCombination->size_ids;

        foreach ($request->input('quantities') as $sizeId => $quantity) {
            if (in_array($sizeId, $allowedSizeIds)) {
                $size = Size::find($sizeId);
                if ($size && $quantity) {
                    $sendQuantities[$size->name] = (int)$quantity;
                    $totalSendQuantity += (int)$quantity;
                }
            }
        }

        // Validate against maximum allowed quantity
        if ($totalSendQuantity > $maxAllowed) {
            return redirect()->back()
                ->withErrors("Total send quantity ($totalSendQuantity) exceeds available quantity ($maxAllowed)")
                ->withInput();
        }

        PrintSendData::create([
            'date' => $request->date,
            'product_combination_id' => $request->product_combination_id,
            'send_quantities' => $sendQuantities,
            'total_send_quantity' => $totalSendQuantity,
        ]);

        return redirect()->route('print_send_data.index')->with('success', 'Print/Send data added successfully.');
    }

    // Other CRUD methods (show, edit, update, destroy) similar to CuttingDataController
    public function show(PrintSendData $printSendDatum)
    {
        return view('backend.library.print_send_data.show', compact('printSendDatum'));
    }
    public function edit(PrintSendData $printSendDatum)
    {
        $printSendDatum->load(
            'productCombination.buyer',
            'productCombination.style',
            'productCombination.color'
        );

        // Calculate available quantities (excluding current record)
        $totalCut = CuttingData::where('product_combination_id', $printSendDatum->product_combination_id)
            ->sum('total_cut_quantity');

        $totalSent = PrintSendData::where('product_combination_id', $printSendDatum->product_combination_id)
            ->where('id', '!=', $printSendDatum->id)
            ->sum('total_send_quantity');

        $available = $totalCut - $totalSent;

        // Get sizes with availability information
        $sizes = $printSendDatum->productCombination->sizes->map(function ($size) use ($printSendDatum, $totalCut, $totalSent) {
            $sizeName = $size->name;

            // Calculate cut quantity for this size
            $cut = CuttingData::where('product_combination_id', $printSendDatum->product_combination_id)
                ->sum(DB::raw("CAST(JSON_VALUE(cut_quantities, '$.\"$sizeName\"') AS INT)")); // CAST to INT

            // Calculate sent quantity for this size (excluding current record)
            $sent = PrintSendData::where('product_combination_id', $printSendDatum->product_combination_id)
                ->where('id', '!=', $printSendDatum->id)
                ->sum(DB::raw("CAST(JSON_VALUE(send_quantities, '$.\"$sizeName\"') AS INT)")); // CAST to INT

            return [
                'id' => $size->id,
                'name' => $sizeName,
                'cut' => $cut ?? 0,
                'sent' => $sent ?? 0,
                'available' => ($cut ?? 0) - ($sent ?? 0),
                'current_quantity' => $printSendDatum->send_quantities[$sizeName] ?? 0
            ];
        });

        return view('backend.library.print_send_data.edit', [
            'printSendDatum' => $printSendDatum,
            'sizes' => $sizes,
            'totalCut' => $totalCut,
            'totalSent' => $totalSent,
            'available' => $available
        ]);
    }

    public function update(Request $request, PrintSendData $printSendDatum)
    {
        $request->validate([
            'date' => 'required|date',
            'quantities.*' => 'nullable|integer|min:0',
        ]);

        // Calculate available quantities (excluding current record)
        $totalCut = CuttingData::where('product_combination_id', $printSendDatum->product_combination_id)
            ->sum('total_cut_quantity');

        $totalSent = PrintSendData::where('product_combination_id', $printSendDatum->product_combination_id)
            ->where('id', '!=', $printSendDatum->id)
            ->sum('total_send_quantity');

        $maxAllowed = $totalCut - $totalSent;

        $sendQuantities = [];
        $totalSendQuantity = 0;
        $errors = [];

        foreach ($request->input('quantities', []) as $sizeId => $quantity) {
            $size = Size::find($sizeId);
            if ($size && $quantity > 0) {
                $sizeName = $size->name;

                // Calculate available for this size (excluding current record)
                $cut = CuttingData::where('product_combination_id', $printSendDatum->product_combination_id)
                    ->sum(DB::raw("CAST(JSON_VALUE(cut_quantities, '$.\"$sizeName\"') AS INT)")); // CAST to INT

                $sent = PrintSendData::where('product_combination_id', $printSendDatum->product_combination_id)
                    ->where('id', '!=', $printSendDatum->id)
                    ->sum(DB::raw("CAST(JSON_VALUE(send_quantities, '$.\"$sizeName\"') AS INT)")); // CAST to INT

                $available = ($cut ?? 0) - ($sent ?? 0);

                if ($quantity > $available) {
                    $errors["quantities.$sizeId"] = "Quantity for $sizeName exceeds available ($available)";
                    continue;
                }

                $sendQuantities[$sizeName] = (int)$quantity;
                $totalSendQuantity += (int)$quantity;
            }
        }

        if (!empty($errors)) {
            return redirect()->back()->withErrors($errors)->withInput();
        }

        if ($totalSendQuantity > $maxAllowed) {
            return redirect()->back()
                ->withErrors(['total' => "Total send quantity exceeds available quantity"])
                ->withInput();
        }

        $printSendDatum->update([
            'date' => $request->date,
            'send_quantities' => $sendQuantities,
            'total_send_quantity' => $totalSendQuantity,
        ]);

        return redirect()->route('print_send_data.index')
            ->with('success', 'Print/Send data updated successfully.');
    }

    public function destroy(PrintSendData $printSendDatum)
    {
        $printSendDatum->delete();

        return redirect()->route('print_send_data.index')
            ->with('success', 'Print/Send data deleted successfully.');
    }

    // Reports
    public function totalPrintEmbSendReport(Request $request)
    {
        $query = PrintSendData::with('productCombination.style', 'productCombination.color');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $printSendData = $query->get();
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $reportData = [];

        foreach ($printSendData as $data) {
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

            foreach ($data->send_quantities as $size => $qty) {
                $normalized = strtolower($size);
                if (array_key_exists($normalized, $reportData[$key]['sizes'])) {
                    $reportData[$key]['sizes'][$normalized] += $qty;
                }
            }
            $reportData[$key]['total'] += $data->total_send_quantity;
        }

        return view('backend.library.print_send_data.reports.total', [
            'reportData' => array_values($reportData),
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
            $totalCut = CuttingData::where('product_combination_id', $pc->id)
                ->sum('total_cut_quantity');
                
            $totalSent = PrintSendData::where('product_combination_id', $pc->id)
                ->sum('total_send_quantity');
                
            if ($totalCut > $totalSent) {
                $key = $pc->style->name . '-' . $pc->color->name;
                
                if (!isset($wipData[$key])) {
                    $wipData[$key] = [
                        'style' => $pc->style->name,
                        'color' => $pc->color->name,
                        'sizes' => [],
                        'total_cut' => 0,
                        'total_sent' => 0,
                        'waiting' => 0
                    ];
                    
                    // Initialize sizes
                    foreach ($allSizes as $size) {
                        $wipData[$key]['sizes'][$size->name] = [
                            'cut' => 0,
                            'sent' => 0,
                            'waiting' => 0
                        ];
                    }
                }
                
                $wipData[$key]['total_cut'] += $totalCut;
                $wipData[$key]['total_sent'] += $totalSent;
                $wipData[$key]['waiting'] += ($totalCut - $totalSent);
                
                // Aggregate size quantities
                $cuttingData = CuttingData::where('product_combination_id', $pc->id)->get();
                foreach ($cuttingData as $cd) {
                    foreach ($cd->cut_quantities as $size => $qty) {
                        if (isset($wipData[$key]['sizes'][$size])) {
                            $wipData[$key]['sizes'][$size]['cut'] += $qty;
                        }
                    }
                }
                
                $sendData = PrintSendData::where('product_combination_id', $pc->id)->get();
                foreach ($sendData as $sd) {
                    foreach ($sd->send_quantities as $size => $qty) {
                        if (isset($wipData[$key]['sizes'][$size])) {
                            $wipData[$key]['sizes'][$size]['sent'] += $qty;
                        }
                    }
                }
                
                // Calculate waiting per size
                foreach ($wipData[$key]['sizes'] as $size => $data) {
                    $wipData[$key]['sizes'][$size]['waiting'] = 
                        $data['cut'] - $data['sent'];
                }
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
            $readyData[] = [
                'style' => $pc->style->name,
                'color' => $pc->color->name,
                'type' => 'No Print/Emb Needed',
                'total_cut' => CuttingData::where('product_combination_id', $pc->id)->sum('total_cut_quantity'),
                'total_sent' => 0
            ];
        }
        
        // Product combinations with print_embroidery = true and completed
        $embCombinations = ProductCombination::where('print_embroidery', true)
            ->with('style', 'color')
            ->get();
            
        foreach ($embCombinations as $pc) {
            $totalCut = CuttingData::where('product_combination_id', $pc->id)
                ->sum('total_cut_quantity');
                
            $totalSent = PrintSendData::where('product_combination_id', $pc->id)
                ->sum('total_send_quantity');
                
            if ($totalSent >= $totalCut) {
                $readyData[] = [
                    'style' => $pc->style->name,
                    'color' => $pc->color->name,
                    'type' => 'Print/Emb Completed',
                    'total_cut' => $totalCut,
                    'total_sent' => $totalSent
                ];
            }
        }

        return view('backend.library.print_send_data.reports.ready', compact('readyData'));
    }
}
