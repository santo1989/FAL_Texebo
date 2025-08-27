<?php

namespace App\Http\Controllers;

use App\Models\CuttingData;
use App\Models\LineInputData;
use App\Models\PrintReceiveData;
use App\Models\ProductCombination;
use App\Models\Size;
use App\Models\SublimationPrintReceive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LineInputDataController extends Controller
{
    public function index(Request $request)
    {
        $query = LineInputData::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

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

        $lineInputData = $query->orderBy('date', 'desc')->paginate(10);
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();

        return view('backend.library.line_input_data.index', compact('lineInputData', 'allSizes'));
    }

    public function create()
    {
        // Get distinct PO numbers based on product combination type
        $distinctPoNumbers = $this->getAvailablePoNumbers();
        $sizes = Size::where('is_active', 1)->get();

        return view('backend.library.line_input_data.create', compact('distinctPoNumbers', 'sizes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'po_number' => 'required|array',
            'po_number.*' => 'required|string',
            'rows' => 'required|array',
            'rows.*.product_combination_id' => 'required|exists:product_combinations,id',
            'rows.*.input_quantities.*' => 'nullable|integer|min:0',
            'rows.*.input_waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->rows as $row) {
                $inputQuantities = [];
                $wasteQuantities = [];
                $totalInputQuantity = 0;
                $totalWasteQuantity = 0;

                // Process input quantities
                foreach ($row['input_quantities'] as $sizeId => $quantity) {
                    if ($quantity !== null && (int)$quantity > 0) {
                        $size = Size::find($sizeId);
                        if ($size) {
                            $inputQuantities[$size->id] = (int)$quantity;
                            $totalInputQuantity += (int)$quantity;
                        }
                    }
                }

                // Process waste quantities
                foreach ($row['input_waste_quantities'] as $sizeId => $quantity) {
                    if ($quantity !== null && (int)$quantity > 0) {
                        $size = Size::find($sizeId);
                        if ($size) {
                            $wasteQuantities[$size->id] = (int)$quantity;
                            $totalWasteQuantity += (int)$quantity;
                        }
                    }
                }

                // Only create a record if there's at least one valid input or waste quantity
                if (!empty($inputQuantities) || !empty($wasteQuantities)) {
                    LineInputData::create([
                        'date' => $request->date,
                        'product_combination_id' => $row['product_combination_id'],
                        'po_number' => implode(',', $request->po_number),
                        'input_quantities' => $inputQuantities,
                        'total_input_quantity' => $totalInputQuantity,
                        'input_waste_quantities' => $wasteQuantities,
                        'total_input_waste_quantity' => $totalWasteQuantity,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('line_input_data.index')
                ->with('success', 'Line input data added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(LineInputData $lineInputDatum)
    {
        $lineInputDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Only valid sizes
        $validSizes = $allSizes->filter(function ($size) use ($lineInputDatum) {
            return isset($lineInputDatum->input_quantities[$size->id]) ||
                isset($lineInputDatum->input_waste_quantities[$size->id]);
        });

        $allSizes = $validSizes->values();

        return view('backend.library.line_input_data.show', compact('lineInputDatum', 'allSizes'));
    }

    public function edit(LineInputData $lineInputDatum)
    {
        $lineInputDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Only valid sizes
        $validSizes = $allSizes->filter(function ($size) use ($lineInputDatum) {
            return isset($lineInputDatum->input_quantities[$size->id]) ||
                isset($lineInputDatum->input_waste_quantities[$size->id]);
        });

        $allSizes = $validSizes->values();

        return view('backend.library.line_input_data.edit', compact('lineInputDatum', 'allSizes'));
    }

    public function update(Request $request, LineInputData $lineInputDatum)
    {
        $request->validate([
            'date' => 'required|date',
            'input_quantities.*' => 'nullable|integer|min:0',
            'input_waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        try {
            $inputQuantities = [];
            $wasteQuantities = [];
            $totalInputQuantity = 0;
            $totalWasteQuantity = 0;

            // Process input quantities
            foreach ($request->input_quantities as $sizeId => $quantity) {
                if ($quantity !== null && (int)$quantity > 0) {
                    $inputQuantities[$sizeId] = (int)$quantity;
                    $totalInputQuantity += (int)$quantity;
                }
            }

            // Process waste quantities
            foreach ($request->input_waste_quantities as $sizeId => $quantity) {
                if ($quantity !== null && (int)$quantity > 0) {
                    $wasteQuantities[$sizeId] = (int)$quantity;
                    $totalWasteQuantity += (int)$quantity;
                }
            }

            $lineInputDatum->update([
                'date' => $request->date,
                'input_quantities' => $inputQuantities,
                'total_input_quantity' => $totalInputQuantity,
                'input_waste_quantities' => $wasteQuantities,
                'total_input_waste_quantity' => $totalWasteQuantity,
            ]);

            return redirect()->route('line_input_data.index')
                ->with('success', 'Line input data updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(LineInputData $lineInputDatum)
    {
        $lineInputDatum->delete();
        return redirect()->route('line_input_data.index')->with('success', 'Line input data deleted successfully.');
    }

    // Reports
    public function totalInputReport(Request $request)
    {
        $query = LineInputData::with('productCombination.style', 'productCombination.color');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $lineInputData = $query->get();
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $reportData = [];

        foreach ($lineInputData as $data) {
            $style = $data->productCombination->style->name;
            $color = $data->productCombination->color->name;
            $key = $style . '-' . $color;

            if (!isset($reportData[$key])) {
                $reportData[$key] = [
                    'style' => $style,
                    'color' => $color,
                    'sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
                    'waste_sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
                    'total' => 0,
                    'total_waste' => 0
                ];
            }

            foreach ($data->input_quantities as $sizeId => $qty) {
                $size = Size::find($sizeId);
                if ($size) {
                    $normalized = strtolower($size->name);
                    if (array_key_exists($normalized, $reportData[$key]['sizes'])) {
                        $reportData[$key]['sizes'][$normalized] += $qty;
                    }
                }
            }
            $reportData[$key]['total'] += $data->total_input_quantity;

            if ($data->input_waste_quantities) {
                foreach ($data->input_waste_quantities as $sizeId => $qty) {
                    $size = Size::find($sizeId);
                    if ($size) {
                        $normalized = strtolower($size->name);
                        if (array_key_exists($normalized, $reportData[$key]['waste_sizes'])) {
                            $reportData[$key]['waste_sizes'][$normalized] += $qty;
                        }
                    }
                }
            }
            $reportData[$key]['total_waste'] += $data->total_input_waste_quantity ?? 0;
        }

        return view('backend.library.line_input_data.reports.total_input', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes
        ]);
    }

    public function inputBalanceReport(Request $request)
    {
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $balanceData = [];

        $productCombinations = ProductCombination::whereHas('cuttingData')
            ->orWhereHas('printReceives')
            ->orWhereHas('sublimationPrintReceives')
            ->with('style', 'color')
            ->get();

        foreach ($productCombinations as $pc) {
            $style = $pc->style->name;
            $color = $pc->color->name;
            $key = $style . '-' . $color;

            $balanceData[$key] = [
                'style' => $style,
                'color' => $color,
                'sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), ['available' => 0, 'input' => 0, 'waste' => 0, 'balance' => 0]),
                'total_available' => 0,
                'total_input' => 0,
                'total_waste' => 0,
                'total_balance' => 0,
            ];

            // Get available quantities based on product combination type
            if ($pc->print_embroidery && !$pc->sublimation_print) {
                // Only print_embroidery is true - from PrintReceiveData
                $receiveQuantities = PrintReceiveData::where('product_combination_id', $pc->id)
                    ->get()
                    ->flatMap(fn($item) => $item->receive_quantities)
                    ->groupBy(fn($value, $key) => strtolower(Size::find($key)->name))
                    ->map(fn($group) => $group->sum())
                    ->toArray();

                foreach ($receiveQuantities as $size => $qty) {
                    $balanceData[$key]['sizes'][$size]['available'] = $qty;
                    $balanceData[$key]['total_available'] += $qty;
                }
            } elseif (!$pc->print_embroidery && $pc->sublimation_print) {
                // Only sublimation_print is true - from SublimationPrintReceive
                $receiveQuantities = SublimationPrintReceive::where('product_combination_id', $pc->id)
                    ->get()
                    ->flatMap(fn($item) => $item->receive_quantities)
                    ->groupBy(fn($value, $key) => strtolower(Size::find($key)->name))
                    ->map(fn($group) => $group->sum())
                    ->toArray();

                foreach ($receiveQuantities as $size => $qty) {
                    $balanceData[$key]['sizes'][$size]['available'] = $qty;
                    $balanceData[$key]['total_available'] += $qty;
                }
            } elseif ($pc->print_embroidery && $pc->sublimation_print) {
                // Both are true - from PrintReceiveData
                $receiveQuantities = PrintReceiveData::where('product_combination_id', $pc->id)
                    ->get()
                    ->flatMap(fn($item) => $item->receive_quantities)
                    ->groupBy(fn($value, $key) => strtolower(Size::find($key)->name))
                    ->map(fn($group) => $group->sum())
                    ->toArray();

                foreach ($receiveQuantities as $size => $qty) {
                    $balanceData[$key]['sizes'][$size]['available'] = $qty;
                    $balanceData[$key]['total_available'] += $qty;
                }
            } else {
                // Both are false - from CuttingData
                $cutQuantities = CuttingData::where('product_combination_id', $pc->id)
                    ->get()
                    ->flatMap(fn($item) => $item->cut_quantities)
                    ->groupBy(fn($value, $key) => strtolower($key))
                    ->map(fn($group) => $group->sum())
                    ->toArray();

                foreach ($cutQuantities as $size => $qty) {
                    $balanceData[$key]['sizes'][$size]['available'] = $qty;
                    $balanceData[$key]['total_available'] += $qty;
                }
            }

            // Get input quantities
            $inputQuantities = LineInputData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->input_quantities)
                ->groupBy(fn($value, $key) => strtolower(Size::find($key)->name))
                ->map(fn($group) => $group->sum())
                ->toArray();

            foreach ($inputQuantities as $size => $qty) {
                $balanceData[$key]['sizes'][$size]['input'] = $qty;
                $balanceData[$key]['total_input'] += $qty;
            }

            // Get waste quantities
            $wasteQuantities = LineInputData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->input_waste_quantities ?? [])
                ->groupBy(fn($value, $key) => strtolower(Size::find($key)->name))
                ->map(fn($group) => $group->sum())
                ->toArray();

            foreach ($wasteQuantities as $size => $qty) {
                $balanceData[$key]['sizes'][$size]['waste'] = $qty;
                $balanceData[$key]['total_waste'] += $qty;
            }

            // Calculate balance
            foreach ($balanceData[$key]['sizes'] as $size => &$data) {
                $data['balance'] = $data['available'] - $data['input'] - $data['waste'];
            }
            unset($data);

            $balanceData[$key]['total_balance'] = $balanceData[$key]['total_available'] - $balanceData[$key]['total_input'] - $balanceData[$key]['total_waste'];
        }

        return view('backend.library.line_input_data.reports.input_balance', [
            'balanceData' => array_values($balanceData),
            'allSizes' => $allSizes
        ]);
    }

    public function getMaxInputQuantities(ProductCombination $pc)
    {
        $maxQuantities = [];
        $allSizes = Size::where('is_active', 1)->get();

        // Determine source based on product combination type
        if ($pc->print_embroidery && !$pc->sublimation_print) {
            // Only print_embroidery is true - from PrintReceiveData
            $sourceQuantities = PrintReceiveData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->receive_quantities)
                ->groupBy(fn($value, $key) => strtolower(Size::find($key)->name))
                ->map(fn($group) => $group->sum())
                ->toArray();
        } elseif (!$pc->print_embroidery && $pc->sublimation_print) {
            // Only sublimation_print is true - from SublimationPrintReceive
            $sourceQuantities = SublimationPrintReceive::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->receive_quantities)
                ->groupBy(fn($value, $key) => strtolower(Size::find($key)->name))
                ->map(fn($group) => $group->sum())
                ->toArray();
        } elseif ($pc->print_embroidery && $pc->sublimation_print) {
            // Both are true - from PrintReceiveData
            $sourceQuantities = PrintReceiveData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->receive_quantities)
                ->groupBy(fn($value, $key) => strtolower(Size::find($key)->name))
                ->map(fn($group) => $group->sum())
                ->toArray();
        } else {
            // Both are false - from CuttingData
            $sourceQuantities = CuttingData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->cut_quantities)
                ->groupBy(fn($value, $key) => strtolower($key))
                ->map(fn($group) => $group->sum())
                ->toArray();
        }

        $inputQuantities = LineInputData::where('product_combination_id', $pc->id)
            ->get()
            ->flatMap(fn($item) => $item->input_quantities)
            ->groupBy(fn($value, $key) => strtolower(Size::find($key)->name))
            ->map(fn($group) => $group->sum())
            ->toArray();

        foreach ($allSizes as $size) {
            $sizeName = strtolower($size->name);
            $source = $sourceQuantities[$sizeName] ?? 0;
            $input = $inputQuantities[$sizeName] ?? 0;
            $maxQuantities[$sizeName] = max(0, $source - $input);
        }

        return $maxQuantities;
    }

    public function getAvailableQuantities(ProductCombination $productCombination)
    {
        $maxQuantities = $this->getMaxInputQuantities($productCombination);
        $sizes = Size::where('is_active', 1)->get();

        return response()->json([
            'availableQuantities' => $maxQuantities,
            'sizes' => $sizes
        ]);
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
            // Get data for the selected PO number based on product combination type
            $productCombinations = ProductCombination::where(function ($query) use ($poNumber) {
                $query->whereHas('cuttingData', function ($q) use ($poNumber) {
                    $q->where('po_number', 'like', '%' . $poNumber . '%');
                })->orWhereHas('printReceives', function ($q) use ($poNumber) {
                    $q->where('po_number', 'like', '%' . $poNumber . '%');
                })->orWhereHas('sublimationPrintReceives', function ($q) use ($poNumber) {
                    $q->where('po_number', 'like', '%' . $poNumber . '%');
                });
            })
                ->with('style', 'color')
                ->get();

            foreach ($productCombinations as $pc) {
                if (!$pc) {
                    continue;
                }

                // Create a unique key for this combination
                $combinationKey = $pc->id . '-' . $pc->style->name . '-' . $pc->color->name;

                // Skip if we've already processed this combination
                if (in_array($combinationKey, $processedCombinations)) {
                    continue;
                }

                // Mark this combination as processed
                $processedCombinations[] = $combinationKey;

                $availableQuantities = $this->getMaxInputQuantities($pc);

                $result[$poNumber][] = [
                    'combination_id' => $pc->id,
                    'style' => $pc->style->name,
                    'color' => $pc->color->name,
                    'available_quantities' => $availableQuantities,
                    'size_ids' => $pc->sizes->pluck('id')->toArray()
                ];
            }
        }

        return response()->json($result);
    }

    private function getAvailablePoNumbers()
    {
        $poNumbers = [];

        // Get PO numbers from CuttingData
        $cuttingPoNumbers = CuttingData::distinct()->pluck('po_number')->filter()->values();
        $poNumbers = array_merge($poNumbers, $cuttingPoNumbers->toArray());

        // Get PO numbers from PrintReceiveData
        $printPoNumbers = PrintReceiveData::distinct()->pluck('po_number')->filter()->values();
        $poNumbers = array_merge($poNumbers, $printPoNumbers->toArray());

        // Get PO numbers from SublimationPrintReceive
        $sublimationPoNumbers = SublimationPrintReceive::distinct()->pluck('po_number')->filter()->values();
        $poNumbers = array_merge($poNumbers, $sublimationPoNumbers->toArray());

        return array_unique($poNumbers);
    }
    // public function index(Request $request)
    // {
    //     $query = LineInputData::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

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

    //     $lineInputData = $query->orderBy('date', 'desc')->paginate(10);
    //     $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();

    //     return view('backend.library.line_input_data.index', compact('lineInputData', 'allSizes'));
    // }

    // public function create()
    // {
    //     // Show only combinations that are ready for input
    //     $productCombinations = ProductCombination::whereHas('cuttingData')
    //         ->with('buyer', 'style', 'color')
    //         ->get();

    //     $sizes = Size::where('is_active', 1)->get();

    //     return view('backend.library.line_input_data.create', compact('productCombinations', 'sizes'));
    // }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'date' => 'required|date',
    //         'product_combination_id' => 'required|exists:product_combinations,id',
    //         'quantities.*' => 'nullable|integer|min:0',
    //     ]);

    //     $productCombination = ProductCombination::findOrFail($request->product_combination_id);
    //     $maxQuantities = $this->getMaxInputQuantities($productCombination);

    //     $inputQuantities = [];
    //     $totalInputQuantity = 0;
    //     $errors = [];

    //     foreach ($request->input('quantities', []) as $sizeId => $quantity) {
    //         $size = Size::where('is_active', 1)->find($sizeId);
    //         if ($size && $quantity > 0) {
    //             $sizeName = strtolower($size->name);
    //             $maxAllowed = $maxQuantities[$sizeName] ?? 0;

    //             if ($quantity > $maxAllowed) {
    //                 $errors["quantities.$sizeId"] = "Quantity for {$size->name} exceeds available limit ($maxAllowed)";
    //                 continue;
    //             }

    //             $inputQuantities[$size->name] = (int)$quantity;
    //             $totalInputQuantity += (int)$quantity;
    //         }
    //     }

    //     if (!empty($errors)) {
    //         return redirect()->back()->withErrors($errors)->withInput();
    //     }

    //     LineInputData::create([
    //         'date' => $request->date,
    //         'product_combination_id' => $request->product_combination_id,
    //         'input_quantities' => $inputQuantities,
    //         'total_input_quantity' => $totalInputQuantity,
    //     ]);

    //     return redirect()->route('line_input_data.index')->with('success', 'Line input data added successfully.');
    // }

   

    // public function show(LineInputData $lineInputDatum)
    // {
    //     return view('backend.library.line_input_data.show', compact('lineInputDatum'));
    // }

    // public function edit(LineInputData $lineInputDatum)
    // {
    //     $lineInputDatum->load('productCombination.style', 'productCombination.color');
    //     $sizes = Size::where('is_active', 1)->get();
    //     $maxQuantities = $this->getMaxInputQuantities($lineInputDatum->productCombination);

    //     $sizeData = $sizes->map(function ($size) use ($lineInputDatum, $maxQuantities) {
    //         $sizeName = strtolower($size->name);
    //         return [
    //             'id' => $size->id,
    //             'name' => $size->name,
    //             'max_allowed' => $maxQuantities[$sizeName] ?? 0,
    //             'current_quantity' => $lineInputDatum->input_quantities[$size->name] ?? 0
    //         ];
    //     });

    //     return view('backend.library.line_input_data.edit', [
    //         'lineInputDatum' => $lineInputDatum,
    //         'sizeData' => $sizeData
    //     ]);
    // }

    // public function update(Request $request, LineInputData $lineInputDatum)
    // {
    //     $request->validate([
    //         'date' => 'required|date',
    //         'quantities.*' => 'nullable|integer|min:0',
    //     ]);

    //     $productCombination = $lineInputDatum->productCombination;
    //     $maxQuantities = $this->getMaxInputQuantities($productCombination);

    //     $inputQuantities = [];
    //     $totalInputQuantity = 0;
    //     $errors = [];

    //     foreach ($request->input('quantities', []) as $sizeId => $quantity) {
    //         $size = Size::where('is_active', 1)->find($sizeId);
    //         if ($size && $quantity > 0) {
    //             $sizeName = strtolower($size->name);
    //             $maxAllowed = ($maxQuantities[$sizeName] ?? 0) + ($lineInputDatum->input_quantities[$size->name] ?? 0);

    //             if ($quantity > $maxAllowed) {
    //                 $errors["quantities.$sizeId"] = "Quantity for {$size->name} exceeds available limit ($maxAllowed)";
    //                 continue;
    //             }

    //             $inputQuantities[$size->name] = (int)$quantity;
    //             $totalInputQuantity += (int)$quantity;
    //         }
    //     }

    //     if (!empty($errors)) {
    //         return redirect()->back()->withErrors($errors)->withInput();
    //     }

    //     $lineInputDatum->update([
    //         'date' => $request->date,
    //         'input_quantities' => $inputQuantities,
    //         'total_input_quantity' => $totalInputQuantity,
    //     ]);

    //     return redirect()->route('line_input_data.index')->with('success', 'Line input data updated successfully.');
    // }

    // public function destroy(LineInputData $lineInputDatum)
    // {
    //     $lineInputDatum->delete();
    //     return redirect()->route('line_input_data.index')->with('success', 'Line input data deleted successfully.');
    // }

    // // Reports
    // public function totalInputReport(Request $request)
    // {
    //     $query = LineInputData::with('productCombination.style', 'productCombination.color');

    //     if ($request->filled('start_date') && $request->filled('end_date')) {
    //         $query->whereBetween('date', [$request->start_date, $request->end_date]);
    //     }

    //     $lineInputData = $query->get();
    //     $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
    //     $reportData = [];

    //     foreach ($lineInputData as $data) {
    //         $style = $data->productCombination->style->name;
    //         $color = $data->productCombination->color->name;
    //         $key = $style . '-' . $color;

    //         if (!isset($reportData[$key])) {
    //             $reportData[$key] = [
    //                 'style' => $style,
    //                 'color' => $color,
    //                 'sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
    //                 'total' => 0
    //             ];
    //         }

    //         foreach ($data->input_quantities as $size => $qty) {
    //             $normalized = strtolower($size);
    //             if (array_key_exists($normalized, $reportData[$key]['sizes'])) {
    //                 $reportData[$key]['sizes'][$normalized] += $qty;
    //             }
    //         }
    //         $reportData[$key]['total'] += $data->total_input_quantity;
    //     }

    //     return view('backend.library.line_input_data.reports.total_input', [
    //         'reportData' => array_values($reportData),
    //         'allSizes' => $allSizes
    //     ]);
    // }

    // public function inputBalanceReport(Request $request)
    // {
    //     $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
    //     $balanceData = [];

    //     $productCombinations = ProductCombination::whereHas('cuttingData')
    //         ->with('style', 'color')
    //         ->get();

    //     foreach ($productCombinations as $pc) {
    //         $style = $pc->style->name;
    //         $color = $pc->color->name;
    //         $key = $style . '-' . $color;

    //         $balanceData[$key] = [
    //             'style' => $style,
    //             'color' => $color,
    //             'sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), ['available' => 0, 'input' => 0, 'balance' => 0]),
    //             'total_available' => 0,
    //             'total_input' => 0,
    //             'total_balance' => 0,
    //         ];

    //         // Get available quantities (cut or received print)
    //         if (!$pc->print_embroidery) {
    //             $cutQuantities = CuttingData::where('product_combination_id', $pc->id)
    //                 ->get()
    //                 ->flatMap(fn($item) => $item->cut_quantities)
    //                 ->groupBy(fn($value, $key) => strtolower($key))
    //                 ->map(fn($group) => $group->sum())
    //                 ->toArray();

    //             foreach ($cutQuantities as $size => $qty) {
    //                 $balanceData[$key]['sizes'][$size]['available'] = $qty;
    //                 $balanceData[$key]['total_available'] += $qty;
    //             }
    //         } else {
    //             $receiveQuantities = PrintReceiveData::where('product_combination_id', $pc->id)
    //                 ->get()
    //                 ->flatMap(fn($item) => $item->receive_quantities)
    //                 ->groupBy(fn($value, $key) => strtolower($key))
    //                 ->map(fn($group) => $group->sum())
    //                 ->toArray();

    //             foreach ($receiveQuantities as $size => $qty) {
    //                 $balanceData[$key]['sizes'][$size]['available'] = $qty;
    //                 $balanceData[$key]['total_available'] += $qty;
    //             }
    //         }

    //         // Get input quantities
    //         $inputQuantities = LineInputData::where('product_combination_id', $pc->id)
    //             ->get()
    //             ->flatMap(fn($item) => $item->input_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         foreach ($inputQuantities as $size => $qty) {
    //             $balanceData[$key]['sizes'][$size]['input'] = $qty;
    //             $balanceData[$key]['total_input'] += $qty;
    //         }

    //         // Calculate balance
    //         foreach ($balanceData[$key]['sizes'] as $size => &$data) {
    //             $data['balance'] = $data['available'] - $data['input'];
    //         }
    //         unset($data);

    //         $balanceData[$key]['total_balance'] = $balanceData[$key]['total_available'] - $balanceData[$key]['total_input'];
    //     }

    //     return view('backend.library.line_input_data.reports.input_balance', [
    //         'balanceData' => array_values($balanceData),
    //         'allSizes' => $allSizes
    //     ]);
    // }

    // public function getMaxInputQuantities(ProductCombination $pc)
    // {
    //     $maxQuantities = [];
    //     $allSizes = Size::where('is_active', 1)->get();

    //     // For non-print/embroidery: max = cut quantities
    //     if (!$pc->print_embroidery) {
    //         $cutQuantities = CuttingData::where('product_combination_id', $pc->id)
    //             ->get()
    //             ->flatMap(fn($item) => $item->cut_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         $inputQuantities = LineInputData::where('product_combination_id', $pc->id)
    //             ->get()
    //             ->flatMap(fn($item) => $item->input_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         foreach ($allSizes as $size) {
    //             $sizeName = strtolower($size->name);
    //             $cut = $cutQuantities[$sizeName] ?? 0;
    //             $input = $inputQuantities[$sizeName] ?? 0;
    //             $maxQuantities[$sizeName] = max(0, $cut - $input);
    //         }
    //     }
    //     // For print/embroidery: max = received print quantities
    //     else {
    //         $receiveQuantities = PrintReceiveData::where('product_combination_id', $pc->id)
    //             ->get()
    //             ->flatMap(fn($item) => $item->receive_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         $inputQuantities = LineInputData::where('product_combination_id', $pc->id)
    //             ->get()
    //             ->flatMap(fn($item) => $item->input_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         foreach ($allSizes as $size) {
    //             $sizeName = strtolower($size->name);
    //             $received = $receiveQuantities[$sizeName] ?? 0;
    //             $input = $inputQuantities[$sizeName] ?? 0;
    //             $maxQuantities[$sizeName] = max(0, $received - $input);
    //         }
    //     }

    //     return $maxQuantities;
    // }
    // public function getAvailableQuantities(ProductCombination $productCombination)
    // {
    //     $maxQuantities = $this->getMaxInputQuantities($productCombination);
    //     $sizes = Size::where('is_active', 1)->get();

    //     return response()->json([
    //         'availableQuantities' => $maxQuantities,
    //         'sizes' => $sizes
    //     ]);
    // }
}
