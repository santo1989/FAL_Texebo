<?php

namespace App\Http\Controllers;

use App\Models\ProductCombination;
use App\Models\Buyer;
use App\Models\Style;
use App\Models\Color;
use App\Models\Size;
use Illuminate\Http\Request;

class ProductCombinationController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductCombination::with(['buyer', 'style', 'color', 'size']);

        // Search and filter logic
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;

            // If search term is numeric, search in ID fields
            if (is_numeric($search)) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('buyer', function ($subQ) use ($search) {
                        $subQ->where('id', $search);
                    })->orWhereHas('style', function ($subQ) use ($search) {
                        $subQ->where('id', $search);
                    })->orWhereHas('color', function ($subQ) use ($search) {
                        $subQ->where('id', $search);
                    });
                });
            } else {
                // If search term is text, search in name fields
                $query->where(function ($q) use ($search) {
                    $q->whereHas('buyer', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', '%' . $search . '%');
                    })->orWhereHas('style', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', '%' . $search . '%');
                    })->orWhereHas('color', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', '%' . $search . '%');
                    });
                });
            }
        }

        $combinations = $query->paginate(10);
        return view('backend.library.product_combinations.index', compact('combinations'));
    }

    public function create()
    {
        $buyers = Buyer::where('is_active', 0)->get();
        $styles = Style::where('is_active', 1)->get();
        $colors = Color::where('is_active', 1)->get();
        $sizes = Size::where('is_active', 1)->get();
        return view('backend.library.product_combinations.create', compact('buyers', 'styles', 'colors', 'sizes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'buyer_id' => 'required|exists:buyers,id',
            'style_id' => 'required|exists:styles,id',
            'color_id' => 'required|exists:colors,id',
            'size_ids' => 'required|array|min:1',
            'size_ids.*' => 'exists:sizes,id'
        ]);

        // Check for existing combination (ignoring sizes)
        $exists = ProductCombination::where('buyer_id', $request->buyer_id)
            ->where('style_id', $request->style_id)
            ->where('color_id', $request->color_id)
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors('A combination with this buyer, style, and color already exists!');
        }

        // Generate unique batch name
        $timestamp = time();
        $batchName = "BATCH-{$request->buyer_id}-{$request->style_id}-{$request->color_id}-{$timestamp}";

        // Create single combination
        ProductCombination::create([
            'buyer_id' => $request->buyer_id,
            'style_id' => $request->style_id,
            'color_id' => $request->color_id,
            'size_ids' => $request->size_ids,
            'batch_name' => $batchName,
        ]);

        return redirect()->route('product-combinations.index')
            ->with('message', "Combination created with batch `{$batchName}`.");
    }


    public function show(ProductCombination $productCombination)
    {
        return view('backend.library.product_combinations.show', compact('productCombination'));
    }

    public function edit(ProductCombination $productCombination)
    {
        $buyers = Buyer::where('is_active', 0)->get();
        $styles = Style::where('is_active', 1)->get();
        $colors = Color::where('is_active', 1)->get();
        $sizes = Size::where('is_active', 1)->get();
        return view('backend.library.product_combinations.edit', compact('productCombination', 'buyers', 'styles', 'colors', 'sizes'));
    }

    public function update(Request $request, ProductCombination $productCombination)
    {
        $request->validate([
            'buyer_id' => 'required|exists:buyers,id',
            'style_id' => 'required|exists:styles,id',
            'color_id' => 'required|exists:colors,id',
            'size_ids' => 'required|array',
            'size_ids.*' => 'exists:sizes,id',
        ]);

        foreach ($request->size_ids as $size_id) {
            $exists = ProductCombination::where('id', '!=', $productCombination->id)
                ->where('buyer_id', $request->buyer_id)
                ->where('style_id', $request->style_id)
                ->where('color_id', $request->color_id)
                ->whereJsonContains('size_ids', $size_id)
                ->exists();

            if ($exists) {
                return redirect()->back()->withErrors('This combination with one or more sizes already exists!');
            }
        }

        $productCombination->update([
            'buyer_id' => $request->buyer_id,
            'style_id' => $request->style_id,
            'color_id' => $request->color_id,
            'size_ids' => $request->size_ids,
        ]);

        return redirect()->route('product-combinations.index')
            ->with('message', 'Combination updated successfully!');
    }


    public function destroy(ProductCombination $productCombination)
    {
        $productCombination->delete();
        return redirect()->route('product-combinations.index')->with('message', 'Combination deleted successfully!');
    }

    public function active($id)
    {
        $combination = ProductCombination::findOrFail($id);
        $combination->update(['is_active' => !$combination->is_active]);
        $status = $combination->is_active ? 'activated' : 'deactivated';
        return redirect()->route('product-combinations.index')->with('message', "Combination {$status} successfully!");
    }

    public function print_embroidery($id)
    {
        $combination = ProductCombination::findOrFail($id);
        $combination->update(['print_embroidery' => !$combination->print_embroidery]);
        $status = $combination->print_embroidery ? 'enabled' : 'disabled';
        return redirect()->route('product-combinations.index')->with('message', "Print embroidery {$status} successfully!");
    }

    public function sublimation_print($id)
    {
        $combination = ProductCombination::findOrFail($id);
        $combination->update(['sublimation_print' => !$combination->sublimation_print]);
        $status = $combination->sublimation_print ? 'enabled' : 'disabled';
        return redirect()->route('product-combinations.index')->with('message', "Sublimation print {$status} successfully!");
    }

    public function getColorsByStyle($styleId)
    {
        $colors = Color::whereHas('productCombinations', function ($query) use ($styleId) {
            $query->where('style_id', $styleId);
        })->get();

        return response()->json($colors);
    }

    public function getCombinationByStyleColor($styleId, $colorId)
    {
        $combination = ProductCombination::with('buyer')
            ->where('style_id', $styleId)
            ->where('color_id', $colorId)
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
    public function getColorsByStylecom($styleId)
    {
        $colors = Color::whereHas('productCombinations', function ($query) use ($styleId) {
            $query->where('style_id', $styleId);
        })->get();

        return response()->json($colors);
    }
    public function getCombinationSizes($styleId, $colorId)
    {
        $combination = ProductCombination::with('size')
            ->where('style_id', $styleId)
            ->where('color_id', $colorId)
            ->first();

        if ($combination) {
            $sizeData = $combination->sizes->map(function ($size) {
                return ['id' => $size->id, 'name' => $size->name];
            });

            return response()->json([
                'success' => true,
                'combination_id' => $combination->id,
                'sizes' => $sizeData
            ]);
        }

        return response()->json(['success' => false]);
    }
}
