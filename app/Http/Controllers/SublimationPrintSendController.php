<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\CuttingData;
use App\Models\ProductCombination;
use App\Models\Size;
use App\Models\Style;
use App\Models\sublimationPrintSend;
use Illuminate\Http\Request;

class SublimationPrintSendController extends Controller
{
    public function index(Request $request)
    {
        $query = sublimationPrintSend::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

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

        return view('backend.library.sublimation_print_send_data.index', compact('printSendData', 'allSizes'));
    }

    public function create()
    {
        $styles = Style::get();
        $sizes = Size::where('is_active', 1)->get();
        $productCombinations = ProductCombination::whereHas('cuttingData')
            ->with('buyer', 'style', 'color')
            ->get();

        return view('backend.library.sublimation_print_send_data.create', compact('styles', 'sizes', 'productCombinations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'product_combination_id' => 'required|exists:product_combinations,id',
            'quantities.*' => 'nullable|integer|min:0',
        ]);

        $productCombination = ProductCombination::findOrFail($request->product_combination_id);
        $availableQuantities = $this->getAvailableSendQuantities($productCombination)->getData()->availableQuantities;

        $sendQuantities = [];
        $totalSendQuantity = 0;
        $errors = [];

        foreach ($request->input('quantities', []) as $sizeId => $quantity) {
            $size = Size::find($sizeId);
            if ($size && $quantity > 0) {
                $sizeName = strtolower($size->name);
                $available = $availableQuantities[$sizeName] ?? 0;
                if ($quantity > $available) {
                    $errors["quantities.$sizeId"] = "Quantity for {$size->name} exceeds available ($available)";
                } else {
                    $sendQuantities[$size->name] = (int)$quantity;
                    $totalSendQuantity += (int)$quantity;
                }
            }
        }

        if (!empty($errors)) {
            return redirect()->back()->withErrors($errors)->withInput();
        }

        sublimationPrintSend::create([
            'date' => $request->date,
            'product_combination_id' => $request->product_combination_id,
            'send_quantities' => $sendQuantities,
            'total_send_quantity' => $totalSendQuantity,
        ]);

        return redirect()->route('sublimation_print_send_data.index')->with('success', 'Print/Send data added successfully.');
    }

    public function show(sublimationPrintSend $printSendDatum)
    {
        return view('backend.library.sublimation_print_send_data.show', compact('printSendDatum'));
    }

    public function edit(sublimationPrintSend $printSendDatum)
    {
        $printSendDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $sizes = Size::where('is_active', 1)->get();
        $availableQuantities = $this->getAvailableSendQuantities($printSendDatum->productCombination)->getData()->availableQuantities;

        $sizeData = $sizes->map(function ($size) use ($printSendDatum, $availableQuantities) {
            $sizeName = strtolower($size->name);
            return [
                'id' => $size->id,
                'name' => $size->name,
                'available' => $availableQuantities[$sizeName] ?? 0,
                'current_quantity' => $printSendDatum->send_quantities[$size->name] ?? 0
            ];
        });

        return view('backend.library.sublimation_print_send_data.edit', [
            'printSendDatum' => $printSendDatum,
            'sizes' => $sizeData
        ]);
    }

    public function update(Request $request, sublimationPrintSend $printSendDatum)
    {
        $request->validate([
            'date' => 'required|date',
            'quantities.*' => 'nullable|integer|min:0',
        ]);

        $productCombination = $printSendDatum->productCombination;
        $availableQuantities = $this->getAvailableSendQuantities($productCombination)->getData()->availableQuantities;

        $sendQuantities = [];
        $totalSendQuantity = 0;
        $errors = [];

        foreach ($request->input('quantities', []) as $sizeId => $quantity) {
            $size = Size::find($sizeId);
            if ($size && $quantity > 0) {
                $sizeName = strtolower($size->name);
                $maxAllowed = ($availableQuantities[$sizeName] ?? 0) + ($printSendDatum->send_quantities[$size->name] ?? 0);
                if ($quantity > $maxAllowed) {
                    $errors["quantities.$sizeId"] = "Quantity for {$size->name} exceeds available ($maxAllowed)";
                } else {
                    $sendQuantities[$size->name] = (int)$quantity;
                    $totalSendQuantity += (int)$quantity;
                }
            }
        }

        if (!empty($errors)) {
            return redirect()->back()->withErrors($errors)->withInput();
        }

        $printSendDatum->update([
            'date' => $request->date,
            'send_quantities' => $sendQuantities,
            'total_send_quantity' => $totalSendQuantity,
        ]);

        return redirect()->route('sublimation_print_send_data.index')->with('success', 'Print/Send data updated successfully.');
    }

    public function destroy(sublimationPrintSend $printSendDatum)
    {
        $printSendDatum->delete();
        return redirect()->route('sublimation_print_send_data.index')->with('success', 'Print/Send data deleted successfully.');
    }

