<?php

namespace App\Http\Controllers;

use App\Models\ShipmentData;
use App\Models\ProductCombination;
use App\Models\Size;
use App\Models\FinishPackingData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShipmentDataController extends Controller
{
    public function index(Request $request)
    {
        $query = ShipmentData::with('productCombination.buyer', 'productCombination.style', 'productCombination.color');

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

        $shipmentData = $query->orderBy('date', 'desc')->paginate(10);
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();

        return view('backend.library.shipment_data.index', compact('shipmentData', 'allSizes'));
    }

    public function create()
    {
        $productCombinations = ProductCombination::whereHas('finishPackingData')
            ->with('buyer', 'style', 'color')
            ->get();

        $sizes = Size::where('is_active', 1)->get();

        return view('backend.library.shipment_data.create', compact('productCombinations', 'sizes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'product_combination_id' => 'required|exists:product_combinations,id',
            'quantities.*' => 'nullable|integer|min:0',
        ]);

        $productCombination = ProductCombination::findOrFail($request->product_combination_id);
        $availableQuantities = $this->getAvailableQuantitiesArray($productCombination);

        $shipmentQuantities = [];
        $totalShipmentQuantity = 0;
        $errors = [];

        foreach ($request->input('quantities', []) as $sizeId => $quantity) {
            $size = Size::where('is_active', 1)->find($sizeId);
            if ($size && $quantity > 0) {
                $sizeName = strtolower($size->name);
                $available = $availableQuantities[$sizeName] ?? 0;

                if ($quantity > $available) {
                    $errors["quantities.$sizeId"] = "Quantity for {$size->name} exceeds available limit ($available)";
                    continue;
                }

                $shipmentQuantities[$size->name] = (int)$quantity;
                $totalShipmentQuantity += (int)$quantity;
            }
        }

        if (!empty($errors)) {
            return redirect()->back()->withErrors($errors)->withInput();
        }

        ShipmentData::create([
            'date' => $request->date,
            'product_combination_id' => $request->product_combination_id,
            'shipment_quantities' => $shipmentQuantities,
            'total_shipment_quantity' => $totalShipmentQuantity,
        ]);

        return redirect()->route('shipment_data.index')->with('success', 'Shipment data added successfully.');
    }

    // Replace the existing getAvailableQuantities method with these two methods:

    protected function getAvailableQuantitiesArray(ProductCombination $productCombination)
    {
        $availableQuantities = [];
        $allSizes = Size::where('is_active', 1)->get();

        $packedQuantities = FinishPackingData::where('product_combination_id', $productCombination->id)
            ->get()
            ->flatMap(fn($item) => $item->packing_quantities)
            ->groupBy(fn($value, $key) => strtolower($key))
            ->map(fn($group) => $group->sum())
            ->toArray();

        $shippedQuantities = ShipmentData::where('product_combination_id', $productCombination->id)
            ->get()
            ->flatMap(fn($item) => $item->shipment_quantities)
            ->groupBy(fn($value, $key) => strtolower($key))
            ->map(fn($group) => $group->sum())
            ->toArray();

        foreach ($allSizes as $size) {
            $sizeName = strtolower($size->name);
            $packed = $packedQuantities[$sizeName] ?? 0;
            $shipped = $shippedQuantities[$sizeName] ?? 0;
            $availableQuantities[$sizeName] = max(0, $packed - $shipped);
        }

        return $availableQuantities;
    }

    public function getAvailableQuantities(ProductCombination $productCombination)
    {
        $availableQuantities = $this->getAvailableQuantitiesArray($productCombination);
        $allSizes = Size::where('is_active', 1)->get();

        return response()->json([
            'availableQuantities' => $availableQuantities,
            'sizes' => $allSizes->map(fn($size) => [
                'id' => $size->id,
                'name' => $size->name
            ])->toArray()
        ]);
    }
    public function show(ShipmentData $shipmentDatum)
    {
        return view('backend.library.shipment_data.show', compact('shipmentDatum'));
    }

    public function edit(ShipmentData $shipmentDatum)
    {
        $shipmentDatum->load('productCombination.style', 'productCombination.color');
        $sizes = Size::where('is_active', 1)->get();
        // In edit method
        // In edit() method:
        $availableQuantities = $this->getAvailableQuantitiesArray($shipmentDatum->productCombination);

        // Add back current shipment quantities to available
        foreach ($shipmentDatum->shipment_quantities as $size => $quantity) {
            $sizeName = strtolower($size);
            if (isset($availableQuantities[$sizeName])) {
                $availableQuantities[$sizeName] += $quantity;
            }
        }

        $sizeData = $sizes->map(function ($size) use ($shipmentDatum, $availableQuantities) {
            $sizeName = strtolower($size->name);
            return [
                'id' => $size->id,
                'name' => $size->name,
                'available' => $availableQuantities[$sizeName] ?? 0,
                'current_quantity' => $shipmentDatum->shipment_quantities[$size->name] ?? 0
            ];
        });

        return view('backend.library.shipment_data.edit', [
            'shipmentDatum' => $shipmentDatum,
            'sizeData' => $sizeData
        ]);
    }

    public function update(Request $request, ShipmentData $shipmentDatum)
    {
        $request->validate([
            'date' => 'required|date',
            'quantities.*' => 'nullable|integer|min:0',
        ]);

        $productCombination = $shipmentDatum->productCombination;
        $availableQuantities = $this->getAvailableQuantitiesArray($productCombination);

        // Add back current shipment quantities to available
        foreach ($shipmentDatum->shipment_quantities as $size => $quantity) {
            $sizeName = strtolower($size);
            if (isset($availableQuantities[$sizeName])) {
                $availableQuantities[$sizeName] += $quantity;
            }
        }

        $shipmentQuantities = [];
        $totalShipmentQuantity = 0;
        $errors = [];

        foreach ($request->input('quantities', []) as $sizeId => $quantity) {
            $size = Size::where('is_active', 1)->find($sizeId);
            if ($size && $quantity > 0) {
                $sizeName = strtolower($size->name);
                $available = $availableQuantities[$sizeName] ?? 0;

                if ($quantity > $available) {
                    $errors["quantities.$sizeId"] = "Quantity for {$size->name} exceeds available limit ($available)";
                    continue;
                }

                $shipmentQuantities[$size->name] = (int)$quantity;
                $totalShipmentQuantity += (int)$quantity;
            }
        }

        if (!empty($errors)) {
            return redirect()->back()->withErrors($errors)->withInput();
        }

        $shipmentDatum->update([
            'date' => $request->date,
            'shipment_quantities' => $shipmentQuantities,
            'total_shipment_quantity' => $totalShipmentQuantity,
        ]);

        return redirect()->route('shipment_data.index')->with('success', 'Shipment data updated successfully.');
    }

    public function destroy(ShipmentData $shipmentDatum)
    {
        $shipmentDatum->delete();
        return redirect()->route('shipment_data.index')->with('success', 'Shipment data deleted successfully.');
    }

    // Reports
    public function totalShipmentReport(Request $request)
    {
        $query = ShipmentData::with('productCombination.style', 'productCombination.color');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $shipmentData = $query->get();
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $reportData = [];

        foreach ($shipmentData as $data) {
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

            foreach ($data->shipment_quantities as $size => $qty) {
                $normalized = strtolower($size);
                if (array_key_exists($normalized, $reportData[$key]['sizes'])) {
                    $reportData[$key]['sizes'][$normalized] += $qty;
                }
            }
            $reportData[$key]['total'] += $data->total_shipment_quantity;
        }

        return view('backend.library.shipment_data.reports.total_shipment', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes
        ]);
    }

    public function readyGoodsReport(Request $request)
    {
        $allSizes = Size::where('is_active', 1)->orderBy('name', 'asc')->get();
        $reportData = [];

        $productCombinations = ProductCombination::whereHas('finishPackingData')
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

            // Get total packed quantities
            $packedQuantities = FinishPackingData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->packing_quantities)
                ->groupBy(fn($value, $key) => strtolower($key))
                ->map(fn($group) => $group->sum())
                ->toArray();

            // Get total shipped quantities
            $shippedQuantities = ShipmentData::where('product_combination_id', $pc->id)
                ->get()
                ->flatMap(fn($item) => $item->shipment_quantities)
                ->groupBy(fn($value, $key) => strtolower($key))
                ->map(fn($group) => $group->sum())
                ->toArray();

            foreach ($allSizes as $size) {
                $sizeName = strtolower($size->name);
                $packed = $packedQuantities[$sizeName] ?? 0;
                $shipped = $shippedQuantities[$sizeName] ?? 0;
                $ready = max(0, $packed - $shipped);

                $reportData[$key]['sizes'][$sizeName] = $ready;
                $reportData[$key]['total'] += $ready;
            }
        }

        return view('backend.library.shipment_data.reports.ready_goods', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes
        ]);
    }
}