<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\CuttingData;
use App\Models\ProductCombination;
use App\Models\Size;
use App\Models\Style;
use App\Models\SublimationPrintSend;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SublimationPrintSendController extends Controller
{
    public function index(Request $request)
    {
        $query = SublimationPrintSend::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

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
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Get distinct PO numbers from CuttingData where product combination has sublimation_print = true
        $sublimationProductIds = ProductCombination::where('sublimation_print', true)->pluck('id');
        $distinctPoNumbers = CuttingData::whereIn('product_combination_id', $sublimationProductIds)
            ->distinct()
            ->pluck('po_number')
            ->filter()
            ->values();

        return view('backend.library.sublimation_print_send_data.create', compact('distinctPoNumbers', 'allSizes'));
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
            'rows.*.sublimation_print_send_quantities.*' => 'nullable|integer|min:0',
            'rows.*.sublimation_print_send_waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->rows as $row) {
                $sendQuantities = [];
                $wasteQuantities = [];
                $totalSendQuantity = 0;
                $totalWasteQuantity = 0;

                // Process send quantities
                foreach ($row['sublimation_print_send_quantities'] as $sizeId => $quantity) {
                    if ($quantity > 0) {
                        $size = Size::find($sizeId);
                        $sendQuantities[$size->name] = (int)$quantity;
                        $totalSendQuantity += (int)$quantity;
                    }
                }

                // Process waste quantities
                foreach ($row['sublimation_print_send_waste_quantities'] as $sizeId => $quantity) {
                    if ($quantity > 0) {
                        $size = Size::find($sizeId);
                        $wasteQuantities[$size->name] = (int)$quantity;
                        $totalWasteQuantity += (int)$quantity;
                    }
                }

                SublimationPrintSend::create([
                    'date' => $request->date,
                    'product_combination_id' => $row['product_combination_id'],
                    'po_number' => implode(',', $request->po_number),
                    'old_order' => $request->old_order,
                    'sublimation_print_send_quantities' => $sendQuantities,
                    'total_sublimation_print_send_quantity' => $totalSendQuantity,
                    'sublimation_print_send_waste_quantities' => $wasteQuantities,
                    'total_sublimation_print_send_waste_quantity' => $totalWasteQuantity,
                ]);
            }

            DB::commit();

            return redirect()->route('sublimation_print_send_data.index')
                ->with('success', 'Sublimation Print/Send data added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(SublimationPrintSend $sublimationPrintSendDatum)
    {
        $sublimationPrintSendDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();

        return view('backend.library.sublimation_print_send_data.show', compact('sublimationPrintSendDatum', 'allSizes'));
    }

    public function edit(SublimationPrintSend $sublimationPrintSendDatum)
    {
        $sublimationPrintSendDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();

        return view('backend.library.sublimation_print_send_data.edit', compact('sublimationPrintSendDatum', 'allSizes'));
    }

    public function update(Request $request, SublimationPrintSend $sublimationPrintSendDatum)
    {
        $request->validate([
            'date' => 'required|date',
            'sublimation_print_send_quantities.*' => 'nullable|integer|min:0',
            'sublimation_print_send_waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        try {
            $sendQuantities = [];
            $wasteQuantities = [];
            $totalSendQuantity = 0;
            $totalWasteQuantity = 0;

            // Process send quantities
            foreach ($request->sublimation_print_send_quantities as $sizeName => $quantity) {
                if ($quantity > 0) {
                    $sendQuantities[$sizeName] = (int)$quantity;
                    $totalSendQuantity += (int)$quantity;
                }
            }

            // Process waste quantities
            foreach ($request->sublimation_print_send_waste_quantities as $sizeName => $quantity) {
                if ($quantity > 0) {
                    $wasteQuantities[$sizeName] = (int)$quantity;
                    $totalWasteQuantity += (int)$quantity;
                }
            }

            $sublimationPrintSendDatum->update([
                'date' => $request->date,
                'sublimation_print_send_quantities' => $sendQuantities,
                'total_sublimation_print_send_quantity' => $totalSendQuantity,
                'sublimation_print_send_waste_quantities' => $wasteQuantities,
                'total_sublimation_print_send_waste_quantity' => $totalWasteQuantity,
            ]);

            return redirect()->route('sublimation_print_send_data.index')
                ->with('success', 'Sublimation Print/Send data updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(SublimationPrintSend $sublimationPrintSendDatum)
    {
        $sublimationPrintSendDatum->delete();

        return redirect()->route('sublimation_print_send_data.index')
            ->with('success', 'Sublimation Print/Send data deleted successfully.');
    }

    public function find(Request $request)
    {
        $poNumbers = $request->input('po_numbers', []);

        // Get product combinations with sublimation_print = true
        $sublimationProductIds = ProductCombination::where('sublimation_print', true)->get()->pluck('id');

        // Get cutting data for the selected PO numbers and sublimation products
        $cuttingData = CuttingData::whereIn('po_number', $poNumbers)
            ->whereIn('product_combination_id', $sublimationProductIds)
            ->with('productCombination.style', 'productCombination.color')
            ->get()
            ->groupBy('po_number');

        $result = [];

        foreach ($cuttingData as $poNumber => $cuttingRecords) {
            $result[$poNumber] = [];

            foreach ($cuttingRecords as $cuttingRecord) {
                $productCombination = $cuttingRecord->productCombination;

                if (!$productCombination) {
                    continue;
                }

                $availableQuantities = $this->getAvailableSendQuantities($productCombination)->getData()->availableQuantities;

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
        $sentQuantities = SublimationPrintSend::where('product_combination_id', $productCombination->id)
            ->get()
            ->pluck('sublimation_print_send_quantities')
            ->reduce(function ($carry, $quantities) {
                foreach ($quantities as $size => $qty) {
                    $carry[$size] = ($carry[$size] ?? 0) + $qty;
                }
                return $carry;
            }, []);

        foreach ($sizes as $size) {
            $sizeName = $size->name;
            $cut = $cutQuantities[$sizeName] ?? 0;
            $sent = $sentQuantities[$sizeName] ?? 0;
            $availableQuantities[$sizeName] = max(0, $cut - $sent);
        }

        return response()->json([
            'availableQuantities' => $availableQuantities,
            'sizes' => $sizes
        ]);
    }

    // Reports
    public function totalPrintEmbSendReport(Request $request)
    {
        $query = SublimationPrintSend::with('productCombination.style', 'productCombination.color');

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

            foreach ($data->sublimation_print_send_quantities as $size => $qty) {
                $normalized = strtolower($size);
                if (array_key_exists($normalized, $reportData[$key]['sizes'])) {
                    $reportData[$key]['sizes'][$normalized] += $qty;
                }
            }
            $reportData[$key]['total'] += $data->total_sublimation_print_send_quantity;
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

            $totalSent = SublimationPrintSend::where('product_combination_id', $pc->id)
                ->sum('total_sublimation_print_send_quantity');

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

                $sendData = SublimationPrintSend::where('product_combination_id', $pc->id)->get();
                foreach ($sendData as $sd) {
                    foreach ($sd->sublimation_print_send_quantities as $size => $qty) {
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

            $totalSent = SublimationPrintSend::where('product_combination_id', $pc->id)
                ->sum('total_sublimation_print_send_quantity');

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

        $sentQuantities = SublimationPrintSend::where('product_combination_id', $product_combination_id)
            ->get()
            ->flatMap(function ($record) {
                return collect($record->sublimation_print_send_quantities)
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