    public function getAvailableSendQuantities(ProductCombination $productCombination)
    {
        $sizes = Size::where('is_active', 1)->get();
        $availableQuantities = [];

        // Sum cut quantities per size using original case
        $cutQuantities = CuttingData::where('product_combination_id', $productCombination->id)
            ->get()
            ->pluck('cut_quantities')
            ->reduce(function ($carry, $quantities) {
                foreach ($quantities as $size => $qty) {
                    $carry[$size] = ($carry[$size] ?? 0) + $qty;
                }
                return $carry;
            }, []);

        // Sum sent quantities per size using original case
        $sentQuantities = sublimationPrintSend::where('product_combination_id', $productCombination->id)
            ->get()
            ->pluck('send_quantities')
            ->reduce(function ($carry, $quantities) {
                foreach ($quantities as $size => $qty) {
                    $carry[$size] = ($carry[$size] ?? 0) + $qty;
                }
                return $carry;
            }, []);

        foreach ($sizes as $size) {
            $sizeName = $size->name; // Use original case, e.g., "XS"
            $cut = $cutQuantities[$sizeName] ?? 0;
            $sent = $sentQuantities[$sizeName] ?? 0;
            $availableQuantities[$sizeName] = max(0, $cut - $sent);
        }

        return response()->json([
            'availableQuantities' => $availableQuantities,
            'sizes' => $sizes
        ]);
    }

    public function getColors($style_id)
    {
        $colors = Color::whereHas('productCombinations', function ($query) use ($style_id) {
            $query->where('style_id', $style_id)
                ->where('sublimation_print', true);
        })->get(['id', 'name']);

        return response()->json($colors);
    }

    public function getCombination($style_id, $color_id)
    {
        $combination = ProductCombination::with('buyer')
            ->where('style_id', $style_id)
            ->where('color_id', $color_id)
            ->where('sublimation_print', true)
            ->first();

        if ($combination) {
            return response()->json([
                'success' => true,
                'combination' => [
                    'id' => $combination->id,
                    'buyer_name' => $combination->buyer->name,
                    'size_ids' => $combination->size_ids
                ]
            ]);
        }

        return response()->json(['success' => false]);
    }


    // Reports
    public function totalPrintEmbSendReport(Request $request)
    {
        $query = sublimationPrintSend::with('productCombination.style', 'productCombination.color');

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

        return view('backend.library.sublimation_print_send_data.reports.total', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes
        ]);
    }

    public function wipReport(Request $request)
    {
        // Get product combinations with sublimation_print = true
        $combinations = ProductCombination::where('sublimation_print', true)
            ->with('style', 'color')
            ->get();

        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $wipData = [];

        foreach ($combinations as $pc) {
            $totalCut = CuttingData::where('product_combination_id', $pc->id)
                ->sum('total_cut_quantity');

            $totalSent = sublimationPrintSend::where('product_combination_id', $pc->id)
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

                $sendData = sublimationPrintSend::where('product_combination_id', $pc->id)->get();
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

        return view('backend.library.sublimation_print_send_data.reports.wip', [
            'wipData' => array_values($wipData),
            'allSizes' => $allSizes
        ]);
    }

    public function readyToInputReport(Request $request)
    {
        $readyData = [];

        // Product combinations with sublimation_print = false
        $nonEmbCombinations = ProductCombination::where('sublimation_print', false)
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

        // Product combinations with sublimation_print = true and completed
        $embCombinations = ProductCombination::where('sublimation_print', true)
            ->with('style', 'color')
            ->get();

        foreach ($embCombinations as $pc) {
            $totalCut = CuttingData::where('product_combination_id', $pc->id)
                ->sum('total_cut_quantity');

            $totalSent = sublimationPrintSend::where('product_combination_id', $pc->id)
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

        return view('backend.library.sublimation_print_send_data.reports.ready', compact('readyData'));
    }
    public function available($product_combination_id)
    {
        $sizes = Size::where('is_active', 1)->get();
        $sizeMap = [];
        foreach ($sizes as $size) {
            $sizeMap[strtolower($size->name)] = $size->id;
        }



        $cutQuantities = CuttingData::where('product_combination_id', $product_combination_id)
            ->get()
            ->flatMap(function ($record) use ($sizeMap) {
                $quantities = [];
                foreach ($record->cut_quantities as $sizeName => $quantity) {
                    $normalized = strtolower(trim($sizeName));
                    if (isset($sizeMap[$normalized])) {
                        $sizeId = $sizeMap[$normalized];
                        $quantities[$sizeId] = $quantity;
                    }
                }
                return $quantities;
            })
            ->groupBy(function ($item, $sizeId) {
                return $sizeId;
            })
            ->map->sum();

        $sentQuantities = sublimationPrintSend::where('product_combination_id', $product_combination_id)
            ->get()
            ->flatMap(function ($record) {
                return collect($record->send_quantities)
                    ->mapWithKeys(fn($qty, $sizeId) => [(int)$sizeId => $qty]);
            })
            ->groupBy('key')
            ->map->sum('value');

        $availableQuantities = [];
        foreach ($cutQuantities as $sizeId => $cutQty) {
            $sentQty = $sentQuantities->get($sizeId, 0);
            $availableQuantities[(string)$sizeId] = $cutQty - $sentQty;
        }

        return response()->json([
            'available' => array_sum($availableQuantities),
            'available_per_size' => $availableQuantities
        ]);
    }
    
}
