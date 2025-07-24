<?php

namespace App\Http\Controllers;

use App\Models\FinishPackingData;
use App\Models\LineInputData;
use App\Models\ProductCombination;
use App\Models\Size;
use App\Models\CuttingData; // Import CuttingData
use App\Models\PrintSendData; // Import PrintSendData
use App\Models\PrintReceiveData; // Import PrintReceiveData
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinishPackingDataController extends Controller
{
    public function index(Request $request)
    {
        $query = FinishPackingData::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

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

        $finishPackingData = $query->orderBy('date', 'desc')->paginate(10);
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();

        return view('backend.library.finish_packing_data.index', compact('finishPackingData', 'allSizes'));
    }

    public function create()
    {
        $productCombinations = ProductCombination::whereHas('lineInputData')
            ->with('buyer', 'style', 'color')
            ->get();

        $sizes = Size::where('is_active', 1)->get();

        return view('backend.library.finish_packing_data.create', compact('productCombinations', 'sizes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'product_combination_id' => 'required|exists:product_combinations,id',
            'quantities.*' => 'nullable|integer|min:0',
        ]);

        $productCombination = ProductCombination::findOrFail($request->product_combination_id);
        $maxQuantities = $this->getMaxPackingQuantities($productCombination);

        $packingQuantities = [];
        $totalPackingQuantity = 0;
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

                $packingQuantities[$size->name] = (int)$quantity;
                $totalPackingQuantity += (int)$quantity;
            }
        }

        if (!empty($errors)) {
            return redirect()->back()->withErrors($errors)->withInput();
        }

        FinishPackingData::create([
            'date' => $request->date,
            'product_combination_id' => $request->product_combination_id,
            'packing_quantities' => $packingQuantities,
            'total_packing_quantity' => $totalPackingQuantity,
        ]);

        return redirect()->route('finish_packing_data.index')->with('success', 'Finish packing data added successfully.');
    }

    public function getMaxPackingQuantities(ProductCombination $pc)
    {
        $maxQuantities = [];
        $allSizes = Size::where('is_active', 1)->get();

        // Get total input quantities
        $inputQuantities = LineInputData::where('product_combination_id', $pc->id)
            ->get()
            ->flatMap(fn($item) => $item->input_quantities)
            ->groupBy(fn($value, $key) => strtolower($key))
            ->map(fn($group) => $group->sum())
            ->toArray();

        // Get total packed quantities
        $packedQuantities = FinishPackingData::where('product_combination_id', $pc->id)
            ->get()
            ->flatMap(fn($item) => $item->packing_quantities)
            ->groupBy(fn($value, $key) => strtolower($key))
            ->map(fn($group) => $group->sum())
            ->toArray();

        foreach ($allSizes as $size) {
            $sizeName = strtolower($size->name);
            $input = $inputQuantities[$sizeName] ?? 0;
            $packed = $packedQuantities[$sizeName] ?? 0;
            $maxQuantities[$sizeName] = max(0, $input - $packed);
        }

        return $maxQuantities;
    }

    public function show(FinishPackingData $finishPackingDatum)
    {
        return view('backend.library.finish_packing_data.show', compact('finishPackingDatum'));
    }

    public function edit(FinishPackingData $finishPackingDatum)
    {
        $finishPackingDatum->load('productCombination.style', 'productCombination.color');
        $sizes = Size::where('is_active', 1)->get();
        $maxQuantities = $this->getMaxPackingQuantities($finishPackingDatum->productCombination);

        $sizeData = $sizes->map(function ($size) use ($finishPackingDatum, $maxQuantities) {
            $sizeName = strtolower($size->name);
            return [
                'id' => $size->id,
                'name' => $size->name,
                'max_allowed' => $maxQuantities[$sizeName] ?? 0,
                'current_quantity' => $finishPackingDatum->packing_quantities[$size->name] ?? 0
            ];
        });

        return view('backend.library.finish_packing_data.edit', [
            'finishPackingDatum' => $finishPackingDatum,
            'sizeData' => $sizeData
        ]);
    }

    public function update(Request $request, FinishPackingData $finishPackingDatum)
    {
        $request->validate([
            'date' => 'required|date',
            'quantities.*' => 'nullable|integer|min:0',
        ]);

        $productCombination = $finishPackingDatum->productCombination;
        $maxQuantities = $this->getMaxPackingQuantities($productCombination);

        $packingQuantities = [];
        $totalPackingQuantity = 0;
        $errors = [];

        foreach ($request->input('quantities', []) as $sizeId => $quantity) {
            $size = Size::where('is_active', 1)->find($sizeId);
            if ($size && $quantity > 0) {
                $sizeName = strtolower($size->name);
                $maxAllowed = ($maxQuantities[$sizeName] ?? 0) + ($finishPackingDatum->packing_quantities[$size->name] ?? 0);

                if ($quantity > $maxAllowed) {
                    $errors["quantities.$sizeId"] = "Quantity for {$size->name} exceeds available limit ($maxAllowed)";
                    continue;
                }

                $packingQuantities[$size->name] = (int)$quantity;
                $totalPackingQuantity += (int)$quantity;
            }
        }

        if (!empty($errors)) {
            return redirect()->back()->withErrors($errors)->withInput();
        }

        $finishPackingDatum->update([
            'date' => $request->date,
            'packing_quantities' => $packingQuantities,
            'total_packing_quantity' => $totalPackingQuantity,
        ]);

        return redirect()->route('finish_packing_data.index')->with('success', 'Finish packing data updated successfully.');
    }

    public function destroy(FinishPackingData $finishPackingDatum)
    {
        $finishPackingDatum->delete();
        return redirect()->route('finish_packing_data.index')->with('success', 'Finish packing data deleted successfully.');
    }

    // Reports
    public function totalPackingReport(Request $request)
    {
        $query = FinishPackingData::with('productCombination.style', 'productCombination.color');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $finishPackingData = $query->get();
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $reportData = [];

        foreach ($finishPackingData as $data) {
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

            foreach ($data->packing_quantities as $size => $qty) {
                $normalized = strtolower($size);
                if (array_key_exists($normalized, $reportData[$key]['sizes'])) {
                    $reportData[$key]['sizes'][$normalized] += $qty;
                }
            }
            $reportData[$key]['total'] += $data->total_packing_quantity;
        }

        return view('backend.library.finish_packing_data.reports.total_packing', [
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
            $packedQuantities = FinishPackingData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->packing_quantities)
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

        return view('backend.library.finish_packing_data.reports.sewing_wip', [
            'wipData' => array_values($wipData),
            'allSizes' => $allSizes
        ]);
    }

    public function getAvailablePackingQuantities(ProductCombination $productCombination)
    {
        $maxQuantities = $this->getMaxPackingQuantities($productCombination);
        $sizes = Size::where('is_active', 1)->get();

        return response()->json([
            'availableQuantities' => $maxQuantities,
            'sizes' => $sizes
        ]);
    }

    public function balanceReport(Request $request)
    {
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $reportData = [];

        $productCombinations = ProductCombination::whereHas('lineInputData') // Only include PCs that have at least some line input
            ->with('style', 'color')
            ->get();

        foreach ($productCombinations as $pc) {
            $style = $pc->style->name;
            $color = $pc->color->name;
            $key = $pc->id; // Use product combination ID as key for uniqueness

            // Initialize data structure for this product combination
            $reportData[$key] = [
                'style' => $style,
                'color' => $color,
                'stage_balances' => [ // Will hold stage => size => quantity
                    'cutting' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
                    'print_wip' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
                    'sewing_wip' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
                    'packing_wip' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
                ],
                'total_per_stage' => [ // Will hold total balances for each stage for this PC
                    'cutting' => 0,
                    'print_wip' => 0,
                    'sewing_wip' => 0,
                    'packing_wip' => 0,
                ]
            ];

            // Fetch all relevant quantities for this product combination
            $cutQuantities = CuttingData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->cut_quantities)
                ->groupBy(fn($value, $key) => strtolower($key))
                ->map(fn($group) => $group->sum())
                ->toArray();

            $printSendQuantities = PrintSendData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->send_quantities)
                ->groupBy(fn($value, $key) => strtolower($key))
                ->map(fn($group) => $group->sum())
                ->toArray();

            $printReceiveQuantities = PrintReceiveData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->receive_quantities)
                ->groupBy(fn($value, $key) => strtolower($key))
                ->map(fn($group) => $group->sum())
                ->toArray();

            $lineInputQuantities = LineInputData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->input_quantities)
                ->groupBy(fn($value, $key) => strtolower($key))
                ->map(fn($group) => $group->sum())
                ->toArray();

            $finishPackingQuantities = FinishPackingData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->packing_quantities)
                ->groupBy(fn($value, $key) => strtolower($key))
                ->map(fn($group) => $group->sum())
                ->toArray();

            foreach ($allSizes as $size) {
                $sizeName = strtolower($size->name);

                $cut = $cutQuantities[$sizeName] ?? 0;
                $printSent = $printSendQuantities[$sizeName] ?? 0;
                $printReceived = $printReceiveQuantities[$sizeName] ?? 0;
                $lineInput = $lineInputQuantities[$sizeName] ?? 0;
                $packed = $finishPackingQuantities[$sizeName] ?? 0;

                // Calculate stage balances for this size
                // Cutting: Total quantity cut for this PC and size
                $reportData[$key]['stage_balances']['cutting'][$sizeName] = $cut;

                // Print WIP: Items sent to print but not yet received back
                $reportData[$key]['stage_balances']['print_wip'][$sizeName] = max(0, $printSent - $printReceived);

                // Sewing WIP: Items received from print but not yet input to line
                $reportData[$key]['stage_balances']['sewing_wip'][$sizeName] = max(0, $printReceived - $lineInput);

                // Packing WIP: Items input to line but not yet packed
                $reportData[$key]['stage_balances']['packing_wip'][$sizeName] = max(0, $lineInput - $packed);

                // Accumulate totals for the current product combination
                $reportData[$key]['total_per_stage']['cutting'] += $reportData[$key]['stage_balances']['cutting'][$sizeName];
                $reportData[$key]['total_per_stage']['print_wip'] += $reportData[$key]['stage_balances']['print_wip'][$sizeName];
                $reportData[$key]['total_per_stage']['sewing_wip'] += $reportData[$key]['stage_balances']['sewing_wip'][$sizeName];
                $reportData[$key]['total_per_stage']['packing_wip'] += $reportData[$key]['stage_balances']['packing_wip'][$sizeName];
            }
        }

        return view('backend.library.finish_packing_data.reports.balance', [
            'reportData' => array_values($reportData), // Pass as array of values
            'allSizes' => $allSizes
        ]);
    }
}
