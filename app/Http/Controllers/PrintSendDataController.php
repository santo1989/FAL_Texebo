<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\CuttingData;
use App\Models\PrintSendData;
use App\Models\ProductCombination;
use App\Models\Size;
use App\Models\Style;
use App\Models\sublimationPrintReceive;
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
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        return view('backend.library.print_send_data.index', compact('printSendData', 'allSizes'));
    }

    // public function create()
    // {
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //     // Get distinct PO numbers from SublimationPrintReceive
    //     $distinctPoNumbers = SublimationPrintReceive::distinct()
    //         ->pluck('po_number')
    //         ->filter()
    //         ->values();

    //     return view('backend.library.print_send_data.create', compact('distinctPoNumbers', 'allSizes'));
    // }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'po_number' => 'required|array',
            'po_number.*' => 'required|string',
            'old_order' => 'required|in:yes,no',
            'rows' => 'required|array',
            'rows.*.product_combination_id' => 'required|exists:product_combinations,id',
            'rows.*.send_quantities.*' => 'nullable|integer|min:0',
            'rows.*.send_waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->rows as $row) {
                $sendQuantities = [];
                $wasteQuantities = [];
                $totalSendQuantity = 0;
                $totalWasteQuantity = 0;

                // Process send quantities
                foreach ($row['send_quantities'] as $sizeId => $quantity) {
                    if ($quantity > 0) {
                        $size = Size::find($sizeId);
                        $sendQuantities[$size->id] = (int)$quantity;
                        $totalSendQuantity += (int)$quantity;
                    }
                }

                // Process waste quantities
                foreach ($row['send_waste_quantities'] as $sizeId => $quantity) {
                    if ($quantity > 0) {
                        $size = Size::find($sizeId);
                        $wasteQuantities[$size->id] = (int)$quantity;
                        $totalWasteQuantity += (int)$quantity;
                    }
                }

                PrintSendData::create([
                    'date' => $request->date,
                    'product_combination_id' => $row['product_combination_id'],
                    'po_number' => implode(',', $request->po_number),
                    'old_order' => $request->old_order,
                    'send_quantities' => $sendQuantities,
                    'total_send_quantity' => $totalSendQuantity,
                    'send_waste_quantities' => $wasteQuantities,
                    'total_send_waste_quantity' => $totalWasteQuantity,
                ]);
            }

            DB::commit();

            return redirect()->route('print_send_data.index')
                ->with('success', 'Print/Embroidery Send data added successfully.');
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
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        return view('backend.library.print_send_data.edit', compact('printSendDatum', 'allSizes'));
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
                if ($quantity > 0) {
                    $sendQuantities[$sizeId] = (int)$quantity;
                    $totalSendQuantity += (int)$quantity;
                }
            }

            // Process waste quantities
            foreach ($request->send_waste_quantities as $sizeId => $quantity) {
                if ($quantity > 0) {
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
                ->with('success', 'Print/Embroidery Send data updated successfully.');
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
            ->with('success', 'Print/Embroidery Send data deleted successfully.');
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
        $processedCombinations = []; // Track processed combinations

        foreach ($poNumbers as $poNumber) {
            // Get cutting data for the selected PO number with print_embroidery = true
            $cuttingData = CuttingData::where('po_number', 'like', '%' . $poNumber . '%')
                ->with(['productCombination.style', 'productCombination.color', 'productCombination.size'])
                ->whereHas('productCombination', function ($query) {
                    $query->where('print_embroidery', true);
                })
                ->get();

            foreach ($cuttingData as $cutting) {
                if (!$cutting->productCombination) {
                    continue;
                }

                // Create a unique key for this combination
                $combinationKey = $cutting->productCombination->id . '-' .
                    $cutting->productCombination->style->name . '-' .
                    $cutting->productCombination->color->name;

                // Skip if we've already processed this combination
                if (in_array($combinationKey, $processedCombinations)) {
                    continue;
                }

                // Mark this combination as processed
                $processedCombinations[] = $combinationKey;

                $availableQuantities = $this->getAvailableSendQuantities($cutting->productCombination)->getData()->availableQuantities;

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

    public function getAvailableSendQuantities(ProductCombination $productCombination)
    {
        $sizes = Size::where('is_active', 1)->get();
        $availableQuantities = [];

        // Get cut quantities from CuttingData
        $cutQuantities = CuttingData::where('product_combination_id', $productCombination->id)
            ->get()
            ->pluck('cut_quantities')
            ->reduce(function ($carry, $quantities) {
                foreach ($quantities as $sizeId => $qty) {
                    $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                }
                return $carry;
            }, []);

        // Sum already sent quantities per size
        $sentQuantities = PrintSendData::where('product_combination_id', $productCombination->id)
            ->get()
            ->pluck('send_quantities')
            ->reduce(function ($carry, $quantities) {
                foreach ($quantities as $sizeId => $qty) {
                    $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                }
                return $carry;
            }, []);

        // Create a mapping of size IDs to size names
        $sizeIdToName = [];
        foreach ($sizes as $size) {
            $sizeIdToName[$size->id] = $size->name;
        }

        // Calculate available quantities
        foreach ($sizes as $size) {
            $cut = $cutQuantities[$size->id] ?? 0;
            $sent = $sentQuantities[$size->id] ?? 0;
            $availableQuantities[$size->name] = max(0, $cut - $sent);
        }

        return response()->json([
            'availableQuantities' => $availableQuantities,
            'sizes' => $sizes
        ]);
    }

    // Reports
    public function totalPrintEmbSendReport(Request $request)
    {
        $query = PrintSendData::with('productCombination.style', 'productCombination.color');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
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

        return view('backend.library.print_send_data.reports.total', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes
        ]);
    }

    public function wipReport(Request $request)
    {
        $combinations = ProductCombination::where('print_embroidery', true)
            ->with('style', 'color')
            ->get();

        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $wipData = [];

        foreach ($combinations as $pc) {
            // Get all cut quantities per size for this product combination
            $cutQuantitiesPerSize = CuttingData::where('product_combination_id', $pc->id)
                ->get()
                ->pluck('cut_quantities')
                ->reduce(function ($carry, $quantities) {
                    foreach ($quantities as $sizeId => $qty) { // Assuming cut_quantities stores size ID as key
                        $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                    }
                    return $carry;
                }, []);

            // Get all sent quantities per size for this product combination
            $sentQuantitiesPerSize = PrintSendData::where('product_combination_id', $pc->id)
                ->get()
                ->pluck('send_quantities')
                ->reduce(function ($carry, $quantities) {
                    foreach ($quantities as $sizeId => $qty) {
                        $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                    }
                    return $carry;
                }, []);

            $totalCut = array_sum($cutQuantitiesPerSize);
            $totalSent = array_sum($sentQuantitiesPerSize);

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

                    $wipData[$key]['sizes'][$size->id]['cut'] = $cut;
                    $wipData[$key]['sizes'][$size->id]['sent'] = $sent;
                    $wipData[$key]['sizes'][$size->id]['waiting'] = $waiting;

                    $wipData[$key]['total_cut'] += $cut;
                    $wipData[$key]['total_sent'] += $sent;
                    $wipData[$key]['total_waiting'] += $waiting;
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

        // Product combinations with print_embroidery = false (don't need print/emb)
        $nonEmbCombinations = ProductCombination::where('print_embroidery', false)
            ->with('style', 'color')
            ->get();

        foreach ($nonEmbCombinations as $pc) {
            $totalCut = CuttingData::where('product_combination_id', $pc->id)->sum('total_cut_quantity');
            // If there's any cut quantity, it's ready to input
            if ($totalCut > 0) {
                $readyData[] = [
                    'style' => $pc->style->name,
                    'color' => $pc->color->name,
                    'po_number' => null, // PO number might be aggregate or not directly applicable here for all cases
                    'type' => 'No Print/Emb Needed',
                    'total_cut' => $totalCut,
                    'total_sent' => 0, // No sending for non-emb
                    'total_received' => 0, // No receiving for non-emb
                    'status' => 'Ready for Finishing',
                ];
            }
        }

        // Product combinations with print_embroidery = true that have completed the send process
        $embCombinations = ProductCombination::where('print_embroidery', true)
            ->with('style', 'color')
            ->get();

        foreach ($embCombinations as $pc) {
            $totalCut = CuttingData::where('product_combination_id', $pc->id)->sum('total_cut_quantity');
            $totalSent = PrintSendData::where('product_combination_id', $pc->id)->sum('total_send_quantity');

            // If total sent is equal to or greater than total cut (meaning all cut pieces for print/emb are sent out)
            if ($totalSent >= $totalCut && $totalCut > 0) { // Also ensure there was something cut
                // Now, check if all sent items have been received back
                $totalReceived = sublimationPrintReceive::where('product_combination_id', $pc->id)->sum('total_sublimation_print_receive_quantity');

                if ($totalReceived >= $totalSent) { // All sent items received, ready for finishing
                    $readyData[] = [
                        'style' => $pc->style->name,
                        'color' => $pc->color->name,
                        'po_number' => PrintSendData::where('product_combination_id', $pc->id)->pluck('po_number')->unique()->implode(', '), // Aggregate POs
                        'type' => 'Print/Emb Completed & Received',
                        'total_cut' => $totalCut,
                        'total_sent' => $totalSent,
                        'total_received' => $totalReceived,
                        'status' => 'Ready for Finishing',
                    ];
                }
            }
        }

        return view('backend.library.print_send_data.reports.ready', compact('readyData'));
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

        $sentQuantities = PrintSendData::where('product_combination_id', $product_combination_id)
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
