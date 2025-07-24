<?php

namespace App\Http\Controllers;

use App\Models\CuttingData;
use App\Models\ProductCombination;
use App\Models\Size; // We'll need this to display size names
use Illuminate\Http\Request;

class CuttingDataController extends Controller
{
    public function index(Request $request)
    {
        $query = CuttingData::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

        // You can add search/filter logic here if needed
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
        // ... more filters as needed

        $cuttingData = $query->orderBy('date', 'desc')->paginate(10);
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get(); // Get all possible sizes to dynamically generate table headers

        return view('backend.library.cutting_data.index', compact('cuttingData', 'allSizes'));
    }


    public function create()
    {
        $productCombinations = ProductCombination::with('buyer', 'style', 'color')->get();
        $sizes = Size::where('is_active', 1)->get(); // All available sizes to render dynamic input fields

        return view('backend.library.cutting_data.create', compact('productCombinations', 'sizes'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'product_combination_id' => 'required|exists:product_combinations,id',
            'quantities.*' => 'nullable|integer|min:0', // Validate dynamic quantity inputs
        ]);

        $productCombination = ProductCombination::findOrFail($request->product_combination_id);
        $allowedSizeIds = $productCombination->size_ids;
        $allSizes = Size::whereIn('id', $allowedSizeIds)->pluck('name', 'id')->toArray(); // Map size ID to name

        $cutQuantities = [];
        $totalCutQuantity = 0;

        foreach ($request->input('quantities') as $sizeId => $quantity) {
            if (in_array($sizeId, $allowedSizeIds) && $quantity !== null) {
                $sizeName = $allSizes[$sizeId];
                $cutQuantities[$sizeName] = (int) $quantity;
                $totalCutQuantity += (int) $quantity;
            }
        }

        if (empty($cutQuantities)) {
            return redirect()->back()->withErrors('At least one size quantity must be entered for cutting.');
        }

        CuttingData::create([
            'date' => $request->date,
            'product_combination_id' => $request->product_combination_id,
            'cut_quantities' => $cutQuantities,
            'total_cut_quantity' => $totalCutQuantity,
        ]);

        return redirect()->route('cutting_data.index')->with('success', 'Cutting data added successfully.');
    }

    public function show(CuttingData $cuttingDatum) // Laravel automatically injects based on route model binding
    {
        $cuttingDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get(); // Get all possible sizes to display in a structured way

        return view('backend.library.cutting_data.show', compact('cuttingDatum', 'allSizes'));
    }

    // Controller (App\Http\Controllers\CuttingDataController.php)
    public function edit(CuttingData $cuttingDatum)
    {
        $cuttingDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $productCombinations = ProductCombination::with('buyer', 'style', 'color')->get();
        $sizes = Size::where('is_active', 1)->get();

        // Create case-insensitive size name to ID map
        $sizeNameToIdMap = [];
        foreach ($sizes as $size) {
            $sizeNameToIdMap[($size->name)] = $size->id;
        }

        // Prepare existing quantities keyed by size ID
        $sizeQuantities = [];
        if (!empty($cuttingDatum->cut_quantities)) {
            foreach ($cuttingDatum->cut_quantities as $sizeName => $qty) {
                $normalized = ($sizeName);
                if (isset($sizeNameToIdMap[$normalized])) {
                    $sizeQuantities[$sizeNameToIdMap[$normalized]] = $qty;
                }
            }
        }

        return view('backend.library.cutting_data.edit', compact(
            'cuttingDatum',
            'productCombinations',
            'sizes',
            'sizeQuantities'
        ));
    }

    public function update(Request $request, CuttingData $cuttingDatum)
    {
        $request->validate([
            'date' => 'required|date',
            'product_combination_id' => 'required|exists:product_combinations,id',
            'quantities.*' => 'nullable|integer|min:0',
        ]);

        $productCombination = ProductCombination::findOrFail($request->product_combination_id);
        $allowedSizeIds = $productCombination->size_ids;

        $cutQuantities = [];
        $totalCutQuantity = 0;

        foreach ($request->input('quantities', []) as $sizeId => $quantity) {
            if (in_array((int)$sizeId, $allowedSizeIds) && !empty($quantity)) {
                $size = Size::find($sizeId);
                if ($size) {
                    $cutQuantities[($size->name)] = (int)$quantity;
                    $totalCutQuantity += (int)$quantity;
                }
            }
        }

        if (empty($cutQuantities)) {
            return redirect()->back()->withErrors('At least one size quantity must be entered for cutting.')->withInput();
        }

        $cuttingDatum->update([
            'date' => $request->date,
            'product_combination_id' => $request->product_combination_id,
            'cut_quantities' => $cutQuantities,
            'total_cut_quantity' => $totalCutQuantity,
        ]);

        return redirect()->route('cutting_data.index')->with('success', 'Cutting data updated successfully.');
    }


    public function destroy(CuttingData $cuttingDatum) // Using $cuttingDatum
    {
        $cuttingDatum->delete();
        return redirect()->route('cutting_data.index')->with('success', 'Cutting data deleted successfully.');
    }

    public function cutting_data_report(Request $request)
    {
        $query = CuttingData::with('productCombination.style', 'productCombination.color');

        // Apply filters
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }
        if ($request->filled('style_id')) {
            $query->whereHas('productCombination', function ($q) use ($request) {
                $q->where('style_id', $request->style_id);
            });
        }
        if ($request->filled('color_id')) {
            $query->whereHas('productCombination', function ($q) use ($request) {
                $q->where('color_id', $request->color_id);
            });
        }

        $cuttingData = $query->get();
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $reportData = [];

        foreach ($cuttingData as $data) {
            $styleName = $data->productCombination->style->name;
            $colorName = $data->productCombination->color->name;
            $key = $styleName . '-' . $colorName;

            if (!isset($reportData[$key])) {
                $reportData[$key] = [
                    'style' => $styleName,
                    'color' => $colorName,
                    'sizes' => [],
                    'total' => 0,
                ];
                // Initialize all sizes with zero
                foreach ($allSizes as $size) {
                    $reportData[$key]['sizes'][strtolower($size->name)] = 0;
                }
            }

            // Add quantities
            foreach ($data->cut_quantities as $sizeName => $quantity) {
                $normalizedSize = strtolower($sizeName);
                if (array_key_exists($normalizedSize, $reportData[$key]['sizes'])) {
                    $reportData[$key]['sizes'][$normalizedSize] += $quantity;
                }
            }
            $reportData[$key]['total'] += $data->total_cut_quantity;
        }

        // Convert to indexed array
        $reportData = array_values($reportData);

        $styles = \App\Models\Style::all();
        $colors = \App\Models\Color::all();

        return view('backend.library.cutting_data.report', compact(
            'reportData',
            'allSizes',
            'styles',
            'colors'
        ));
    }
}
