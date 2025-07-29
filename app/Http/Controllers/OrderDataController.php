<?php

namespace App\Http\Controllers;

use App\Models\OrderData;
use App\Models\ProductCombination;
use App\Models\Size;
use Illuminate\Http\Request;

class OrderDataController extends Controller
{
    public function index(Request $request)
    {
        $query = OrderData::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

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

        $orderData = $query->orderBy('date', 'desc')->paginate(10);
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();

        return view('backend.library.order_data.index', compact('orderData', 'allSizes'));
    }

    public function create()
    {
        $productCombinations = ProductCombination::with('buyer', 'style', 'color')->get();
        $sizes = Size::where('is_active', 1)->get();

        return view('backend.library.order_data.create', compact('productCombinations', 'sizes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'product_combination_id' => 'required|exists:product_combinations,id',
            'quantities.*' => 'nullable|integer|min:0',
        ]);

        $orderQuantities = [];
        $totalOrderQuantity = 0;

        foreach ($request->input('quantities', []) as $sizeId => $quantity) {
            $size = Size::where('is_active', 1)->find($sizeId);
            if ($size && $quantity > 0) {
                $orderQuantities[$size->name] = (int)$quantity;
                $totalOrderQuantity += (int)$quantity;
            }
        }

        OrderData::create([
            'date' => $request->date,
            'product_combination_id' => $request->product_combination_id,
            'order_quantities' => $orderQuantities,
            'total_order_quantity' => $totalOrderQuantity,
        ]);

        return redirect()->route('order_data.index')->with('success', 'Order data added successfully.');
    }

    public function show(OrderData $orderDatum)
    {
        return view('backend.library.order_data.show', compact('orderDatum'));
    }

    public function edit(OrderData $orderDatum)
    {
        $orderDatum->load('productCombination.style', 'productCombination.color');
        $sizes = Size::where('is_active', 1)->get();

        return view('backend.library.order_data.edit', compact('orderDatum', 'sizes'));
    }

    public function update(Request $request, OrderData $orderDatum)
    {
        $request->validate([
            'date' => 'required|date',
            'quantities.*' => 'nullable|integer|min:0',
        ]);

        $orderQuantities = [];
        $totalOrderQuantity = 0;

        foreach ($request->input('quantities', []) as $sizeId => $quantity) {
            $size = Size::where('is_active', 1)->find($sizeId);
            if ($size && $quantity > 0) {
                $orderQuantities[$size->name] = (int)$quantity;
                $totalOrderQuantity += (int)$quantity;
            }
        }

        $orderDatum->update([
            'date' => $request->date,
            'order_quantities' => $orderQuantities,
            'total_order_quantity' => $totalOrderQuantity,
        ]);

        return redirect()->route('order_data.index')->with('success', 'Order data updated successfully.');
    }

    public function destroy(OrderData $orderDatum)
    {
        $orderDatum->delete();
        return redirect()->route('order_data.index')->with('success', 'Order data deleted successfully.');
    }

    // Report
    public function totalOrderReport(Request $request)
    {
        $query = OrderData::with('productCombination.style', 'productCombination.color');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $orderData = $query->get();
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $reportData = [];

        foreach ($orderData as $data) {
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

            foreach ($data->order_quantities as $size => $qty) {
                $normalized = strtolower($size);
                if (array_key_exists($normalized, $reportData[$key]['sizes'])) {
                    $reportData[$key]['sizes'][$normalized] += $qty;
                }
            }
            $reportData[$key]['total'] += $data->total_order_quantity;
        }

        return view('backend.library.order_data.total_order', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes
        ]);
    }
}
