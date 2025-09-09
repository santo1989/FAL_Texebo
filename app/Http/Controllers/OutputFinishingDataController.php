<?php

namespace App\Http\Controllers;

use App\Models\LineInputData;
use App\Models\OrderData;
use App\Models\OutputFinishingData;
use App\Models\ProductCombination;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OutputFinishingDataController extends Controller
{
    public function index(Request $request)
    {
        $query = OutputFinishingData::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

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

        $outputFinishingData = $query->orderBy('date', 'desc')->paginate(10);
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        return view('backend.library.output_finishing_data.index', compact('outputFinishingData', 'allSizes'));
    }

    public function create()
    {
        // Get distinct PO numbers based on product combination type
        $distinctPoNumbers = $this->getAvailablePoNumbers();
        $sizes = Size::where('is_active', 1)->get();

        return view('backend.library.output_finishing_data.create', compact('distinctPoNumbers', 'sizes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'po_number' => 'required|array',
            'po_number.*' => 'required|string',
            'rows' => 'required|array',
            'rows.*.product_combination_id' => 'required|exists:product_combinations,id',
            'rows.*.output_quantities.*' => 'nullable|integer|min:0',
            'rows.*.output_waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->rows as $row) {
                $outputQuantities = [];
                $wasteQuantities = [];
                $totalOutputQuantity = 0;
                $totalWasteQuantity = 0;

                // Process output quantities
                foreach ($row['output_quantities'] as $sizeId => $quantity) {
                    if ($quantity !== null && (int)$quantity > 0) {
                        $size = Size::find($sizeId);
                        if ($size) {
                            $outputQuantities[$size->id] = (int)$quantity;
                            $totalOutputQuantity += (int)$quantity;
                        }
                    }
                }

                // Process waste quantities
                foreach ($row['output_waste_quantities'] as $sizeId => $quantity) {
                    if ($quantity !== null && (int)$quantity > 0) {
                        $size = Size::find($sizeId);
                        if ($size) {
                            $wasteQuantities[$size->id] = (int)$quantity;
                            $totalWasteQuantity += (int)$quantity;
                        }
                    }
                }

                // Only create a record if there's at least one valid output or waste quantity
                if (!empty($outputQuantities) || !empty($wasteQuantities)) {
                    OutputFinishingData::create([
                        'date' => $request->date,
                        'product_combination_id' => $row['product_combination_id'],
                        'po_number' => implode(',', $request->po_number),
                        'output_quantities' => $outputQuantities,
                        'total_output_quantity' => $totalOutputQuantity,
                        'output_waste_quantities' => $wasteQuantities,
                        'total_output_waste_quantity' => $totalWasteQuantity,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('output_finishing_data.index')
                ->with('success', 'Output finishing data added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(OutputFinishingData $outputFinishingDatum)
    {
        $outputFinishingDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Only valid sizes
        $validSizes = $allSizes->filter(function ($size) use ($outputFinishingDatum) {
            return isset($outputFinishingDatum->output_quantities[$size->id]) ||
                isset($outputFinishingDatum->output_waste_quantities[$size->id]);
        });

        $allSizes = $validSizes->values();

        return view('backend.library.output_finishing_data.show', compact('outputFinishingDatum', 'allSizes'));
    }

    public function edit(OutputFinishingData $outputFinishingDatum)
    {
        $outputFinishingDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Only valid sizes
        $validSizes = $allSizes->filter(function ($size) use ($outputFinishingDatum) {
            return isset($outputFinishingDatum->output_quantities[$size->id]) ||
                isset($outputFinishingDatum->output_waste_quantities[$size->id]);
        });

        $allSizes = $validSizes->values();

        // Get max available quantities for this product combination
        $maxQuantities = $this->getMaxOutputQuantities($outputFinishingDatum->productCombination);

        // Get order quantities from order_data table
        $poNumbers = explode(',', $outputFinishingDatum->po_number);
        $orderQuantities = [];

        foreach ($poNumbers as $poNumber) {
            $orderData = OrderData::where('product_combination_id', $outputFinishingDatum->product_combination_id)
                ->where('po_number', $poNumber)
                ->first();

            if ($orderData && $orderData->order_quantities) {
                foreach ($orderData->order_quantities as $sizeId => $qty) {
                    $orderQuantities[$sizeId] = ($orderQuantities[$sizeId] ?? 0) + $qty;
                }
            }
        }

        // Prepare size data with max available quantities and order quantities
        $sizeData = [];
        foreach ($allSizes as $size) {
            $outputQty = $outputFinishingDatum->output_quantities[$size->id] ?? 0;
            $wasteQty = $outputFinishingDatum->output_waste_quantities[$size->id] ?? 0;
            $maxAvailable = $maxQuantities[$size->id] ?? 0;
            $orderQty = $orderQuantities[$size->id] ?? 0;

            // Calculate the maximum allowed (available + current output)
            $maxAllowed = $maxAvailable + $outputQty;

            $sizeData[] = [
                'id' => $size->id,
                'name' => $size->name,
                'output_quantity' => $outputQty,
                'waste_quantity' => $wasteQty,
                'max_available' => $maxAvailable,
                'max_allowed' => $maxAllowed,
                'order_quantity' => $orderQty,
            ];
        }

        return view('backend.library.output_finishing_data.edit', compact('outputFinishingDatum', 'sizeData'));
    }

    public function update(Request $request, OutputFinishingData $outputFinishingDatum)
    {
        $request->validate([
            'date' => 'required|date',
            'output_quantities.*' => 'nullable|integer|min:0',
            'output_waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        try {
            $outputQuantities = [];
            $wasteQuantities = [];
            $totalOutputQuantity = 0;
            $totalWasteQuantity = 0;

            // Process output quantities
            foreach ($request->output_quantities as $sizeId => $quantity) {
                if ($quantity !== null && (int)$quantity > 0) {
                    $outputQuantities[$sizeId] = (int)$quantity;
                    $totalOutputQuantity += (int)$quantity;
                }
            }

            // Process waste quantities
            foreach ($request->output_waste_quantities as $sizeId => $quantity) {
                if ($quantity !== null && (int)$quantity > 0) {
                    $wasteQuantities[$sizeId] = (int)$quantity;
                    $totalWasteQuantity += (int)$quantity;
                }
            }

            $outputFinishingDatum->update([
                'date' => $request->date,
                'output_quantities' => $outputQuantities,
                'total_output_quantity' => $totalOutputQuantity,
                'output_waste_quantities' => $wasteQuantities,
                'total_output_waste_quantity' => $totalWasteQuantity,
            ]);

            return redirect()->route('output_finishing_data.index')
                ->with('success', 'Output finishing data updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(OutputFinishingData $outputFinishingDatum)
    {
        $outputFinishingDatum->delete();
        return redirect()->route('output_finishing_data.index')->with('success', 'Output finishing data deleted successfully.');
    }

    // Report: Total Balance Report

    public function getMaxOutputQuantities(ProductCombination $pc)
    {
        $maxQuantities = [];
        $allSizes = Size::where('is_active', 1)->get();

        // Get total input quantities from LineInputData
        $inputQuantities = LineInputData::where('product_combination_id', $pc->id)
            ->get()
            ->flatMap(function ($item) {
                return $item->input_quantities;
            })
            ->groupBy(function ($value, $key) {
                return $key; // Use size ID as key
            })
            ->map(function ($group) {
                return $group->sum();
            })
            ->toArray();

        // Get total output quantities
        $outputQuantities = OutputFinishingData::where('product_combination_id', $pc->id)
            ->get()
            ->flatMap(function ($item) {
                return $item->output_quantities;
            })
            ->groupBy(function ($value, $key) {
                return $key; // Use size ID as key
            })
            ->map(function ($group) {
                return $group->sum();
            })
            ->toArray();

        foreach ($allSizes as $size) {
            $input = $inputQuantities[$size->id] ?? 0;
            $output = $outputQuantities[$size->id] ?? 0;
            $maxQuantities[$size->id] = max(0, $input - $output);
        }

        return $maxQuantities;
    }

    // public function find(Request $request)
    // {
    //     $poNumbers = $request->input('po_numbers', []);

    //     if (empty($poNumbers)) {
    //         return response()->json([]);
    //     }

    //     $result = [];
    //     $processedCombinations = [];

    //     foreach ($poNumbers as $poNumber) {
    //         // Get data for the selected PO number from LineInputData
    //         $productCombinations = ProductCombination::whereHas('lineInputData', function ($query) use ($poNumber) {
    //             $query->where('po_number', 'like', '%' . $poNumber . '%');
    //         })
    //             ->with('style', 'color', 'size')
    //             ->get();

    //         foreach ($productCombinations as $pc) {
    //             // Skip if product combination doesn't have style or color
    //             if (!$pc->style || !$pc->color) {
    //                 continue;
    //             }

    //             // Create a unique key for this combination
    //             $combinationKey = $pc->id . '-' . $pc->style->name . '-' . $pc->color->name;

    //             // Skip if we've already processed this combination
    //             if (in_array($combinationKey, $processedCombinations)) {
    //                 continue;
    //             }

    //             // Mark this combination as processed
    //             $processedCombinations[] = $combinationKey;

    //             $availableQuantities = $this->getMaxOutputQuantities($pc);

    //             $result[$poNumber][] = [
    //                 'combination_id' => $pc->id,
    //                 'style' => $pc->style->name,
    //                 'color' => $pc->color->name,
    //                 'available_quantities' => $availableQuantities,
    //                 'size_ids' => $pc->sizes->pluck('id')->toArray()
    //             ];
    //         }
    //     }

    //     return response()->json($result);
    // }



    public function find(Request $request)
    {
        $poNumbers = $request->input('po_numbers', []);

        if (empty($poNumbers)) {
            return response()->json([]);
        }

        $result = [];
        $processedCombinations = [];

        foreach ($poNumbers as $poNumber) {
            // Get product combinations with LineInputData for the PO
            $productCombinations = ProductCombination::whereHas('lineInputData', function ($query) use ($poNumber) {
                $query->where('po_number', 'like', '%' . $poNumber . '%');
            })
                ->with(['style', 'color', 'size', 'lineInputData' => function ($query) use ($poNumber) {
                    $query->where('po_number', 'like', '%' . $poNumber . '%');
                }])
                ->get();

            foreach ($productCombinations as $pc) {
                if (!$pc->style || !$pc->color) continue;

                $combinationKey = $pc->id . '-' . $pc->style->name . '-' . $pc->color->name;
                if (in_array($combinationKey, $processedCombinations)) continue;

                $processedCombinations[] = $combinationKey;

                // Calculate total input quantities from LineInputData
                $inputQuantities = [];
                foreach ($pc->lineInputData as $input) {
                    foreach ($input->input_quantities as $sizeId => $qty) {
                        $inputQuantities[$sizeId] = ($inputQuantities[$sizeId] ?? 0) + $qty;
                    }
                }

                // Subtract already outputted quantities from OutputFinishingData
                $outputQuantities = [];
                OutputFinishingData::where('product_combination_id', $pc->id)
                    ->where('po_number', 'like', '%' . $poNumber . '%')
                    ->get()
                    ->each(function ($item) use (&$outputQuantities) {
                        foreach ($item->output_quantities as $sizeId => $qty) {
                            $outputQuantities[$sizeId] = ($outputQuantities[$sizeId] ?? 0) + $qty;
                        }
                    });

                $availableQuantities = [];
                foreach ($inputQuantities as $sizeId => $qty) {
                    $availableQuantities[$sizeId] = $qty - ($outputQuantities[$sizeId] ?? 0);
                }

                $result[$poNumber][] = [
                    'combination_id' => $pc->id,
                    'style' => $pc->style->name,
                    'color' => $pc->color->name,
                    'available_quantities' => $availableQuantities,
                    'size_ids' => array_keys($availableQuantities)
                ];
            }
        }

        return response()->json($result);
    }
    
    private function getAvailablePoNumbers()
    {
        $poNumbers = [];

        // Get PO numbers from LineInputData
        $lineInputPoNumbers = LineInputData::distinct()->pluck('po_number')->filter()->values();
        $poNumbers = array_merge($poNumbers, $lineInputPoNumbers->toArray());

        return array_unique($poNumbers);
    }

    // Report: Total Balance Report
    public function totalBalanceReport(Request $request)
    {
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $reportData = [];

        $productCombinations = ProductCombination::whereHas('lineInputData')
            ->with('style', 'color')
            ->get();

        foreach ($productCombinations as $pc) {
            $style = $pc->style->name;
            $color = $pc->color->name;
            $key = $style . '-' . $color;

            if (!isset($reportData[$key])) {
                $reportData[$key] = [
                    'style' => $style,
                    'color' => $color,
                    'sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
                    'total' => 0
                ];
            }

            // Get total input quantities
            $inputQuantities = LineInputData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->input_quantities)
                ->groupBy(fn($value, $key) => $key) // Use size ID as key
                ->map(fn($group) => $group->sum())
                ->toArray();

            // Get total output quantities
            $outputQuantities = OutputFinishingData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->output_quantities)
                ->groupBy(fn($value, $key) => $key) // Use size ID as key
                ->map(fn($group) => $group->sum())
                ->toArray();

            foreach ($allSizes as $size) {
                $input = $inputQuantities[$size->id] ?? 0;
                $output = $outputQuantities[$size->id] ?? 0;
                $balance = max(0, $input - $output);

                $reportData[$key]['sizes'][$size->id] = $balance;
                $reportData[$key]['total'] += $balance;
            }
        }

        return view('backend.library.output_finishing_data.reports.total_balance', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes
        ]);
    }

    public function sewingWipReport(Request $request)
    {
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $wipData = [];

        $productCombinations = ProductCombination::whereHas('lineInputData')
            ->with('style', 'color')
            ->get();

        foreach ($productCombinations as $pc) {
            $style = $pc->style->name;
            $color = $pc->color->name;
            $key = $style . '-' . $color;

            // Initialize with size IDs as keys
            $wipData[$key] = [
                'style' => $style,
                'color' => $color,
                'sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
                'total' => 0
            ];

            // Get input quantities (using size IDs as keys)
            $inputQuantities = LineInputData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->input_quantities)
                ->groupBy(fn($value, $key) => $key) // Use size ID as key
                ->map(fn($group) => $group->sum())
                ->toArray();

            // Get output quantities (using size IDs as keys)
            $outputQuantities = OutputFinishingData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->output_quantities)
                ->groupBy(fn($value, $key) => $key) // Use size ID as key
                ->map(fn($group) => $group->sum())
                ->toArray();

            foreach ($allSizes as $size) {
                $input = $inputQuantities[$size->id] ?? 0;
                $output = $outputQuantities[$size->id] ?? 0;
                $wip = max(0, $input - $output);

                $wipData[$key]['sizes'][$size->id] = $wip;
                $wipData[$key]['total'] += $wip;
            }
        }

        return view('backend.library.output_finishing_data.reports.sewing_wip', [
            'wipData' => array_values($wipData),
            'allSizes' => $allSizes
        ]);
    }

    // public function index(Request $request)
    // {
    //     $query = OutputFinishingData::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

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

    //     $outputFinishingData = $query->orderBy('date', 'desc')->paginate(10);
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //     return view('backend.library.output_finishing_data.index', compact('outputFinishingData', 'allSizes'));
    // }

    // public function create()
    // {
    //     $productCombinations = ProductCombination::whereHas('lineInputData')
    //         ->with('buyer', 'style', 'color')
    //         ->get();

    //     $sizes = Size::where('is_active', 1)->get();

    //     return view('backend.library.output_finishing_data.create', compact('productCombinations', 'sizes'));
    // }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'date' => 'required|date',
    //         'product_combination_id' => 'required|exists:product_combinations,id',
    //         'quantities.*' => 'nullable|integer|min:0',
    //     ]);

    //     $productCombination = ProductCombination::findOrFail($request->product_combination_id);
    //     $maxQuantities = $this->getMaxOutputQuantities($productCombination);

    //     $outputQuantities = [];
    //     $totalOutputQuantity = 0;
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

    //             $outputQuantities[$size->name] = (int)$quantity;
    //             $totalOutputQuantity += (int)$quantity;
    //         }
    //     }

    //     if (!empty($errors)) {
    //         return redirect()->back()->withErrors($errors)->withInput();
    //     }

    //     OutputFinishingData::create([
    //         'date' => $request->date,
    //         'product_combination_id' => $request->product_combination_id,
    //         'output_quantities' => $outputQuantities,
    //         'total_output_quantity' => $totalOutputQuantity,
    //     ]);

    //     return redirect()->route('output_finishing_data.index')->with('success', 'Output finishing data added successfully.');
    // }

    // public function maxQuantities($id)
    // {
    //     $productCombination = ProductCombination::findOrFail($id);
    //     $maxQuantities = $this->getMaxOutputQuantities($productCombination);
    //     $sizes = Size::where('is_active', 1)->get();

    //     return response()->json([
    //         'maxQuantities' => $maxQuantities,
    //         'sizes' => $sizes->map(function ($size) {
    //             return [
    //                 'id' => $size->id,
    //                 'name' => $size->name
    //             ];
    //         })
    //     ]);
    // }

    // public function getMaxOutputQuantities(ProductCombination $productCombination)
    // {
    //     $maxQuantities = [];
    //     $allSizes = Size::where('is_active', 1)->get();

    //     // Get total input quantities from LineInputData
    //     $inputQuantities = LineInputData::where('product_combination_id', $productCombination->id)
    //         ->get()
    //         ->flatMap(function ($item) {
    //             return collect($item->input_quantities)->mapWithKeys(function ($value, $key) {
    //                 return [strtolower($key) => $value];
    //             });
    //         })
    //         ->groupBy(function ($value, $key) {
    //             return $key;
    //         })
    //         ->map(function ($group) {
    //             return $group->sum();
    //         })
    //         ->toArray();

    //     // Get total output quantities
    //     $outputQuantities = OutputFinishingData::where('product_combination_id', $productCombination->id)
    //         ->get()
    //         ->flatMap(function ($item) {
    //             return collect($item->output_quantities)->mapWithKeys(function ($value, $key) {
    //                 return [strtolower($key) => $value];
    //             });
    //         })
    //         ->groupBy(function ($value, $key) {
    //             return $key;
    //         })
    //         ->map(function ($group) {
    //             return $group->sum();
    //         })
    //         ->toArray();

    //     foreach ($allSizes as $size) {
    //         $sizeName = strtolower($size->name);
    //         $input = $inputQuantities[$sizeName] ?? 0;
    //         $output = $outputQuantities[$sizeName] ?? 0;
    //         $maxQuantities[$sizeName] = max(0, $input - $output);
    //     }

    //     return $maxQuantities;
    // }

    // public function show(OutputFinishingData $outputFinishingDatum)
    // {
    //     return view('backend.library.output_finishing_data.show', compact('outputFinishingDatum'));
    // }

    // public function edit(OutputFinishingData $outputFinishingDatum)
    // {
    //     $outputFinishingDatum->load('productCombination.style', 'productCombination.color');
    //     $sizes = Size::where('is_active', 1)->get();
    //     $maxQuantities = $this->getMaxOutputQuantities($outputFinishingDatum->productCombination);

    //     // Add back current output quantities to available
    //     foreach ($outputFinishingDatum->output_quantities as $size => $quantity) {
    //         $sizeName = strtolower($size);
    //         if (isset($maxQuantities[$sizeName])) {
    //             $maxQuantities[$sizeName] += $quantity;
    //         }
    //     }

    //     $sizeData = $sizes->map(function ($size) use ($outputFinishingDatum, $maxQuantities) {
    //         $sizeName = strtolower($size->name);
    //         return [
    //             'id' => $size->id,
    //             'name' => $size->name,
    //             'max_allowed' => $maxQuantities[$sizeName] ?? 0,
    //             'current_quantity' => $outputFinishingDatum->output_quantities[$size->name] ?? 0
    //         ];
    //     });

    //     return view('backend.library.output_finishing_data.edit', [
    //         'outputFinishingDatum' => $outputFinishingDatum,
    //         'sizeData' => $sizeData
    //     ]);
    // }

    // public function update(Request $request, OutputFinishingData $outputFinishingDatum)
    // {
    //     $request->validate([
    //         'date' => 'required|date',
    //         'quantities.*' => 'nullable|integer|min:0',
    //     ]);

    //     $productCombination = $outputFinishingDatum->productCombination;
    //     $maxQuantities = $this->getMaxOutputQuantities($productCombination);

    //     // Add back current output quantities to available
    //     foreach ($outputFinishingDatum->output_quantities as $size => $quantity) {
    //         $sizeName = strtolower($size);
    //         if (isset($maxQuantities[$sizeName])) {
    //             $maxQuantities[$sizeName] += $quantity;
    //         }
    //     }

    //     $outputQuantities = [];
    //     $totalOutputQuantity = 0;
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

    //             $outputQuantities[$size->name] = (int)$quantity;
    //             $totalOutputQuantity += (int)$quantity;
    //         }
    //     }

    //     if (!empty($errors)) {
    //         return redirect()->back()->withErrors($errors)->withInput();
    //     }

    //     $outputFinishingDatum->update([
    //         'date' => $request->date,
    //         'output_quantities' => $outputQuantities,
    //         'total_output_quantity' => $totalOutputQuantity,
    //     ]);

    //     return redirect()->route('output_finishing_data.index')->with('success', 'Output finishing data updated successfully.');
    // }

    // public function destroy(OutputFinishingData $outputFinishingDatum)
    // {
    //     $outputFinishingDatum->delete();
    //     return redirect()->route('output_finishing_data.index')->with('success', 'Output finishing data deleted successfully.');
    // }

    // // Report: Total Balance Report
    // public function totalBalanceReport(Request $request)
    // {
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $reportData = [];

    //     $productCombinations = ProductCombination::whereHas('lineInputData') // Only include PCs that have line input
    //         ->with('style', 'color')
    //         ->get();

    //     foreach ($productCombinations as $pc) {
    //         $style = $pc->style->name;
    //         $color = $pc->color->name;
    //         $key = $style . '-' . $color;

    //         if (!isset($reportData[$key])) {
    //             $reportData[$key] = [
    //                 'style' => $style,
    //                 'color' => $color,
    //                 'sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
    //                 'total' => 0
    //             ];
    //         }

    //         // Get total input quantities
    //         $inputQuantities = LineInputData::where('product_combination_id', $pc->id)
    //             ->get()
    //             ->flatMap(fn($item) => $item->input_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         // Get total output quantities
    //         $outputQuantities = OutputFinishingData::where('product_combination_id', $pc->id)
    //             ->get()
    //             ->flatMap(fn($item) => $item->output_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         foreach ($allSizes as $size) {
    //             $sizeName = strtolower($size->name);
    //             $input = $inputQuantities[$sizeName] ?? 0;
    //             $output = $outputQuantities[$sizeName] ?? 0;
    //             $balance = max(0, $input - $output);

    //             $reportData[$key]['sizes'][$sizeName] = $balance;
    //             $reportData[$key]['total'] += $balance;
    //         }
    //     }

    //     return view('backend.library.output_finishing_data.reports.total_balance', [
    //         'reportData' => array_values($reportData),
    //         'allSizes' => $allSizes
    //     ]);
    // }

    // public function sewingWipReport(Request $request)
    // {
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $wipData = [];

    //     $productCombinations = ProductCombination::whereHas('lineInputData')
    //         ->with('style', 'color')
    //         ->get();

    //     foreach ($productCombinations as $pc) {
    //         $style = $pc->style->name;
    //         $color = $pc->color->name;
    //         $key = $style . '-' . $color;

    //         $wipData[$key] = [
    //             'style' => $style,
    //             'color' => $color,
    //             'sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
    //             'total' => 0
    //         ];

    //         // Get input quantities
    //         $inputQuantities = LineInputData::where('product_combination_id', $pc->id)
    //             ->get()
    //             ->flatMap(fn($item) => $item->input_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         // Get packed quantities
    //         $packedQuantities = OutputFinishingData::where('product_combination_id', $pc->id)
    //             ->get()
    //             ->flatMap(fn($item) => $item->output_quantities)
    //             ->groupBy(fn($value, $key) => strtolower($key))
    //             ->map(fn($group) => $group->sum())
    //             ->toArray();

    //         foreach ($allSizes as $size) {
    //             $sizeName = strtolower($size->name);
    //             $input = $inputQuantities[$sizeName] ?? 0;
    //             $packed = $packedQuantities[$sizeName] ?? 0;
    //             $wip = max(0, $input - $packed);

    //             $wipData[$key]['sizes'][$sizeName] = $wip;
    //             $wipData[$key]['total'] += $wip;
    //         }
    //     }

    //     return view('backend.library.output_finishing_data.reports.sewing_wip', [
    //         'wipData' => array_values($wipData),
    //         'allSizes' => $allSizes
    //     ]);
    // }
}
