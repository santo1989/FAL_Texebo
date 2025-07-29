<?php

namespace App\Http\Controllers;

use App\Models\LineInputData;
use App\Models\OutputFinishingData;
use App\Models\ProductCombination;
use App\Models\Size;
use Illuminate\Http\Request;

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
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();

        return view('backend.library.output_finishing_data.index', compact('outputFinishingData', 'allSizes'));
    }

    public function create()
    {
        $productCombinations = ProductCombination::whereHas('lineInputData')
            ->with('buyer', 'style', 'color')
            ->get();

        $sizes = Size::where('is_active', 1)->get();

        return view('backend.library.output_finishing_data.create', compact('productCombinations', 'sizes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'product_combination_id' => 'required|exists:product_combinations,id',
            'quantities.*' => 'nullable|integer|min:0',
        ]);

        $productCombination = ProductCombination::findOrFail($request->product_combination_id);
        $maxQuantities = $this->getMaxOutputQuantities($productCombination);

        $outputQuantities = [];
        $totalOutputQuantity = 0;
        $errors = [];

        foreach ($request->input('quantities', []) as $sizeId => $quantity) {
            $size = Size::where('is_active', 1)->find($sizeId);
            if ($size && $quantity > 0) {
                $sizeName = strtolower($size->name);
                $maxAllowed = $maxQuantities[$sizeName] ?? 0;

                if ($quantity > $maxAllowed) {
                    $errors["quantities.$sizeId"] = "Quantity for {$size->name} exceeds available limit ($maxAllowed)";
                    continue;
                }

                $outputQuantities[$size->name] = (int)$quantity;
                $totalOutputQuantity += (int)$quantity;
            }
        }

        if (!empty($errors)) {
            return redirect()->back()->withErrors($errors)->withInput();
        }

        OutputFinishingData::create([
            'date' => $request->date,
            'product_combination_id' => $request->product_combination_id,
            'output_quantities' => $outputQuantities,
            'total_output_quantity' => $totalOutputQuantity,
        ]);

        return redirect()->route('output_finishing_data.index')->with('success', 'Output finishing data added successfully.');
    }

    public function maxQuantities($id)
    {
        $productCombination = ProductCombination::findOrFail($id);
        $maxQuantities = $this->getMaxOutputQuantities($productCombination);
        $sizes = Size::where('is_active', 1)->get();

        return response()->json([
            'maxQuantities' => $maxQuantities,
            'sizes' => $sizes->map(function ($size) {
                return [
                    'id' => $size->id,
                    'name' => $size->name
                ];
            })
        ]);
    }

    public function getMaxOutputQuantities(ProductCombination $productCombination)
    {
        $maxQuantities = [];
        $allSizes = Size::where('is_active', 1)->get();

        // Get total input quantities from LineInputData
        $inputQuantities = LineInputData::where('product_combination_id', $productCombination->id)
            ->get()
            ->flatMap(function ($item) {
                return collect($item->input_quantities)->mapWithKeys(function ($value, $key) {
                    return [strtolower($key) => $value];
                });
            })
            ->groupBy(function ($value, $key) {
                return $key;
            })
            ->map(function ($group) {
                return $group->sum();
            })
            ->toArray();

        // Get total output quantities
        $outputQuantities = OutputFinishingData::where('product_combination_id', $productCombination->id)
            ->get()
            ->flatMap(function ($item) {
                return collect($item->output_quantities)->mapWithKeys(function ($value, $key) {
                    return [strtolower($key) => $value];
                });
            })
            ->groupBy(function ($value, $key) {
                return $key;
            })
            ->map(function ($group) {
                return $group->sum();
            })
            ->toArray();

        foreach ($allSizes as $size) {
            $sizeName = strtolower($size->name);
            $input = $inputQuantities[$sizeName] ?? 0;
            $output = $outputQuantities[$sizeName] ?? 0;
            $maxQuantities[$sizeName] = max(0, $input - $output);
        }

        return $maxQuantities;
    }

    public function show(OutputFinishingData $outputFinishingDatum)
    {
        return view('backend.library.output_finishing_data.show', compact('outputFinishingDatum'));
    }

    public function edit(OutputFinishingData $outputFinishingDatum)
    {
        $outputFinishingDatum->load('productCombination.style', 'productCombination.color');
        $sizes = Size::where('is_active', 1)->get();
        $maxQuantities = $this->getMaxOutputQuantities($outputFinishingDatum->productCombination);

        // Add back current output quantities to available
        foreach ($outputFinishingDatum->output_quantities as $size => $quantity) {
            $sizeName = strtolower($size);
            if (isset($maxQuantities[$sizeName])) {
                $maxQuantities[$sizeName] += $quantity;
            }
        }

        $sizeData = $sizes->map(function ($size) use ($outputFinishingDatum, $maxQuantities) {
            $sizeName = strtolower($size->name);
            return [
                'id' => $size->id,
                'name' => $size->name,
                'max_allowed' => $maxQuantities[$sizeName] ?? 0,
                'current_quantity' => $outputFinishingDatum->output_quantities[$size->name] ?? 0
            ];
        });

        return view('backend.library.output_finishing_data.edit', [
            'outputFinishingDatum' => $outputFinishingDatum,
            'sizeData' => $sizeData
        ]);
    }

    public function update(Request $request, OutputFinishingData $outputFinishingDatum)
    {
        $request->validate([
            'date' => 'required|date',
            'quantities.*' => 'nullable|integer|min:0',
        ]);

        $productCombination = $outputFinishingDatum->productCombination;
        $maxQuantities = $this->getMaxOutputQuantities($productCombination);

        // Add back current output quantities to available
        foreach ($outputFinishingDatum->output_quantities as $size => $quantity) {
            $sizeName = strtolower($size);
            if (isset($maxQuantities[$sizeName])) {
                $maxQuantities[$sizeName] += $quantity;
            }
        }

        $outputQuantities = [];
        $totalOutputQuantity = 0;
        $errors = [];

        foreach ($request->input('quantities', []) as $sizeId => $quantity) {
            $size = Size::where('is_active', 1)->find($sizeId);
            if ($size && $quantity > 0) {
                $sizeName = strtolower($size->name);
                $maxAllowed = $maxQuantities[$sizeName] ?? 0;

                if ($quantity > $maxAllowed) {
                    $errors["quantities.$sizeId"] = "Quantity for {$size->name} exceeds available limit ($maxAllowed)";
                    continue;
                }

                $outputQuantities[$size->name] = (int)$quantity;
                $totalOutputQuantity += (int)$quantity;
            }
        }

        if (!empty($errors)) {
            return redirect()->back()->withErrors($errors)->withInput();
        }

        $outputFinishingDatum->update([
            'date' => $request->date,
            'output_quantities' => $outputQuantities,
            'total_output_quantity' => $totalOutputQuantity,
        ]);

        return redirect()->route('output_finishing_data.index')->with('success', 'Output finishing data updated successfully.');
    }

    public function destroy(OutputFinishingData $outputFinishingDatum)
    {
        $outputFinishingDatum->delete();
        return redirect()->route('output_finishing_data.index')->with('success', 'Output finishing data deleted successfully.');
    }

    // Report: Total Balance Report
    public function totalBalanceReport(Request $request)
    {
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $reportData = [];

        $productCombinations = ProductCombination::whereHas('lineInputData') // Only include PCs that have line input
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
                    'sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
                    'total' => 0
                ];
            }

            // Get total input quantities
            $inputQuantities = LineInputData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->input_quantities)
                ->groupBy(fn($value, $key) => strtolower($key))
                ->map(fn($group) => $group->sum())
                ->toArray();

            // Get total output quantities
            $outputQuantities = OutputFinishingData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->output_quantities)
                ->groupBy(fn($value, $key) => strtolower($key))
                ->map(fn($group) => $group->sum())
                ->toArray();

            foreach ($allSizes as $size) {
                $sizeName = strtolower($size->name);
                $input = $inputQuantities[$sizeName] ?? 0;
                $output = $outputQuantities[$sizeName] ?? 0;
                $balance = max(0, $input - $output);

                $reportData[$key]['sizes'][$sizeName] = $balance;
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
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $wipData = [];

        $productCombinations = ProductCombination::whereHas('lineInputData')
            ->with('style', 'color')
            ->get();

        foreach ($productCombinations as $pc) {
            $style = $pc->style->name;
            $color = $pc->color->name;
            $key = $style . '-' . $color;

            $wipData[$key] = [
                'style' => $style,
                'color' => $color,
                'sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
                'total' => 0
            ];

            // Get input quantities
            $inputQuantities = LineInputData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->input_quantities)
                ->groupBy(fn($value, $key) => strtolower($key))
                ->map(fn($group) => $group->sum())
                ->toArray();

            // Get packed quantities
            $packedQuantities = OutputFinishingData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->output_quantities)
                ->groupBy(fn($value, $key) => strtolower($key))
                ->map(fn($group) => $group->sum())
                ->toArray();

            foreach ($allSizes as $size) {
                $sizeName = strtolower($size->name);
                $input = $inputQuantities[$sizeName] ?? 0;
                $packed = $packedQuantities[$sizeName] ?? 0;
                $wip = max(0, $input - $packed);

                $wipData[$key]['sizes'][$sizeName] = $wip;
                $wipData[$key]['total'] += $wip;
            }
        }

        return view('backend.library.output_finishing_data.reports.sewing_wip', [
            'wipData' => array_values($wipData),
            'allSizes' => $allSizes
        ]);
    }
}
