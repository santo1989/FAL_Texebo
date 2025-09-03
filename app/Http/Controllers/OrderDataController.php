<?php

namespace App\Http\Controllers;

use App\Models\OrderData;
use App\Models\ProductCombination;
use App\Models\Size;
use App\Models\Style;
use App\Models\Color; // Assuming you have a Color model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderDataController extends Controller
{
    public function index(Request $request)
    {
        $query = OrderData::with('productCombination.buyer', 'style', 'color');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('style', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            })->orWhereHas('color', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            })->orWhere('po_number', 'like', '%' . $search . '%');
        }
        if ($request->filled('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        $orderData = $query->orderBy('created_at', 'desc')->paginate(10);
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // dd($allSizes, $orderData);

        return view('backend.library.order_data.index', compact('orderData', 'allSizes'));
    }

    // updateStatus
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'po_status' => 'required|in:running,completed,cancelled',
        ]);

        $orderData = OrderData::findOrFail($id);
        $orderData->po_status = $request->input('po_status');
        $orderData->save();

        return redirect()->route('order_data.index')->with('message', 'Order status updated successfully!');
    }

    public function create()
    {
        $productCombinations = ProductCombination::with('buyer', 'style', 'color')->get();
        $sizes = Size::where('is_active', 1)->get();
        $styles = Style::where('is_active', 1)->get();
        $colors = Color::all();

        return view('backend.library.order_data.create', compact('productCombinations', 'sizes', 'styles', 'colors'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'date' => 'required|date',
            'po_number' => 'required|string|max:255',
            'combinations' => 'required|array',
            'combinations.*.product_combination_id' => 'required|exists:product_combinations,id',
            'combinations.*.quantities' => 'required|array',
            'combinations.*.quantities.*' => 'nullable|integer|min:0'
        ]);

        DB::beginTransaction();

        try {
            foreach ($request->combinations as $combinationData) {
                // Filter out quantities that are 0 or null
                $quantities = array_filter($combinationData['quantities']);

                // Only create an order if there's at least one quantity greater than 0
                if (!empty($quantities)) {
                    $totalQuantity = array_sum($quantities);

                    // Fetch style and color IDs from the product combination
                    $productCombination = ProductCombination::find($combinationData['product_combination_id']);

                    OrderData::create([
                        'date' => $request->date,
                        'po_number' => strtoupper($request->po_number),
                        'style_id' => $productCombination->style_id,
                        'color_id' => $productCombination->color_id,
                        'product_combination_id' => $combinationData['product_combination_id'],
                        // Let Laravel's model casting handle the conversion to JSON
                        'order_quantities' => $quantities,
                        'total_order_quantity' => $totalQuantity,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('order_data.index')->with('message', 'Order data added successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors('Failed to add order data. Please try again. Error: ' . $e->getMessage());
        }
    }

    public function show(OrderData $orderDatum)
    {
        $orderDatum->load('productCombination.buyer', 'style', 'color');
        return view('backend.library.order_data.show', compact('orderDatum'));
    }

    public function edit(OrderData $orderDatum)
    {
        $orderDatum->load('productCombination.buyer', 'style', 'color');
        $sizes = Size::where('is_active', 1)->get();
        $poStatuses = ['running', 'completed', 'cancelled'];

        return view('backend.library.order_data.edit', compact('orderDatum', 'sizes', 'poStatuses'));
    }

    public function update(Request $request, OrderData $orderDatum)
    {
        $request->validate([
            'date' => 'required|date',
            'po_number' => 'required|string|max:255',
            'po_status' => 'required|string|in:running,completed,cancelled',
            'quantities.*' => 'nullable|integer|min:0',
        ]);

        $orderQuantities = [];
        $totalOrderQuantity = 0;

        // Use the size ID as the key to match the database schema
        foreach ($request->input('quantities', []) as $sizeId => $quantity) {
            if ($quantity > 0) {
                $orderQuantities[$sizeId] = (int)$quantity;
                $totalOrderQuantity += (int)$quantity;
            }
        }

        $orderDatum->update([
            'date' => $request->date,
            'po_number' => $request->po_number,
            'order_quantities' => $orderQuantities,
            'total_order_quantity' => $totalOrderQuantity,
            'po_status' => $request->po_status,
        ]);

        return redirect()->route('order_data.index')->with('success', 'Order data updated successfully.');
    }

    public function destroy(OrderData $orderDatum)
    {
        // $orderDatum->delete();
        //if any related data exists in any table then delete it as well
        DB::transaction(function () use ($orderDatum) {
            $orderDatum->sublimationPrintSends()->delete();
            $orderDatum->sublimationPrintReceives()->delete();
            $orderDatum->printSends()->delete();
            $orderDatum->printReceives()->delete();
            $orderDatum->lineInputData()->delete();
            $orderDatum->outputFinishingData()->delete();
            $orderDatum->finishPackingData()->delete();
            $orderDatum->packedData()->delete();
            $orderDatum->shipmentData()->delete();

            $orderDatum->delete();
        });

        return redirect()->route('order_data.index')->with('success', 'Order data deleted successfully.');
    }

    // totalOrderReport
    // public function totalOrderReport(Request $request)
    // {
    //     $query = OrderData::with('style', 'color');

    //     // Filter by PO Number if selected
    //     if ($request->filled('po_number')) {
    //         $query->where('po_number', $request->po_number);
    //     }

    //     if ($request->filled('start_date') && $request->filled('end_date')) {
    //         $query->whereBetween('date', [$request->start_date, $request->end_date]);
    //     }

    //     // Filter by style if selected
    //     if ($request->filled('style_id')) {
    //         $query->where('style_id', $request->style_id);
    //     }

    //     // Filter by color if selected
    //     if ($request->filled('color_id')) {
    //         $query->where('color_id', $request->color_id);
    //     }

    //     $orderData = $query->get();
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $reportData = [];

    //     foreach ($orderData as $data) {
    //         $style = $data->style->name ?? 'N/A';
    //         $color = $data->color->name ?? 'N/A';
    //         // Using a combination of Style and Color to group rows
    //         $key = $style . '-' . $color;

    //         // Initialize report data for the style-color combination if it doesn't exist
    //         if (!isset($reportData[$key])) {
    //             $reportData[$key] = [
    //                 'style' => $style,
    //                 'color' => $color,
    //                 // Use size IDs as keys for initialization
    //                 'sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
    //                 'total' => 0
    //             ];
    //         }

    //         // Aggregate quantities based on size IDs
    //         // $data->order_quantities will be an array because of the model cast
    //         foreach ($data->order_quantities as $sizeId => $qty) {
    //             // Ensure the size ID exists in our initialized array
    //             if (array_key_exists($sizeId, $reportData[$key]['sizes'])) {
    //                 $reportData[$key]['sizes'][$sizeId] += $qty;
    //             }
    //         }
    //         $reportData[$key]['total'] += $data->total_order_quantity;
    //     }

    //     // Add filtering for distinct PO numbers to the view
    //     $distinctPoNumbers = OrderData::distinct()->pluck('po_number');
    //     $allStyles = Style::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $allColors = Color::where('is_active', 1)->orderBy('id', 'asc')->get();

    //     return view('backend.library.order_data.total_order', [
    //         'reportData' => array_values($reportData),
    //         'allSizes' => $allSizes,
    //         'orderData' => $orderData, // Pass the original query results for the PO filter in the view
    //         'distinctPoNumbers' => $distinctPoNumbers,
    //         'allStyles' => $allStyles,
    //         'allColors' => $allColors,
    //     ]);
    // }

    public function totalOrderReport(Request $request)
    {
        $query = OrderData::with('style', 'color');

        // Filter by PO Number if selected
        if ($request->has('po_number') && is_array($request->po_number)) {
            $query->whereIn('po_number', $request->po_number);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        // Filter by style if selected
        if ($request->has('style_id') && is_array($request->style_id)) {
            $query->whereIn('style_id', $request->style_id);
        }

        // Filter by color if selected
        if ($request->has('color_id') && is_array($request->color_id)) {
            $query->whereIn('color_id', $request->color_id);
        }

        $orderData = $query->get();
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $reportData = [];

        foreach ($orderData as $data) {
            $style = $data->style->name ?? 'N/A';
            $color = $data->color->name ?? 'N/A';
            $key = $style . '-' . $color;

            if (!isset($reportData[$key])) {
                $reportData[$key] = [
                    'style' => $style,
                    'color' => $color,
                    'sizes' => array_fill_keys($allSizes->pluck('id')->toArray(), 0),
                    'total' => 0
                ];
            }

            foreach ($data->order_quantities as $sizeId => $qty) {
                if (array_key_exists($sizeId, $reportData[$key]['sizes'])) {
                    $reportData[$key]['sizes'][$sizeId] += $qty;
                }
            }
            $reportData[$key]['total'] += $data->total_order_quantity;
        }

        $distinctPoNumbers = OrderData::distinct()->pluck('po_number');
        $allStyles = Style::orderBy('id', 'asc')->get();
        $allColors = Color::orderBy('id', 'asc')->get();

        return view('backend.library.order_data.total_order', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes,
            'orderData' => $orderData,
            'distinctPoNumbers' => $distinctPoNumbers,
            'allStyles' => $allStyles,
            'allColors' => $allColors,
        ]);
    }

    //old_data_create
    public function old_data_create()
    {
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $distinctPoNumbers = OrderData::where('po_status', 'running')->distinct()->pluck('po_number');

        return view('backend.library.old_data.create', compact('allSizes', 'distinctPoNumbers'));
    }

   

}
