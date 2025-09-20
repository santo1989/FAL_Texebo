<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\CuttingData;
use App\Models\OrderData;
use App\Models\ProductCombination;
use App\Models\Size;
use App\Models\Style;
use App\Models\SublimationPrintSend;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SublimationPrintSendController extends Controller
{
    public function index(Request $request)
    {
        $query = SublimationPrintSend::with('productCombination.buyer', 'productCombination.style', 'productCombination.color', 'productCombination.size');

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
        $alldata = OrderData::with('style', 'color')->distinct()->get(['po_number', 'style_id', 'color_id']);
        $allStyles = $alldata->pluck('style')->unique('id')->values();
        $allColors = $alldata->pluck('color')->unique('id')->values();
        $distinctPoNumbers = $alldata->pluck('po_number')->unique()->values();

        return view('backend.library.sublimation_print_send_data.index', compact('printSendData', 'allSizes', 'allStyles', 'allColors', 'distinctPoNumbers'));
    }

    public function create()
    {
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Get distinct PO numbers from CuttingData where product combination has sublimation_print = true
        $sublimationProductIds = ProductCombination::where('sublimation_print', true)->pluck('id');
        $distinctPoNumbers = CuttingData::whereIn('product_combination_id', $sublimationProductIds)
            ->distinct()
            ->pluck('po_number')
            ->filter()
            ->values();

        return view('backend.library.sublimation_print_send_data.create', compact('distinctPoNumbers', 'allSizes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'po_number' => 'required|array',
            'po_number.*' => 'required|string',
            'old_order' => 'required|in:yes,no',
            'rows' => 'required|array',
            'rows.*.product_combination_id' => 'required|exists:product_combinations,id',
            'rows.*.sublimation_print_send_quantities.*' => 'nullable|integer|min:0',
            // 'rows.*.sublimation_print_send_waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        // dd($request->all());

        try {
            DB::beginTransaction();

            foreach ($request->rows as $row) {
                $sendQuantities = [];
                // $wasteQuantities = [];
                $totalSendQuantity = 0;
                // $totalWasteQuantity = 0;

                // if any row has no send or waste quantities, skip it and if any size has zero quantity, skip that size
                if (
                    empty(array_filter($row['sublimation_print_send_quantities'])) 
                    // &&
                    // empty(array_filter($row['sublimation_print_send_waste_quantities']))
                ) {
                    continue;
                }

                // Process send quantities
                foreach ($row['sublimation_print_send_quantities'] as $sizeId => $quantity) {
                    if ($quantity > 0) {
                        $size = Size::find($sizeId);
                        $sendQuantities[$size->id] = (int)$quantity;
                        $totalSendQuantity += (int)$quantity;
                    }
                }

                // // Process waste quantities
                // foreach ($row['sublimation_print_send_waste_quantities'] as $sizeId => $quantity) {
                //     if ($quantity > 0) {
                //         $size = Size::find($sizeId);
                //         $wasteQuantities[$size->id] = (int)$quantity;
                //         $totalWasteQuantity += (int)$quantity;
                //     }
                // }

                SublimationPrintSend::create([
                    'date' => $request->date,
                    'product_combination_id' => $row['product_combination_id'],
                    'po_number' => $row['po_number'],
                    'old_order' => $request->old_order,
                    'sublimation_print_send_quantities' => $sendQuantities,
                    'total_sublimation_print_send_quantity' => $totalSendQuantity,
                    // 'sublimation_print_send_waste_quantities' => $wasteQuantities,
                    // 'total_sublimation_print_send_waste_quantity' => $totalWasteQuantity,
                ]);
            }

            DB::commit();

            return redirect()->route('sublimation_print_send_data.index')
                ->withMessage('Sublimation Print/Send data added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(SublimationPrintSend $sublimationPrintSendDatum)
    {
        $sublimationPrintSendDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        return view('backend.library.sublimation_print_send_data.show', compact('sublimationPrintSendDatum', 'allSizes'));
    }

    public function edit(SublimationPrintSend $sublimationPrintSendDatum)
    {
        $sublimationPrintSendDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color', 'productCombination.size');
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

        // Get available quantities for this product combination using the record's PO numbers
        $poNumbers = explode(',', $sublimationPrintSendDatum->po_number);
        $availableQuantitiesResponse = $this->getAvailableSendQuantities($sublimationPrintSendDatum->productCombination, $poNumbers);
        $availableQuantities = (array) $availableQuantitiesResponse->getData()->availableQuantities;

        // Add current quantities to available for editing (since we're editing existing record)
        $sendQuantities = (array) $sublimationPrintSendDatum->sublimation_print_send_quantities;
        foreach ($sendQuantities as $sizeId => $qty) {
            $sizeName = $this->getSizeNameById($sizeId);
            if ($sizeName && isset($availableQuantities[$sizeName])) {
                $availableQuantities[$sizeName] += $qty;
            }
        }

        // Ensure the quantities are arrays, not objects
        $wasteQuantities = (array) $sublimationPrintSendDatum->sublimation_print_send_waste_quantities;

        // Get distinct PO numbers for dropdown
        $sublimationProductIds = ProductCombination::where('sublimation_print', true)->pluck('id');
        $distinctPoNumbers = CuttingData::whereIn('product_combination_id', $sublimationProductIds)
            ->distinct()
            ->pluck('po_number')
            ->filter()
            ->values();

        return view('backend.library.sublimation_print_send_data.edit', [
            'sublimationPrintSendDatum' => $sublimationPrintSendDatum,
            'allSizes' => $allSizes,
            'availableQuantities' => $availableQuantities,
            'sendQuantities' => $sendQuantities,
            'wasteQuantities' => $wasteQuantities,
            'distinctPoNumbers' => $distinctPoNumbers
        ]);
    }

    public function update(Request $request, SublimationPrintSend $sublimationPrintSendDatum)
    {
        $request->validate([
            'date' => 'required|date',
            'sublimation_print_send_quantities.*' => 'nullable|integer|min:0',
            'sublimation_print_send_waste_quantities.*' => 'nullable|integer|min:0',
        ]);

        try {
            $productCombination = $sublimationPrintSendDatum->productCombination;
            $poNumbers = explode(',', $sublimationPrintSendDatum->po_number);
            $availableQuantitiesResponse = $this->getAvailableSendQuantities($productCombination, $poNumbers);
            $availableQuantities = (array) $availableQuantitiesResponse->getData()->availableQuantities;

            // Ensure send and waste quantities are arrays
            $sendQuantities = [];
            $wasteQuantities = [];
            $totalSendQuantity = 0;
            $totalWasteQuantity = 0;
            $errors = [];

            // Process send quantities with validation - FIXED: Use size IDs from request
            foreach ($request->sublimation_print_send_quantities as $sizeId => $quantity) {
                $quantity = (int)$quantity;
                if ($quantity > 0) {
                    // Convert size ID to name for available quantities lookup
                    $sizeName = $this->getSizeNameById($sizeId);

                    // Calculate max allowed (available + current quantity in this record)
                    $currentSendQty = isset($sublimationPrintSendDatum->sublimation_print_send_quantities[$sizeId])
                        ? (int)$sublimationPrintSendDatum->sublimation_print_send_quantities[$sizeId]
                        : 0;

                    $maxAllowed = ($availableQuantities[$sizeName] ?? 0) + $currentSendQty;

                    if ($quantity > $maxAllowed) {
                        $errors["sublimation_print_send_quantities.$sizeId"] = "Quantity for $sizeName exceeds available ($maxAllowed)";
                    } else {
                        $sendQuantities[$sizeId] = $quantity;
                        $totalSendQuantity += $quantity;
                    }
                }
            }

            // Process waste quantities - FIXED: Use size IDs from request
            foreach ($request->sublimation_print_send_waste_quantities as $sizeId => $quantity) {
                $quantity = (int)$quantity;
                if ($quantity > 0) {
                    $wasteQuantities[$sizeId] = $quantity;
                    $totalWasteQuantity += $quantity;
                }
            }

            if (empty($errors)) {
                $sublimationPrintSendDatum->update([
                    'date' => $request->date,
                    'sublimation_print_send_quantities' => $sendQuantities,
                    'total_sublimation_print_send_quantity' => $totalSendQuantity,
                    'sublimation_print_send_waste_quantities' => $wasteQuantities,
                    'total_sublimation_print_send_waste_quantity' => $totalWasteQuantity,
                ]);
                return redirect()->route('sublimation_print_send_data.index')
                    ->withMessage('Sublimation Print/Send data updated successfully.');
            } else {
                return redirect()->back()
                    ->withErrors($errors)
                    ->withInput();
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }


    // Helper method to get size ID by name - FIXED VERSION
    private function getSizeIdByName($sizeName)
    {
        // Handle both string names and numeric IDs
        if (is_numeric($sizeName)) {
            // If it's numeric, treat it as an ID
            $size = Size::find((int)$sizeName);
        } else {
            // If it's a string, search by name
            $size = Size::where('name', $sizeName)->first();
        }
        return $size ? $size->id : null;
    }

    // Helper method to get size name by ID
    private function getSizeNameById($sizeId)
    {
        $size = Size::find($sizeId);
        return $size ? $size->name : null;
    }

    public function destroy(SublimationPrintSend $sublimationPrintSendDatum)
    {
        $sublimationPrintSendDatum->delete();

        return redirect()->route('sublimation_print_send_data.index')
            ->withMessage('Sublimation Print/Send data deleted successfully.');
    }

    public function find(Request $request)
    {
        $poNumbers = $request->input('po_numbers', []);

        if (empty($poNumbers)) {
            return response()->json([]);
        }

        // Get product combinations with sublimation_print = true
        $sublimationProductIds = ProductCombination::where('sublimation_print', true)->pluck('id');

        // Get cutting data for the selected PO numbers and sublimation products
        $cuttingData = CuttingData::whereIn('po_number', $poNumbers)
            ->whereIn('product_combination_id', $sublimationProductIds)
            ->with(['productCombination.style', 'productCombination.color', 'productCombination.size'])
            ->get()
            ->groupBy('po_number');

        $result = [];

        foreach ($cuttingData as $poNumber => $cuttingRecords) {
            $result[$poNumber] = [];

            // Group cutting records by product_combination_id
            $groupedByCombination = $cuttingRecords->groupBy('product_combination_id');

            foreach ($groupedByCombination as $combinationId => $records) {
                // Get the product combination from the first record
                $productCombination = $records->first()->productCombination;

                if (!$productCombination) {
                    continue;
                }

                // Pass the specific PO number to get available quantities
                $availableQuantities = $this->getAvailableSendQuantities($productCombination, [$poNumber])->getData()->availableQuantities;

                $result[$poNumber][] = [
                    'combination_id' => $productCombination->id,
                    'style' => $productCombination->style->name,
                    'color' => $productCombination->color->name,
                    'available_quantities' => $availableQuantities,
                    'size_ids' => $productCombination->sizes->pluck('id')->toArray()
                ];
            }
        }

        return response()->json($result);
    }

    public function getAvailableSendQuantities(ProductCombination $productCombination, $poNumbers = [])
    {
        $sizes = Size::where('is_active', 1)->get();
        $availableQuantities = [];

        // Create a mapping of size IDs to size names
        $sizeIdToName = [];
        foreach ($sizes as $size) {
            $sizeIdToName[$size->id] = $size->name;
        }

        // Sum cut quantities per size using original case, with PO number filter
        $cutQuantitiesQuery = CuttingData::where('product_combination_id', $productCombination->id);

        if (!empty($poNumbers)) {
            $cutQuantitiesQuery->whereIn('po_number', $poNumbers);
        }

        $cutQuantities = $cutQuantitiesQuery->get()
            ->pluck('cut_quantities')
            ->reduce(function ($carry, $quantities) use ($sizeIdToName) {
                foreach ($quantities as $key => $qty) {
                    // Handle both numeric keys (size IDs) and string keys (size names)
                    $sizeName = is_numeric($key) ? ($sizeIdToName[$key] ?? null) : $key;

                    if ($sizeName && $qty > 0) {
                        $carry[$sizeName] = ($carry[$sizeName] ?? 0) + $qty;
                    }
                }
                return $carry;
            }, []);

        // Sum sent quantities per size using original case, with PO number filter
        $sentQuantitiesQuery = SublimationPrintSend::where('product_combination_id', $productCombination->id);

        if (!empty($poNumbers)) {
            $sentQuantitiesQuery->where(function ($query) use ($poNumbers) {
                foreach ($poNumbers as $po) {
                    $query->orWhere('po_number', 'like', '%' . $po . '%');
                }
            });
        }

        $sentQuantities = $sentQuantitiesQuery->get()
            ->pluck('sublimation_print_send_quantities')
            ->reduce(function ($carry, $quantities) use ($sizeIdToName) {
                foreach ($quantities as $key => $qty) {
                    // Handle both numeric keys (size IDs) and string keys (size names)
                    $sizeName = is_numeric($key) ? ($sizeIdToName[$key] ?? null) : $key;

                    if ($sizeName && $qty > 0) {
                        $carry[$sizeName] = ($carry[$sizeName] ?? 0) + $qty;
                    }
                }
                return $carry;
            }, []);

        // Calculate available quantities
        foreach ($sizes as $size) {
            $sizeName = $size->name;
            $cut = $cutQuantities[$sizeName] ?? 0;
            $sent = $sentQuantities[$sizeName] ?? 0;
            $availableQuantities[$sizeName] = max(0, $cut - $sent);
        }

        return response()->json([
            'availableQuantities' => $availableQuantities,
            'sizes' => $sizes
        ]);
    }

    // Reports

    public function totalPrintEmbSendReport(Request $request)
    {
        $query = SublimationPrintSend::with('productCombination.style', 'productCombination.color');

        // Apply filters
        $styleIds = $request->input('style_id', []);
        $colorIds = $request->input('color_id', []);
        $poNumbers = $request->input('po_number', []);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');

        // Style filter
        if (!empty($styleIds)) {
            $query->whereHas('productCombination', function ($q) use ($styleIds) {
                $q->whereIn('style_id', $styleIds);
            });
        }

        // Color filter
        if (!empty($colorIds)) {
            $query->whereHas('productCombination', function ($q) use ($colorIds) {
                $q->whereIn('color_id', $colorIds);
            });
        }

        // PO Number filter
        if (!empty($poNumbers)) {
            $query->whereIn('po_number', $poNumbers);
        }

        // Date filter
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', '%' . $search . '%')
                    ->orWhereHas('productCombination.style', function ($q2) use ($search) {
                        $q2->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('productCombination.color', function ($q2) use ($search) {
                        $q2->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $printSendData = $query->get();
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $reportData = [];

        // Create a mapping of size IDs to size names
        $sizeIdToName = [];
        foreach ($allSizes as $size) {
            $sizeIdToName[$size->id] = $size->name;
        }

        foreach ($printSendData as $data) {
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

            // Convert size IDs to names for display
            foreach ($data->sublimation_print_send_quantities as $sizeId => $qty) {
                $sizeName = $sizeIdToName[$sizeId] ?? null;
                if ($sizeName) {
                    $normalized = strtolower($sizeName);
                    if (array_key_exists($normalized, $reportData[$key]['sizes'])) {
                        $reportData[$key]['sizes'][$normalized] += $qty;
                    }
                }
            }
            $reportData[$key]['total'] += $data->total_sublimation_print_send_quantity;
        }

        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = SublimationPrintSend::distinct()->pluck('po_number')->filter()->values();

        return view('backend.library.sublimation_print_send_data.reports.total', [
            'reportData' => array_values($reportData),
            'allSizes' => $allSizes,
            'allStyles' => $allStyles,
            'allColors' => $allColors,
            'distinctPoNumbers' => $distinctPoNumbers
        ]);
    }

    // public function wipReport(Request $request)
    // {
    //     // Get filter parameters
    //     $styleIds = $request->input('style_id', []);
    //     $colorIds = $request->input('color_id', []);
    //     $poNumbers = $request->input('po_number', []);
    //     $startDate = $request->input('start_date');
    //     $endDate = $request->input('end_date');
    //     $search = $request->input('search');

    //     // Base query for product combinations with sublimation_print = true
    //     $combinationsQuery = ProductCombination::where('sublimation_print', true)
    //         ->with('style', 'color');

    //     // Apply style and color filters
    //     if (!empty($styleIds)) {
    //         $combinationsQuery->whereIn('style_id', $styleIds);
    //     }

    //     if (!empty($colorIds)) {
    //         $combinationsQuery->whereIn('color_id', $colorIds);
    //     }

    //     $combinations = $combinationsQuery->get();
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $wipData = [];

    //     // Create a mapping of size IDs to size names
    //     $sizeIdToName = [];
    //     foreach ($allSizes as $size) {
    //         $sizeIdToName[$size->id] = $size->name;
    //     }

    //     foreach ($combinations as $pc) {
    //         // Apply filters to cutting data
    //         $cuttingDataQuery = CuttingData::where('product_combination_id', $pc->id);
    //         $sendDataQuery = SublimationPrintSend::where('product_combination_id', $pc->id);

    //         // PO Number filter
    //         if (!empty($poNumbers)) {
    //             $cuttingDataQuery->whereIn('po_number', $poNumbers);
    //             $sendDataQuery->whereIn('po_number', $poNumbers);
    //         }

    //         // Date filter
    //         if ($startDate && $endDate) {
    //             $cuttingDataQuery->whereBetween('created_at', [$startDate, $endDate]);
    //             $sendDataQuery->whereBetween('date', [$startDate, $endDate]);
    //         }

    //         $totalCut = $cuttingDataQuery->sum('total_cut_quantity');
    //         $totalSent = $sendDataQuery->sum('total_sublimation_print_send_quantity');

    //         // Skip if no cutting data matches the filters
    //         if ($totalCut == 0) continue;

    //         // Apply search filter
    //         if ($search) {
    //             $styleName = strtolower($pc->style->name);
    //             $colorName = strtolower($pc->color->name);
    //             $searchTerm = strtolower($search);

    //             if (
    //                 strpos($styleName, $searchTerm) === false &&
    //                 strpos($colorName, $searchTerm) === false
    //             ) {
    //                 continue;
    //             }
    //         }

    //         if ($totalCut > $totalSent) {
    //             $key = $pc->style->name . '-' . $pc->color->name;

    //             if (!isset($wipData[$key])) {
    //                 $wipData[$key] = [
    //                     'style' => $pc->style->name,
    //                     'color' => $pc->color->name,
    //                     'sizes' => [],
    //                     'total_cut' => 0,
    //                     'total_sent' => 0,
    //                     'waiting' => 0
    //                 ];

    //                 // Initialize sizes
    //                 foreach ($allSizes as $size) {
    //                     $wipData[$key]['sizes'][$size->name] = [
    //                         'cut' => 0,
    //                         'sent' => 0,
    //                         'waiting' => 0
    //                     ];
    //                 }
    //             }

    //             $wipData[$key]['total_cut'] += $totalCut;
    //             $wipData[$key]['total_sent'] += $totalSent;
    //             $wipData[$key]['waiting'] += ($totalCut - $totalSent);

    //             // Aggregate size quantities for cutting data
    //             $cuttingData = $cuttingDataQuery->get();
    //             foreach ($cuttingData as $cd) {
    //                 foreach ($cd->cut_quantities as $size => $qty) {
    //                     if (isset($wipData[$key]['sizes'][$size])) {
    //                         $wipData[$key]['sizes'][$size]['cut'] += $qty;
    //                     }
    //                 }
    //             }

    //             // Aggregate size quantities for send data
    //             $sendData = $sendDataQuery->get();
    //             foreach ($sendData as $sd) {
    //                 foreach ($sd->sublimation_print_send_quantities as $sizeId => $qty) {
    //                     $sizeName = $sizeIdToName[$sizeId] ?? null;
    //                     if ($sizeName && isset($wipData[$key]['sizes'][$sizeName])) {
    //                         $wipData[$key]['sizes'][$sizeName]['sent'] += $qty;
    //                     }
    //                 }
    //             }

    //             // Calculate waiting per size
    //             foreach ($wipData[$key]['sizes'] as $size => $data) {
    //                 $wipData[$key]['sizes'][$size]['waiting'] =
    //                     $data['cut'] - $data['sent'];
    //             }
    //         }
    //     }

    //     $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
    //     $allColors = Color::where('is_active', 1)->orderBy('name')->get();
    //     $distinctPoNumbers = CuttingData::distinct()->pluck('po_number')->filter()->values();

    //     return view('backend.library.sublimation_print_send_data.reports.wip', [
    //         'wipData' => array_values($wipData),
    //         'allSizes' => $allSizes,
    //         'allStyles' => $allStyles,
    //         'allColors' => $allColors,
    //         'distinctPoNumbers' => $distinctPoNumbers
    //     ]);
    // }

    public function wipReport(Request $request)
    {
        // Get filter parameters
        $styleIds = $request->input('style_id', []);
        $colorIds = $request->input('color_id', []);
        $poNumbers = $request->input('po_number', []);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');

        // Base query for product combinations with sublimation_print = true
        $combinationsQuery = ProductCombination::where('sublimation_print', true)
            ->with('style', 'color');

        // Apply style and color filters
        if (!empty($styleIds)) {
            $combinationsQuery->whereIn('style_id', $styleIds);
        }

        if (!empty($colorIds)) {
            $combinationsQuery->whereIn('color_id', $colorIds);
        }

        $combinations = $combinationsQuery->get();
        $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
        $wipData = [];

        // Create mappings for size conversion
        $sizeIdToName = [];
        foreach ($allSizes as $size) {
            $sizeIdToName[$size->id] = $size->name;
        }

        foreach ($combinations as $pc) {
            // Build base queries
            $cuttingDataQuery = CuttingData::where('product_combination_id', $pc->id);
            $sendDataQuery = SublimationPrintSend::where('product_combination_id', $pc->id);

            // PO Number filter
            if (!empty($poNumbers)) {
                $cuttingDataQuery->whereIn('po_number', $poNumbers);
                $sendDataQuery->whereIn('po_number', $poNumbers);
            }

            // Date filter - use 'date' field for both tables
            if ($startDate && $endDate) {
                $cuttingDataQuery->whereBetween('date', [$startDate, $endDate]);
                $sendDataQuery->whereBetween('date', [$startDate, $endDate]);
            }

            // Get the data first, then calculate totals
            $cuttingData = $cuttingDataQuery->get();
            $sendData = $sendDataQuery->get();

            $totalCut = $cuttingData->sum('total_cut_quantity');
            $totalSent = $sendData->sum('total_sublimation_print_send_quantity');

            // Skip if no cutting data matches the filters
            if ($totalCut == 0) continue;

            // Apply search filter
            if ($search) {
                $styleName = strtolower($pc->style->name);
                $colorName = strtolower($pc->color->name);
                $searchTerm = strtolower($search);

                if (
                    strpos($styleName, $searchTerm) === false &&
                    strpos($colorName, $searchTerm) === false
                ) {
                    continue;
                }
            }

            if ($totalCut > $totalSent) {
                $key = $pc->style->name . '-' . $pc->color->name;

                if (!isset($wipData[$key])) {
                    $wipData[$key] = [
                        'style' => $pc->style->name,
                        'color' => $pc->color->name,
                        'sizes' => [],
                        'total_cut' => 0,
                        'total_sent' => 0,
                        'waiting' => 0
                    ];

                    // Initialize sizes with both ID and name references
                    foreach ($allSizes as $size) {
                        $wipData[$key]['sizes'][$size->name] = [
                            'cut' => 0,
                            'sent' => 0,
                            'waiting' => 0
                        ];
                    }
                }

                $wipData[$key]['total_cut'] += $totalCut;
                $wipData[$key]['total_sent'] += $totalSent;
                $wipData[$key]['waiting'] += ($totalCut - $totalSent);

                // Aggregate size quantities for cutting data (uses size IDs)
                foreach ($cuttingData as $cd) {
                    $cutQuantities = $cd->cut_quantities;
                    foreach ($cutQuantities as $sizeId => $qty) {
                        $sizeName = $sizeIdToName[$sizeId] ?? null;
                        if ($sizeName && isset($wipData[$key]['sizes'][$sizeName])) {
                            $wipData[$key]['sizes'][$sizeName]['cut'] += $qty;
                        }
                    }
                }

                // Aggregate size quantities for send data (uses size IDs)
                foreach ($sendData as $sd) {
                    $sendQuantities = $sd->sublimation_print_send_quantities;
                    foreach ($sendQuantities as $sizeId => $qty) {
                        $sizeName = $sizeIdToName[$sizeId] ?? null;
                        if ($sizeName && isset($wipData[$key]['sizes'][$sizeName])) {
                            $wipData[$key]['sizes'][$sizeName]['sent'] += $qty;
                        }
                    }
                }

                // Calculate waiting per size
                foreach ($wipData[$key]['sizes'] as $size => $data) {
                    $wipData[$key]['sizes'][$size]['waiting'] =
                        $wipData[$key]['sizes'][$size]['cut'] - $wipData[$key]['sizes'][$size]['sent'];
                }
            }
        }

        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = CuttingData::distinct()->pluck('po_number')->filter()->values();

        return view('backend.library.sublimation_print_send_data.reports.wip', [
            'wipData' => array_values($wipData),
            'allSizes' => $allSizes,
            'allStyles' => $allStyles,
            'allColors' => $allColors,
            'distinctPoNumbers' => $distinctPoNumbers
        ]);
    }

    public function readyToInputReport(Request $request)
    {
        $readyData = [];

        // Get filter parameters
        $styleIds = $request->input('style_id', []);
        $colorIds = $request->input('color_id', []);
        $poNumbers = $request->input('po_number', []);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');

        // Base query for non-sublimation combinations
        $nonSublimationCombinationsQuery = ProductCombination::where('sublimation_print', false)
            ->with(['style', 'color']);

        // Base query for sublimation combinations
        $sublimationCombinationsQuery = ProductCombination::where('sublimation_print', true)
            ->with(['style', 'color']);

        // Apply filters if provided
        if (!empty($styleIds)) {
            $nonSublimationCombinationsQuery->whereIn('style_id', $styleIds);
            $sublimationCombinationsQuery->whereIn('style_id', $styleIds);
        }

        if (!empty($colorIds)) {
            $nonSublimationCombinationsQuery->whereIn('color_id', $colorIds);
            $sublimationCombinationsQuery->whereIn('color_id', $colorIds);
        }

        // Get the combinations
        $nonSublimationCombinations = $nonSublimationCombinationsQuery->get();
        $sublimationCombinations = $sublimationCombinationsQuery->get();

        // Process non-sublimation combinations (ready for next step without printing)
        foreach ($nonSublimationCombinations as $pc) {
            $cuttingDataQuery = CuttingData::where('product_combination_id', $pc->id);

            // Apply PO number filter
            if (!empty($poNumbers)) {
                $cuttingDataQuery->whereIn('po_number', $poNumbers);
            }

            // Apply date filter
            if ($startDate && $endDate) {
                $cuttingDataQuery->whereBetween('date', [$startDate, $endDate]);
            }

            $totalCut = $cuttingDataQuery->sum('total_cut_quantity');

            // Skip if no cutting data matches the filters
            if ($totalCut == 0) continue;

            // Apply search filter
            if ($search) {
                $styleName = strtolower($pc->style->name);
                $colorName = strtolower($pc->color->name);
                $searchTerm = strtolower($search);

                if (
                    strpos($styleName, $searchTerm) === false &&
                    strpos($colorName, $searchTerm) === false
                ) {
                    continue;
                }
            }

            $readyData[] = [
                'style' => $pc->style->name,
                'color' => $pc->color->name,
                'type' => 'No Sublimation Print Needed',
                'total_cut' => $totalCut,
                'total_sent' => 0,
                'status' => 'Ready for Next Step'
            ];
        }

        // Process sublimation combinations (where printing is completed)
        foreach ($sublimationCombinations as $pc) {
            $cuttingDataQuery = CuttingData::where('product_combination_id', $pc->id);
            $sendDataQuery = SublimationPrintSend::where('product_combination_id', $pc->id);

            // Apply PO number filter
            if (!empty($poNumbers)) {
                $cuttingDataQuery->whereIn('po_number', $poNumbers);
                $sendDataQuery->whereIn('po_number', $poNumbers);
            }

            // Apply date filter
            if ($startDate && $endDate) {
                $cuttingDataQuery->whereBetween('date', [$startDate, $endDate]);
                $sendDataQuery->whereBetween('date', [$startDate, $endDate]);
            }

            $totalCut = $cuttingDataQuery->sum('total_cut_quantity');
            $totalSent = $sendDataQuery->sum('total_sublimation_print_send_quantity');

            // Skip if no cutting data matches the filters
            if ($totalCut == 0) continue;

            // Apply search filter
            if ($search) {
                $styleName = strtolower($pc->style->name);
                $colorName = strtolower($pc->color->name);
                $searchTerm = strtolower($search);

                if (
                    strpos($styleName, $searchTerm) === false &&
                    strpos($colorName, $searchTerm) === false
                ) {
                    continue;
                }
            }

            // DEBUG: Check what's happening with this combination
            // if ($pc->style->name === 'LNSD_6044' && $pc->color->name === 'Grey Marl') {
            //     \log::debug('LNSD_6044 Grey Marl Debug', [
            //         'total_cut' => $totalCut,
            //         'total_sent' => $totalSent,
            //         'cutting_count' => $cuttingDataQuery->count(),
            //         'send_count' => $sendDataQuery->count(),
            //         'cutting_data' => $cuttingDataQuery->get()->toArray(),
            //         'send_data' => $sendDataQuery->get()->toArray()
            //     ]);
            // }

            // Check if all cutting has been sent for sublimation
            if ($totalSent >= $totalCut && $totalSent > 0) {
                $readyData[] = [
                    'style' => $pc->style->name,
                    'color' => $pc->color->name,
                    'type' => 'Sublimation Print Completed',
                    'total_cut' => $totalCut,
                    'total_sent' => $totalSent,
                    'status' => 'Ready for Next Step'
                ];
            }
        }

        $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
        $allColors = Color::where('is_active', 1)->orderBy('name')->get();
        $distinctPoNumbers = CuttingData::distinct()->pluck('po_number')->filter()->values();

        return view('backend.library.sublimation_print_send_data.reports.ready', compact('readyData', 'allStyles', 'allColors', 'distinctPoNumbers'));
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

        $sentQuantities = SublimationPrintSend::where('product_combination_id', $product_combination_id)
            ->get()
            ->flatMap(function ($record) {
                return collect($record->sublimation_print_send_quantities)
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


    // public function index(Request $request)
    // {
    //     $query = SublimationPrintSend::with('productCombination.buyer', 'productCombination.style', 'productCombination.color', 'productCombination.size');

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

    //     $printSendData = $query->orderBy('date', 'desc')->paginate(10);
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //     return view('backend.library.sublimation_print_send_data.index', compact('printSendData', 'allSizes'));
    // }

    // public function create()
    // {
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //     // Get distinct PO numbers from CuttingData where product combination has sublimation_print = true
    //     $sublimationProductIds = ProductCombination::where('sublimation_print', true)->pluck('id');
    //     $distinctPoNumbers = CuttingData::whereIn('product_combination_id', $sublimationProductIds)
    //         ->distinct()
    //         ->pluck('po_number')
    //         ->filter()
    //         ->values();

    //     return view('backend.library.sublimation_print_send_data.create', compact('distinctPoNumbers', 'allSizes'));
    // }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'date' => 'required|date',
    //         'po_number' => 'required|array',
    //         'po_number.*' => 'required|string',
    //         'old_order' => 'required|in:yes,no',
    //         'rows' => 'required|array',
    //         'rows.*.product_combination_id' => 'required|exists:product_combinations,id',
    //         'rows.*.sublimation_print_send_quantities.*' => 'nullable|integer|min:0',
    //         'rows.*.sublimation_print_send_waste_quantities.*' => 'nullable|integer|min:0',
    //     ]);

    //     try {
    //         DB::beginTransaction();

    //         foreach ($request->rows as $row) {
    //             $sendQuantities = [];
    //             $wasteQuantities = [];
    //             $totalSendQuantity = 0;
    //             $totalWasteQuantity = 0;

    //             // if any row has no send or waste quantities, skip it and if any size has zero quantity, skip that size
    //             if (empty(array_filter($row['sublimation_print_send_quantities'])) &&
    //                 empty(array_filter($row['sublimation_print_send_waste_quantities']))) {
    //                 continue;
    //             }

    //             // Process send quantities
    //             foreach ($row['sublimation_print_send_quantities'] as $sizeId => $quantity) {
    //                 if ($quantity > 0) {
    //                     $size = Size::find($sizeId);
    //                     $sendQuantities[$size->id] = (int)$quantity;
    //                     $totalSendQuantity += (int)$quantity;
    //                 }
    //             }

    //             // Process waste quantities
    //             foreach ($row['sublimation_print_send_waste_quantities'] as $sizeId => $quantity) {
    //                 if ($quantity > 0) {
    //                     $size = Size::find($sizeId);
    //                     $wasteQuantities[$size->id] = (int)$quantity;
    //                     $totalWasteQuantity += (int)$quantity;
    //                 }
    //             }

    //             SublimationPrintSend::create([
    //                 'date' => $request->date,
    //                 'product_combination_id' => $row['product_combination_id'],
    //                 'po_number' => implode(',', $request->po_number),
    //                 'old_order' => $request->old_order,
    //                 'sublimation_print_send_quantities' => $sendQuantities,
    //                 'total_sublimation_print_send_quantity' => $totalSendQuantity,
    //                 'sublimation_print_send_waste_quantities' => $wasteQuantities,
    //                 'total_sublimation_print_send_waste_quantity' => $totalWasteQuantity,
    //             ]);
    //         }

    //         DB::commit();

    //         return redirect()->route('sublimation_print_send_data.index')
    //             ->withMessage( 'Sublimation Print/Send data added successfully.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return redirect()->back()
    //             ->with('error', 'Error occurred: ' . $e->getMessage())
    //             ->withInput();
    //     }
    // }

    // public function show(SublimationPrintSend $sublimationPrintSendDatum)
    // {
    //     $sublimationPrintSendDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color');
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //     return view('backend.library.sublimation_print_send_data.show', compact('sublimationPrintSendDatum', 'allSizes'));
    // }

    // // public function edit(SublimationPrintSend $sublimationPrintSendDatum)
    // // {
    // //     $sublimationPrintSendDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color', 'productCombination.size');
    // //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();



    // //     // Get available quantities for this product combination
    // //     $availableQuantitiesResponse = $this->getAvailableSendQuantities($sublimationPrintSendDatum->productCombination);
    // //     $availableQuantities = (array) $availableQuantitiesResponse->getData()->availableQuantities;
    // //     $availableQuantities = array_filter($availableQuantities);

    // //     //filter the available quantities to remove zero quantities from allSizes
    // //     $allSizes = $allSizes->filter(function ($size) use ($availableQuantities) {
    // //         return isset($availableQuantities[$size->name]);
    // //     });

    // //     // Ensure the quantities are arrays, not objects
    // //     $sendQuantities = (array) $sublimationPrintSendDatum->sublimation_print_send_quantities;
    // //     $wasteQuantities = (array) $sublimationPrintSendDatum->sublimation_print_send_waste_quantities;

    // //     return view('backend.library.sublimation_print_send_data.edit', [
    // //         'sublimationPrintSendDatum' => $sublimationPrintSendDatum,
    // //         'allSizes' => $allSizes,
    // //         'availableQuantities' => $availableQuantities,
    // //         'sendQuantities' => $sendQuantities,
    // //         'wasteQuantities' => $wasteQuantities
    // //     ]);
    // // }

    // // public function update(Request $request, SublimationPrintSend $sublimationPrintSendDatum)
    // // {
    // //     $request->validate([
    // //         'date' => 'required|date',
    // //         'sublimation_print_send_quantities.*' => 'nullable|integer|min:0',
    // //         'sublimation_print_send_waste_quantities.*' => 'nullable|integer|min:0',
    // //     ]);

    // //     try {
    // //         $productCombination = $sublimationPrintSendDatum->productCombination;
    // //         $availableQuantitiesResponse = $this->getAvailableSendQuantities($productCombination);
    // //         $availableQuantities = (array) $availableQuantitiesResponse->getData()->availableQuantities;

    // //         // Ensure send and waste quantities are arrays
    // //         $sendQuantities = [];
    // //         $wasteQuantities = [];
    // //         $totalSendQuantity = 0;
    // //         $totalWasteQuantity = 0;
    // //         $errors = [];

    // //         // Process send quantities with validation
    // //         foreach ($request->sublimation_print_send_quantities as $sizeName => $quantity) {
    // //             $quantity = (int)$quantity;
    // //             if ($quantity > 0) {
    // //                 // Calculate max allowed (available + current quantity in this record)
    // //                 $currentSendQty = isset($sublimationPrintSendDatum->sublimation_print_send_quantities[$sizeName])
    // //                     ? (int)$sublimationPrintSendDatum->sublimation_print_send_quantities[$sizeName]
    // //                     : 0;

    // //                 $maxAllowed = ($availableQuantities[$sizeName] ?? 0) + $currentSendQty;

    // //                 if ($quantity > $maxAllowed) {
    // //                     $errors["sublimation_print_send_quantities.$sizeName"] = "Quantity for $sizeName exceeds available ($maxAllowed)";
    // //                 } else {
    // //                     // Convert size name to ID for storage
    // //                     $sizeId = $this->getSizeIdByName($sizeName);
    // //                     if ($sizeId) {
    // //                         $sendQuantities[$sizeId] = $quantity;
    // //                         $totalSendQuantity += $quantity;
    // //                     }
    // //                 }
    // //             }
    // //         }

    // //         // Process waste quantities
    // //         foreach ($request->sublimation_print_send_waste_quantities as $sizeName => $quantity) {
    // //             $quantity = (int)$quantity;
    // //             if ($quantity > 0) {
    // //                 // Convert size name to ID for storage
    // //                 $sizeId = $this->getSizeIdByName($sizeName);
    // //                 if ($sizeId) {
    // //                     $wasteQuantities[$sizeId] = $quantity;
    // //                     $totalWasteQuantity += $quantity;
    // //                 }
    // //             }
    // //         }

    // //         if (!empty($errors)) {
    // //             return redirect()->back()->withErrors($errors)->withInput();
    // //         }

    // //         $sublimationPrintSendDatum->update([
    // //             'date' => $request->date,
    // //             'sublimation_print_send_quantities' => $sendQuantities,
    // //             'total_sublimation_print_send_quantity' => $totalSendQuantity,
    // //             'sublimation_print_send_waste_quantities' => $wasteQuantities,
    // //             'total_sublimation_print_send_waste_quantity' => $totalWasteQuantity,
    // //         ]);

    // //         return redirect()->route('sublimation_print_send_data.index')
    // //             ->withMessage( 'Sublimation Print/Send data updated successfully.');
    // //     } catch (\Exception $e) {
    // //         return redirect()->back()
    // //             ->with('error', 'Error occurred: ' . $e->getMessage())
    // //             ->withInput();
    // //     }
    // // }


    // public function edit(SublimationPrintSend $sublimationPrintSendDatum)
    // {
    //     $sublimationPrintSendDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color', 'productCombination.size');
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //     // Get available quantities for this product combination
    //     $availableQuantitiesResponse = $this->getAvailableSendQuantities($sublimationPrintSendDatum->productCombination);
    //     $availableQuantities = (array) $availableQuantitiesResponse->getData()->availableQuantities;

    //     // Add current quantities to available for editing (since we're editing existing record)
    //     $sendQuantities = (array) $sublimationPrintSendDatum->sublimation_print_send_quantities;
    //     foreach ($sendQuantities as $sizeId => $qty) {
    //         $sizeName = $this->getSizeNameById($sizeId);
    //         if ($sizeName && isset($availableQuantities[$sizeName])) {
    //             $availableQuantities[$sizeName] += $qty;
    //         }
    //     }

    //     // Ensure the quantities are arrays, not objects
    //     $wasteQuantities = (array) $sublimationPrintSendDatum->sublimation_print_send_waste_quantities;

    //     // Get distinct PO numbers for dropdown
    //     $sublimationProductIds = ProductCombination::where('sublimation_print', true)->pluck('id');
    //     $distinctPoNumbers = CuttingData::whereIn('product_combination_id', $sublimationProductIds)
    //         ->distinct()
    //         ->pluck('po_number')
    //         ->filter()
    //         ->values();

    //     return view('backend.library.sublimation_print_send_data.edit', [
    //         'sublimationPrintSendDatum' => $sublimationPrintSendDatum,
    //         'allSizes' => $allSizes,
    //         'availableQuantities' => $availableQuantities,
    //         'sendQuantities' => $sendQuantities,
    //         'wasteQuantities' => $wasteQuantities,
    //         'distinctPoNumbers' => $distinctPoNumbers
    //     ]);
    // }

    // public function update(Request $request, SublimationPrintSend $sublimationPrintSendDatum)
    // {
    //     $request->validate([
    //         'date' => 'required|date',
    //         'sublimation_print_send_quantities' => 'required|array',
    //         'sublimation_print_send_quantities.*' => 'nullable|integer|min:0',
    //     ]);

    //     try {
    //         DB::beginTransaction();

    //         $productCombination = $sublimationPrintSendDatum->productCombination;

    //         // Get available quantities without the current record's quantities
    //         $availableQuantitiesResponse = $this->getAvailableSendQuantities($productCombination);
    //         $availableQuantities = (array) $availableQuantitiesResponse->getData()->availableQuantities;

    //         // Process send quantities with validation
    //         $sendQuantities = [];
    //         $wasteQuantities = [];
    //         $totalSendQuantity = 0;
    //         $totalWasteQuantity = 0;
    //         $errors = [];

    //         foreach ($request->sublimation_print_send_quantities as $sizeName => $quantity) {
    //             $quantity = (int)$quantity;
    //             if ($quantity > 0) {
    //                 $maxAllowed = $availableQuantities[$sizeName] ?? 0;

    //                 // Add back the current quantity from this record
    //                 $currentSizeId = $this->getSizeIdByName($sizeName);
    //                 $currentQty = isset($sublimationPrintSendDatum->sublimation_print_send_quantities[$currentSizeId])
    //                     ? (int)$sublimationPrintSendDatum->sublimation_print_send_quantities[$currentSizeId]
    //                     : 0;

    //                 $maxAllowed += $currentQty;

    //                 if ($quantity > $maxAllowed) {
    //                     $errors["sublimation_print_send_quantities.$sizeName"] = "Quantity for $sizeName exceeds available ($maxAllowed)";
    //                 } else {
    //                     $sizeId = $this->getSizeIdByName($sizeName);
    //                     if ($sizeId) {
    //                         $sendQuantities[$sizeId] = $quantity;
    //                         $totalSendQuantity += $quantity;
    //                     }
    //                 }
    //             }
    //         }

    //         // Process waste quantities
    //         foreach ($request->sublimation_print_send_waste_quantities as $sizeName => $quantity) {
    //             $quantity = (int)$quantity;
    //             if ($quantity > 0) {
    //                 $sizeId = $this->getSizeIdByName($sizeName);
    //                 if ($sizeId) {
    //                     $wasteQuantities[$sizeId] = $quantity;
    //                     $totalWasteQuantity += $quantity;
    //                 }
    //             }
    //         }

    //         if (!empty($errors)) {
    //             return redirect()->back()->withErrors($errors)->withInput();
    //         }

    //         $sublimationPrintSendDatum->update([
    //             'date' => $request->date,
    //             'po_number' => implode(',', $request->po_number),
    //             'old_order' => $request->old_order,
    //             'sublimation_print_send_quantities' => $sendQuantities,
    //             'total_sublimation_print_send_quantity' => $totalSendQuantity,
    //             'sublimation_print_send_waste_quantities' => $wasteQuantities,
    //             'total_sublimation_print_send_waste_quantity' => $totalWasteQuantity,
    //         ]);

    //         DB::commit();

    //         return redirect()->route('sublimation_print_send_data.index')
    //             ->withMessage( 'Sublimation Print/Send data updated successfully.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return redirect()->back()
    //             ->with('error', 'Error occurred: ' . $e->getMessage())
    //             ->withInput();
    //     }
    // }

    // // Helper method to get size ID by name
    // private function getSizeIdByName($sizeName)
    // {
    //     // Convert numeric strings to integers if they represent IDs
    //     if (is_numeric($sizeName)) {
    //         $size = Size::find((int)$sizeName);
    //     } else {
    //         $size = Size::where('name', $sizeName)->first();
    //     }
    //     return $size ? $size->id : null;
    // }

    // // Helper method to get size name by ID
    // private function getSizeNameById($sizeId)
    // {
    //     $size = Size::find($sizeId);
    //     return $size ? $size->name : null;
    // }

    // public function destroy(SublimationPrintSend $sublimationPrintSendDatum)
    // {
    //     $sublimationPrintSendDatum->delete();

    //     return redirect()->route('sublimation_print_send_data.index')
    //         ->withMessage( 'Sublimation Print/Send data deleted successfully.');
    // }

    // public function find(Request $request)
    // {
    //     $poNumbers = $request->input('po_numbers', []);

    //     if (empty($poNumbers)) {
    //         return response()->json([]);
    //     }

    //     // Get product combinations with sublimation_print = true
    //     $sublimationProductIds = ProductCombination::where('sublimation_print', true)->pluck('id');

    //     // Get cutting data for the selected PO numbers and sublimation products
    //     $cuttingData = CuttingData::whereIn('po_number', $poNumbers)
    //         ->whereIn('product_combination_id', $sublimationProductIds)
    //         ->with(['productCombination.style', 'productCombination.color', 'productCombination.size'])
    //         ->get()
    //         ->groupBy('po_number');

    //     $result = [];

    //     foreach ($cuttingData as $poNumber => $cuttingRecords) {
    //         $result[$poNumber] = [];

    //         // Group cutting records by product_combination_id
    //         $groupedByCombination = $cuttingRecords->groupBy('product_combination_id');

    //         foreach ($groupedByCombination as $combinationId => $records) {
    //             // Get the product combination from the first record
    //             $productCombination = $records->first()->productCombination;

    //             if (!$productCombination) {
    //                 continue;
    //             }

    //             $availableQuantities = $this->getAvailableSendQuantities($productCombination)->getData()->availableQuantities;

    //             $result[$poNumber][] = [
    //                 'combination_id' => $productCombination->id,
    //                 'style' => $productCombination->style->name,
    //                 'color' => $productCombination->color->name,
    //                 'available_quantities' => $availableQuantities,
    //                 'size_ids' => $productCombination->sizes->pluck('id')->toArray()
    //             ];
    //         }
    //     }

    //     return response()->json($result);
    // }

    // public function getAvailableSendQuantities(ProductCombination $productCombination)
    // {
    //     $sizes = Size::where('is_active', 1)->get();
    //     $availableQuantities = [];

    //     // Create a mapping of size IDs to size names
    //     $sizeIdToName = [];
    //     foreach ($sizes as $size) {
    //         $sizeIdToName[$size->id] = $size->name;
    //     }

    //     // Sum cut quantities per size using original case
    //     $cutQuantities = CuttingData::where('product_combination_id', $productCombination->id)
    //         ->get()
    //         ->pluck('cut_quantities')
    //         ->reduce(function ($carry, $quantities) use ($sizeIdToName) {
    //             foreach ($quantities as $key => $qty) {
    //                 // Handle both numeric keys (size IDs) and string keys (size names)
    //                 $sizeName = is_numeric($key) ? ($sizeIdToName[$key] ?? null) : $key;

    //                 if ($sizeName && $qty > 0) {
    //                     $carry[$sizeName] = ($carry[$sizeName] ?? 0) + $qty;
    //                 }
    //             }
    //             return $carry;
    //         }, []);

    //     // Sum sent quantities per size using original case
    //     $sentQuantities = SublimationPrintSend::where('product_combination_id', $productCombination->id)
    //         ->get()
    //         ->pluck('sublimation_print_send_quantities')
    //         ->reduce(function ($carry, $quantities) use ($sizeIdToName) {
    //             foreach ($quantities as $key => $qty) {
    //                 // Handle both numeric keys (size IDs) and string keys (size names)
    //                 $sizeName = is_numeric($key) ? ($sizeIdToName[$key] ?? null) : $key;

    //                 if ($sizeName && $qty > 0) {
    //                     $carry[$sizeName] = ($carry[$sizeName] ?? 0) + $qty;
    //                 }
    //             }
    //             return $carry;
    //         }, []);

    //     // Calculate available quantities
    //     foreach ($sizes as $size) {
    //         $sizeName = $size->name;
    //         $cut = $cutQuantities[$sizeName] ?? 0;
    //         $sent = $sentQuantities[$sizeName] ?? 0;
    //         $availableQuantities[$sizeName] = max(0, $cut - $sent);
    //     }

    //     return response()->json([
    //         'availableQuantities' => $availableQuantities,
    //         'sizes' => $sizes
    //     ]);
    // }



    // // Reports
    // public function totalPrintEmbSendReport(Request $request)
    // {
    //     $query = SublimationPrintSend::with('productCombination.style', 'productCombination.color');

    //     if ($request->filled('start_date') && $request->filled('end_date')) {
    //         $query->whereBetween('date', [$request->start_date, $request->end_date]);
    //     }

    //     $printSendData = $query->get();
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $reportData = [];

    //     // Create a mapping of size IDs to size names
    //     $sizeIdToName = [];
    //     foreach ($allSizes as $size) {
    //         $sizeIdToName[$size->id] = $size->name;
    //     }

    //     foreach ($printSendData as $data) {
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

    //         // Convert size IDs to names for display
    //         foreach ($data->sublimation_print_send_quantities as $sizeId => $qty) {
    //             $sizeName = $sizeIdToName[$sizeId] ?? null;
    //             if ($sizeName) {
    //                 $normalized = strtolower($sizeName);
    //                 if (array_key_exists($normalized, $reportData[$key]['sizes'])) {
    //                     $reportData[$key]['sizes'][$normalized] += $qty;
    //                 }
    //             }
    //         }
    //         $reportData[$key]['total'] += $data->total_sublimation_print_send_quantity;
    //     }

    //     return view('backend.library.sublimation_print_send_data.reports.total', [
    //         'reportData' => array_values($reportData),
    //         'allSizes' => $allSizes
    //     ]);
    // }

    // public function wipReport(Request $request)
    // {
    //     // Get product combinations with sublimation_print = true
    //     $combinations = ProductCombination::where('sublimation_print', true)
    //         ->with('style', 'color')
    //         ->get();

    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $wipData = [];

    //     // Create a mapping of size IDs to size names
    //     $sizeIdToName = [];
    //     foreach ($allSizes as $size) {
    //         $sizeIdToName[$size->id] = $size->name;
    //     }

    //     foreach ($combinations as $pc) {
    //         $totalCut = CuttingData::where('product_combination_id', $pc->id)
    //             ->sum('total_cut_quantity');

    //         $totalSent = SublimationPrintSend::where('product_combination_id', $pc->id)
    //             ->sum('total_sublimation_print_send_quantity');

    //         if ($totalCut > $totalSent) {
    //             $key = $pc->style->name . '-' . $pc->color->name;

    //             if (!isset($wipData[$key])) {
    //                 $wipData[$key] = [
    //                     'style' => $pc->style->name,
    //                     'color' => $pc->color->name,
    //                     'sizes' => [],
    //                     'total_cut' => 0,
    //                     'total_sent' => 0,
    //                     'waiting' => 0
    //                 ];

    //                 // Initialize sizes
    //                 foreach ($allSizes as $size) {
    //                     $wipData[$key]['sizes'][$size->name] = [
    //                         'cut' => 0,
    //                         'sent' => 0,
    //                         'waiting' => 0
    //                     ];
    //                 }
    //             }

    //             $wipData[$key]['total_cut'] += $totalCut;
    //             $wipData[$key]['total_sent'] += $totalSent;
    //             $wipData[$key]['waiting'] += ($totalCut - $totalSent);

    //             // Aggregate size quantities
    //             $cuttingData = CuttingData::where('product_combination_id', $pc->id)->get();
    //             foreach ($cuttingData as $cd) {
    //                 foreach ($cd->cut_quantities as $size => $qty) {
    //                     if (isset($wipData[$key]['sizes'][$size])) {
    //                         $wipData[$key]['sizes'][$size]['cut'] += $qty;
    //                     }
    //                 }
    //             }

    //             $sendData = SublimationPrintSend::where('product_combination_id', $pc->id)->get();
    //             foreach ($sendData as $sd) {
    //                 foreach ($sd->sublimation_print_send_quantities as $sizeId => $qty) {
    //                     $sizeName = $sizeIdToName[$sizeId] ?? null;
    //                     if ($sizeName && isset($wipData[$key]['sizes'][$sizeName])) {
    //                         $wipData[$key]['sizes'][$sizeName]['sent'] += $qty;
    //                     }
    //                 }
    //             }

    //             // Calculate waiting per size
    //             foreach ($wipData[$key]['sizes'] as $size => $data) {
    //                 $wipData[$key]['sizes'][$size]['waiting'] =
    //                     $data['cut'] - $data['sent'];
    //             }
    //         }
    //     }

    //     return view('backend.library.sublimation_print_send_data.reports.wip', [
    //         'wipData' => array_values($wipData),
    //         'allSizes' => $allSizes
    //     ]);
    // }

    // public function readyToInputReport(Request $request)
    // {
    //     $readyData = [];

    //     // Product combinations with sublimation_print = false
    //     $nonEmbCombinations = ProductCombination::where('sublimation_print', false)
    //         ->with('style', 'color')
    //         ->get();

    //     foreach ($nonEmbCombinations as $pc) {
    //         $readyData[] = [
    //             'style' => $pc->style->name,
    //             'color' => $pc->color->name,
    //             'type' => 'No Print/Emb Needed',
    //             'total_cut' => CuttingData::where('product_combination_id', $pc->id)->sum('total_cut_quantity'),
    //             'total_sent' => 0
    //         ];
    //     }

    //     // Product combinations with sublimation_print = true and completed
    //     $embCombinations = ProductCombination::where('sublimation_print', true)
    //         ->with('style', 'color')
    //         ->get();

    //     foreach ($embCombinations as $pc) {
    //         $totalCut = CuttingData::where('product_combination_id', $pc->id)
    //             ->sum('total_cut_quantity');

    //         $totalSent = SublimationPrintSend::where('product_combination_id', $pc->id)
    //             ->sum('total_sublimation_print_send_quantity');

    //         if ($totalSent >= $totalCut) {
    //             $readyData[] = [
    //                 'style' => $pc->style->name,
    //                 'color' => $pc->color->name,
    //                 'type' => 'Print/Emb Completed',
    //                 'total_cut' => $totalCut,
    //                 'total_sent' => $totalSent
    //             ];
    //         }
    //     }

    //     return view('backend.library.sublimation_print_send_data.reports.ready', compact('readyData'));
    // }

    // public function available($product_combination_id)
    // {
    //     $sizes = Size::where('is_active', 1)->get();
    //     $sizeMap = [];
    //     foreach ($sizes as $size) {
    //         $sizeMap[strtolower($size->name)] = $size->id;
    //     }

    //     $cutQuantities = CuttingData::where('product_combination_id', $product_combination_id)
    //         ->get()
    //         ->flatMap(function ($record) use ($sizeMap) {
    //             $quantities = [];
    //             foreach ($record->cut_quantities as $sizeName => $quantity) {
    //                 $normalized = strtolower(trim($sizeName));
    //                 if (isset($sizeMap[$normalized])) {
    //                     $sizeId = $sizeMap[$normalized];
    //                     $quantities[$sizeId] = $quantity;
    //                 }
    //             }
    //             return $quantities;
    //         })
    //         ->groupBy(function ($item, $sizeId) {
    //             return $sizeId;
    //         })
    //         ->map->sum();

    //     $sentQuantities = SublimationPrintSend::where('product_combination_id', $product_combination_id)
    //         ->get()
    //         ->flatMap(function ($record) {
    //             return collect($record->sublimation_print_send_quantities)
    //                 ->mapWithKeys(fn($qty, $sizeId) => [(int)$sizeId => $qty]);
    //         })
    //         ->groupBy('key')
    //         ->map->sum('value');

    //     $availableQuantities = [];
    //     foreach ($cutQuantities as $sizeId => $cutQty) {
    //         $sentQty = $sentQuantities->get($sizeId, 0);
    //         $availableQuantities[(string)$sizeId] = $cutQty - $sentQty;
    //     }

    //     return response()->json([
    //         'available' => array_sum($availableQuantities),
    //         'available_per_size' => $availableQuantities
    //     ]);
    // }

    // // // Reports
    // // public function totalPrintEmbSendReport(Request $request)
    // // {
    // //     $query = SublimationPrintSend::with('productCombination.style', 'productCombination.color');

    // //     if ($request->filled('start_date') && $request->filled('end_date')) {
    // //         $query->whereBetween('date', [$request->start_date, $request->end_date]);
    // //     }

    // //     $printSendData = $query->get();
    // //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    // //     $reportData = [];

    // //     foreach ($printSendData as $data) {
    // //         $style = $data->productCombination->style->name;
    // //         $color = $data->productCombination->color->name;
    // //         $key = $style . '-' . $color;

    // //         if (!isset($reportData[$key])) {
    // //             $reportData[$key] = [
    // //                 'style' => $style,
    // //                 'color' => $color,
    // //                 'sizes' => array_fill_keys($allSizes->pluck('name')->map(fn($n) => strtolower($n))->toArray(), 0),
    // //                 'total' => 0
    // //             ];
    // //         }

    // //         foreach ($data->sublimation_print_send_quantities as $size => $qty) {
    // //             $normalized = strtolower($size);
    // //             if (array_key_exists($normalized, $reportData[$key]['sizes'])) {
    // //                 $reportData[$key]['sizes'][$normalized] += $qty;
    // //             }
    // //         }
    // //         $reportData[$key]['total'] += $data->total_sublimation_print_send_quantity;
    // //     }

    // //     return view('backend.library.sublimation_print_send_data.reports.total', [
    // //         'reportData' => array_values($reportData),
    // //         'allSizes' => $allSizes
    // //     ]);
    // // }

    // // public function wipReport(Request $request)
    // // {
    // //     // Get product combinations with sublimation_print = true
    // //     $combinations = ProductCombination::where('sublimation_print', true)
    // //         ->with('style', 'color')
    // //         ->get();

    // //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    // //     $wipData = [];

    // //     foreach ($combinations as $pc) {
    // //         $totalCut = CuttingData::where('product_combination_id', $pc->id)
    // //             ->sum('total_cut_quantity');

    // //         $totalSent = SublimationPrintSend::where('product_combination_id', $pc->id)
    // //             ->sum('total_sublimation_print_send_quantity');

    // //         if ($totalCut > $totalSent) {
    // //             $key = $pc->style->name . '-' . $pc->color->name;

    // //             if (!isset($wipData[$key])) {
    // //                 $wipData[$key] = [
    // //                     'style' => $pc->style->name,
    // //                     'color' => $pc->color->name,
    // //                     'sizes' => [],
    // //                     'total_cut' => 0,
    // //                     'total_sent' => 0,
    // //                     'waiting' => 0
    // //                 ];

    // //                 // Initialize sizes
    // //                 foreach ($allSizes as $size) {
    // //                     $wipData[$key]['sizes'][$size->name] = [
    // //                         'cut' => 0,
    // //                         'sent' => 0,
    // //                         'waiting' => 0
    // //                     ];
    // //                 }
    // //             }

    // //             $wipData[$key]['total_cut'] += $totalCut;
    // //             $wipData[$key]['total_sent'] += $totalSent;
    // //             $wipData[$key]['waiting'] += ($totalCut - $totalSent);

    // //             // Aggregate size quantities
    // //             $cuttingData = CuttingData::where('product_combination_id', $pc->id)->get();
    // //             foreach ($cuttingData as $cd) {
    // //                 foreach ($cd->cut_quantities as $size => $qty) {
    // //                     if (isset($wipData[$key]['sizes'][$size])) {
    // //                         $wipData[$key]['sizes'][$size]['cut'] += $qty;
    // //                     }
    // //                 }
    // //             }

    // //             $sendData = SublimationPrintSend::where('product_combination_id', $pc->id)->get();
    // //             foreach ($sendData as $sd) {
    // //                 foreach ($sd->sublimation_print_send_quantities as $size => $qty) {
    // //                     if (isset($wipData[$key]['sizes'][$size])) {
    // //                         $wipData[$key]['sizes'][$size]['sent'] += $qty;
    // //                     }
    // //                 }
    // //             }

    // //             // Calculate waiting per size
    // //             foreach ($wipData[$key]['sizes'] as $size => $data) {
    // //                 $wipData[$key]['sizes'][$size]['waiting'] =
    // //                     $data['cut'] - $data['sent'];
    // //             }
    // //         }
    // //     }

    // //     return view('backend.library.sublimation_print_send_data.reports.wip', [
    // //         'wipData' => array_values($wipData),
    // //         'allSizes' => $allSizes
    // //     ]);
    // // }

    // // public function readyToInputReport(Request $request)
    // // {
    // //     $readyData = [];

    // //     // Product combinations with sublimation_print = false
    // //     $nonEmbCombinations = ProductCombination::where('sublimation_print', false)
    // //         ->with('style', 'color')
    // //         ->get();

    // //     foreach ($nonEmbCombinations as $pc) {
    // //         $readyData[] = [
    // //             'style' => $pc->style->name,
    // //             'color' => $pc->color->name,
    // //             'type' => 'No Print/Emb Needed',
    // //             'total_cut' => CuttingData::where('product_combination_id', $pc->id)->sum('total_cut_quantity'),
    // //             'total_sent' => 0
    // //         ];
    // //     }

    // //     // Product combinations with sublimation_print = true and completed
    // //     $embCombinations = ProductCombination::where('sublimation_print', true)
    // //         ->with('style', 'color')
    // //         ->get();

    // //     foreach ($embCombinations as $pc) {
    // //         $totalCut = CuttingData::where('product_combination_id', $pc->id)
    // //             ->sum('total_cut_quantity');

    // //         $totalSent = SublimationPrintSend::where('product_combination_id', $pc->id)
    // //             ->sum('total_sublimation_print_send_quantity');

    // //         if ($totalSent >= $totalCut) {
    // //             $readyData[] = [
    // //                 'style' => $pc->style->name,
    // //                 'color' => $pc->color->name,
    // //                 'type' => 'Print/Emb Completed',
    // //                 'total_cut' => $totalCut,
    // //                 'total_sent' => $totalSent
    // //             ];
    // //         }
    // //     }

    // //     return view('backend.library.sublimation_print_send_data.reports.ready', compact('readyData'));
    // // }

    // // public function available($product_combination_id)
    // // {
    // //     $sizes = Size::where('is_active', 1)->get();
    // //     $sizeMap = [];
    // //     foreach ($sizes as $size) {
    // //         $sizeMap[strtolower($size->name)] = $size->id;
    // //     }

    // //     $cutQuantities = CuttingData::where('product_combination_id', $product_combination_id)
    // //         ->get()
    // //         ->flatMap(function ($record) use ($sizeMap) {
    // //             $quantities = [];
    // //             foreach ($record->cut_quantities as $sizeName => $quantity) {
    // //                 $normalized = strtolower(trim($sizeName));
    // //                 if (isset($sizeMap[$normalized])) {
    // //                     $sizeId = $sizeMap[$normalized];
    // //                     $quantities[$sizeId] = $quantity;
    // //                 }
    // //             }
    // //             return $quantities;
    // //         })
    // //         ->groupBy(function ($item, $sizeId) {
    // //             return $sizeId;
    // //         })
    // //         ->map->sum();

    // //     $sentQuantities = SublimationPrintSend::where('product_combination_id', $product_combination_id)
    // //         ->get()
    // //         ->flatMap(function ($record) {
    // //             return collect($record->sublimation_print_send_quantities)
    // //                 ->mapWithKeys(fn($qty, $sizeId) => [(int)$sizeId => $qty]);
    // //         })
    // //         ->groupBy('key')
    // //         ->map->sum('value');

    // //     $availableQuantities = [];
    // //     foreach ($cutQuantities as $sizeId => $cutQty) {
    // //         $sentQty = $sentQuantities->get($sizeId, 0);
    // //         $availableQuantities[(string)$sizeId] = $cutQty - $sentQty;
    // //     }

    // //     return response()->json([
    // //         'available' => array_sum($availableQuantities),
    // //         'available_per_size' => $availableQuantities
    // //     ]);
    // // }
    // }

    // public function edit(SublimationPrintSend $sublimationPrintSendDatum)
    // {
    //     $sublimationPrintSendDatum->load('productCombination.buyer', 'productCombination.style', 'productCombination.color', 'productCombination.size');
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();

    //     // Get available quantities for this product combination
    //     $availableQuantitiesResponse = $this->getAvailableSendQuantities($sublimationPrintSendDatum->productCombination);
    //     $availableQuantities = (array) $availableQuantitiesResponse->getData()->availableQuantities;

    //     // Add current quantities to available for editing (since we're editing existing record)
    //     $sendQuantities = (array) $sublimationPrintSendDatum->sublimation_print_send_quantities;
    //     foreach ($sendQuantities as $sizeId => $qty) {
    //         $sizeName = $this->getSizeNameById($sizeId);
    //         if ($sizeName && isset($availableQuantities[$sizeName])) {
    //             $availableQuantities[$sizeName] += $qty;
    //         }
    //     }

    //     // Ensure the quantities are arrays, not objects
    //     $wasteQuantities = (array) $sublimationPrintSendDatum->sublimation_print_send_waste_quantities;

    //     // Get distinct PO numbers for dropdown
    //     $sublimationProductIds = ProductCombination::where('sublimation_print', true)->pluck('id');
    //     $distinctPoNumbers = CuttingData::whereIn('product_combination_id', $sublimationProductIds)
    //         ->distinct()
    //         ->pluck('po_number')
    //         ->filter()
    //         ->values();

    //     return view('backend.library.sublimation_print_send_data.edit', [
    //         'sublimationPrintSendDatum' => $sublimationPrintSendDatum,
    //         'allSizes' => $allSizes,
    //         'availableQuantities' => $availableQuantities,
    //         'sendQuantities' => $sendQuantities,
    //         'wasteQuantities' => $wasteQuantities,
    //         'distinctPoNumbers' => $distinctPoNumbers
    //     ]);
    // }

    // public function update(Request $request, SublimationPrintSend $sublimationPrintSendDatum)
    // {
    //     $request->validate([
    //         'date' => 'required|date',
    //         'sublimation_print_send_quantities' => 'required|array',
    //         'sublimation_print_send_quantities.*' => 'nullable|integer|min:0',
    //     ]);

    //     try {
    //         DB::beginTransaction();

    //         $productCombination = $sublimationPrintSendDatum->productCombination;

    //         // Get available quantities without the current record's quantities
    //         $availableQuantitiesResponse = $this->getAvailableSendQuantities($productCombination);
    //         $availableQuantities = (array) $availableQuantitiesResponse->getData()->availableQuantities;

    //         // Process send quantities with validation
    //         $sendQuantities = [];
    //         $wasteQuantities = [];
    //         $totalSendQuantity = 0;
    //         $totalWasteQuantity = 0;
    //         $errors = [];

    //         foreach ($request->sublimation_print_send_quantities as $sizeName => $quantity) {
    //             $quantity = (int)$quantity;
    //             if ($quantity > 0) {
    //                 $maxAllowed = $availableQuantities[$sizeName] ?? 0;

    //                 // Add back the current quantity from this record
    //                 $currentSizeId = $this->getSizeIdByName($sizeName);
    //                 $currentQty = isset($sublimationPrintSendDatum->sublimation_print_send_quantities[$currentSizeId])
    //                     ? (int)$sublimationPrintSendDatum->sublimation_print_send_quantities[$currentSizeId]
    //                     : 0;

    //                 $maxAllowed += $currentQty;

    //                 if ($quantity > $maxAllowed) {
    //                     $errors["sublimation_print_send_quantities.$sizeName"] = "Quantity for $sizeName exceeds available ($maxAllowed)";
    //                 } else {
    //                     $sizeId = $this->getSizeIdByName($sizeName);
    //                     if ($sizeId) {
    //                         $sendQuantities[$sizeId] = $quantity;
    //                         $totalSendQuantity += $quantity;
    //                     }
    //                 }
    //             }
    //         }

    //         // Process waste quantities
    //         foreach ($request->sublimation_print_send_waste_quantities as $sizeName => $quantity) {
    //             $quantity = (int)$quantity;
    //             if ($quantity > 0) {
    //                 $sizeId = $this->getSizeIdByName($sizeName);
    //                 if ($sizeId) {
    //                     $wasteQuantities[$sizeId] = $quantity;
    //                     $totalWasteQuantity += $quantity;
    //                 }
    //             }
    //         }

    //         if (!empty($errors)) {
    //             return redirect()->back()->withErrors($errors)->withInput();
    //         }

    //         $sublimationPrintSendDatum->update([
    //             'date' => $request->date,
    //             'po_number' => $request->po_number ? implode(',', $request->po_number) : $sublimationPrintSendDatum->po_number,
    //             'old_order' => $request->old_order,
    //             'sublimation_print_send_quantities' => $sendQuantities,
    //             'total_sublimation_print_send_quantity' => $totalSendQuantity,
    //             'sublimation_print_send_waste_quantities' => $wasteQuantities,
    //             'total_sublimation_print_send_waste_quantity' => $totalWasteQuantity,
    //         ]);

    //         DB::commit();

    //         return redirect()->route('sublimation_print_send_data.index')
    //             ->withMessage( 'Sublimation Print/Send data updated successfully.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return redirect()->back()
    //             ->with('error', 'Error occurred: ' . $e->getMessage())
    //             ->withInput();
    //     }
    // }


    // public function find(Request $request)
    // {
    //     $poNumbers = $request->input('po_numbers', []);

    //     if (empty($poNumbers)) {
    //         return response()->json([]);
    //     }

    //     // Get product combinations with sublimation_print = true
    //     $sublimationProductIds = ProductCombination::where('sublimation_print', true)->pluck('id');

    //     // Get cutting data for the selected PO numbers and sublimation products
    //     $cuttingData = CuttingData::whereIn('po_number', $poNumbers)
    //         ->whereIn('product_combination_id', $sublimationProductIds)
    //         ->with(['productCombination.style', 'productCombination.color', 'productCombination.size'])
    //         ->get()
    //         ->groupBy('po_number');

    //     $result = [];

    //     foreach ($cuttingData as $poNumber => $cuttingRecords) {
    //         $result[$poNumber] = [];

    //         // Group cutting records by product_combination_id
    //         $groupedByCombination = $cuttingRecords->groupBy('product_combination_id');

    //         foreach ($groupedByCombination as $combinationId => $records) {
    //             // Get the product combination from the first record
    //             $productCombination = $records->first()->productCombination;

    //             if (!$productCombination) {
    //                 continue;
    //             }

    //             $availableQuantities = $this->getAvailableSendQuantities($productCombination)->getData()->availableQuantities;


    //             $result[$poNumber][] = [
    //                 'combination_id' => $productCombination->id,
    //                 'style' => $productCombination->style->name,
    //                 'color' => $productCombination->color->name,
    //                 'available_quantities' => $availableQuantities,
    //                 'size_ids' => $productCombination->sizes->pluck('id')->toArray()
    //             ];
    //         }
    //     }

    //     return response()->json($result);
    // }


    // public function getAvailableSendQuantities(ProductCombination $productCombination)

    // {
    //     $sizes = Size::where('is_active', 1)->get();
    //     $availableQuantities = [];

    //     // Create a mapping of size IDs to size names
    //     $sizeIdToName = [];
    //     foreach ($sizes as $size) {
    //         $sizeIdToName[$size->id] = $size->name;
    //     }

    //     // Sum cut quantities per size using original case
    //     $cutQuantities = CuttingData::where('product_combination_id', $productCombination->id)
    //         ->get()
    //         ->pluck('cut_quantities')
    //         ->reduce(function ($carry, $quantities) use ($sizeIdToName) {
    //             foreach ($quantities as $key => $qty) {
    //                 // Handle both numeric keys (size IDs) and string keys (size names)
    //                 $sizeName = is_numeric($key) ? ($sizeIdToName[$key] ?? null) : $key;

    //                 if ($sizeName && $qty > 0) {
    //                     $carry[$sizeName] = ($carry[$sizeName] ?? 0) + $qty;
    //                 }
    //             }
    //             return $carry;
    //         }, []);

    //     // Sum sent quantities per size using original case
    //     $sentQuantities = SublimationPrintSend::where('product_combination_id', $productCombination->id)
    //         ->get()
    //         ->pluck('sublimation_print_send_quantities')
    //         ->reduce(function ($carry, $quantities) use ($sizeIdToName) {
    //             foreach ($quantities as $key => $qty) {
    //                 // Handle both numeric keys (size IDs) and string keys (size names)
    //                 $sizeName = is_numeric($key) ? ($sizeIdToName[$key] ?? null) : $key;

    //                 if ($sizeName && $qty > 0) {
    //                     $carry[$sizeName] = ($carry[$sizeName] ?? 0) + $qty;
    //                 }
    //             }
    //             return $carry;
    //         }, []);

    //     // Calculate available quantities
    //     foreach ($sizes as $size) {
    //         $sizeName = $size->name;
    //         $cut = $cutQuantities[$sizeName] ?? 0;
    //         $sent = $sentQuantities[$sizeName] ?? 0;
    //         $availableQuantities[$sizeName] = max(0, $cut - $sent);
    //     }

    //     return response()->json([
    //         'availableQuantities' => $availableQuantities,
    //         'sizes' => $sizes
    //     ]);
    // }

    // public function totalPrintEmbSendReport(Request $request)
    // {
    //     $query = SublimationPrintSend::with('productCombination.style', 'productCombination.color');

    //     if ($request->filled('start_date') && $request->filled('end_date')) {
    //         $query->whereBetween('date', [$request->start_date, $request->end_date]);
    //     }

    //     $printSendData = $query->get();
    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $reportData = [];

    //     // Create a mapping of size IDs to size names
    //     $sizeIdToName = [];
    //     foreach ($allSizes as $size) {
    //         $sizeIdToName[$size->id] = $size->name;
    //     }

    //     foreach ($printSendData as $data) {
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

    //         // Convert size IDs to names for display
    //         foreach ($data->sublimation_print_send_quantities as $sizeId => $qty) {
    //             $sizeName = $sizeIdToName[$sizeId] ?? null;
    //             if ($sizeName) {
    //                 $normalized = strtolower($sizeName);
    //                 if (array_key_exists($normalized, $reportData[$key]['sizes'])) {
    //                     $reportData[$key]['sizes'][$normalized] += $qty;
    //                 }
    //             }
    //         }
    //         $reportData[$key]['total'] += $data->total_sublimation_print_send_quantity;
    //     }
    //     $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
    //     $allColors = Color::where('is_active', 1)->orderBy('name')->get();
    //     $distinctPoNumbers = CuttingData::distinct()->pluck('po_number')->filter()->values();

    //     return view('backend.library.sublimation_print_send_data.reports.total', [
    //         'reportData' => array_values($reportData),
    //         'allSizes' => $allSizes,
    //         'allStyles' => $allStyles,
    //         'allColors' => $allColors,
    //         'distinctPoNumbers' => $distinctPoNumbers
    //     ]);
    // }

    // public function wipReport(Request $request)
    // {
    //     // Get product combinations with sublimation_print = true
    //     $combinations = ProductCombination::where('sublimation_print', true)
    //         ->with('style', 'color')
    //         ->get();

    //     $allSizes = Size::where('is_active', 1)->orderBy('id', 'asc')->get();
    //     $wipData = [];

    //     // Create a mapping of size IDs to size names
    //     $sizeIdToName = [];
    //     foreach ($allSizes as $size) {
    //         $sizeIdToName[$size->id] = $size->name;
    //     }

    //     foreach ($combinations as $pc) {
    //         $totalCut = CuttingData::where('product_combination_id', $pc->id)
    //             ->sum('total_cut_quantity');

    //         $totalSent = SublimationPrintSend::where('product_combination_id', $pc->id)
    //             ->sum('total_sublimation_print_send_quantity');

    //         if ($totalCut > $totalSent) {
    //             $key = $pc->style->name . '-' . $pc->color->name;

    //             if (!isset($wipData[$key])) {
    //                 $wipData[$key] = [
    //                     'style' => $pc->style->name,
    //                     'color' => $pc->color->name,
    //                     'sizes' => [],
    //                     'total_cut' => 0,
    //                     'total_sent' => 0,
    //                     'waiting' => 0
    //                 ];

    //                 // Initialize sizes
    //                 foreach ($allSizes as $size) {
    //                     $wipData[$key]['sizes'][$size->name] = [
    //                         'cut' => 0,
    //                         'sent' => 0,
    //                         'waiting' => 0
    //                     ];
    //                 }
    //             }

    //             $wipData[$key]['total_cut'] += $totalCut;
    //             $wipData[$key]['total_sent'] += $totalSent;
    //             $wipData[$key]['waiting'] += ($totalCut - $totalSent);

    //             // Aggregate size quantities
    //             $cuttingData = CuttingData::where('product_combination_id', $pc->id)->get();
    //             foreach ($cuttingData as $cd) {
    //                 foreach ($cd->cut_quantities as $size => $qty) {
    //                     if (isset($wipData[$key]['sizes'][$size])) {
    //                         $wipData[$key]['sizes'][$size]['cut'] += $qty;
    //                     }
    //                 }
    //             }

    //             $sendData = SublimationPrintSend::where('product_combination_id', $pc->id)->get();
    //             foreach ($sendData as $sd) {
    //                 foreach ($sd->sublimation_print_send_quantities as $sizeId => $qty) {
    //                     $sizeName = $sizeIdToName[$sizeId] ?? null;
    //                     if ($sizeName && isset($wipData[$key]['sizes'][$sizeName])) {
    //                         $wipData[$key]['sizes'][$sizeName]['sent'] += $qty;
    //                     }
    //                 }
    //             }

    //             // Calculate waiting per size
    //             foreach ($wipData[$key]['sizes'] as $size => $data) {
    //                 $wipData[$key]['sizes'][$size]['waiting'] =
    //                     $data['cut'] - $data['sent'];
    //             }
    //         }
    //     }
    //     $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
    //     $allColors = Color::where('is_active', 1)->orderBy('name')->get();
    //     $distinctPoNumbers = CuttingData::distinct()->pluck('po_number')->filter()->values();

    //     return view('backend.library.sublimation_print_send_data.reports.wip', [
    //         'wipData' => array_values($wipData),
    //         'allSizes' => $allSizes,
    //         'allStyles' => $allStyles,
    //         'allColors' => $allColors,
    //         'distinctPoNumbers' => $distinctPoNumbers
    //     ]);
    // }

    // public function readyToInputReport(Request $request)
    // {
    //     $readyData = [];

    //     // Product combinations with sublimation_print = false
    //     $nonEmbCombinations = ProductCombination::where('sublimation_print', false)
    //         ->with('style', 'color')
    //         ->get();

    //     foreach ($nonEmbCombinations as $pc) {
    //         $readyData[] = [
    //             'style' => $pc->style->name,
    //             'color' => $pc->color->name,
    //             'type' => 'No Print/Emb Needed',
    //             'total_cut' => CuttingData::where('product_combination_id', $pc->id)->sum('total_cut_quantity'),
    //             'total_sent' => 0
    //         ];
    //     }

    //     // Product combinations with sublimation_print = true and completed
    //     $embCombinations = ProductCombination::where('sublimation_print', true)
    //         ->with('style', 'color')
    //         ->get();

    //     foreach ($embCombinations as $pc) {
    //         $totalCut = CuttingData::where('product_combination_id', $pc->id)
    //             ->sum('total_cut_quantity');

    //         $totalSent = SublimationPrintSend::where('product_combination_id', $pc->id)
    //             ->sum('total_sublimation_print_send_quantity');

    //         if ($totalSent >= $totalCut) {
    //             $readyData[] = [
    //                 'style' => $pc->style->name,
    //                 'color' => $pc->color->name,
    //                 'type' => 'Print/Emb Completed',
    //                 'total_cut' => $totalCut,
    //                 'total_sent' => $totalSent
    //             ];
    //         }

    //     }

    //     $allStyles = Style::where('is_active', 1)->orderBy('name')->get();
    //     $allColors = Color::where('is_active', 1)->orderBy('name')->get();
    //     $distinctPoNumbers = CuttingData::distinct()->pluck('po_number')->filter()->values();

    //     return view('backend.library.sublimation_print_send_data.reports.ready', compact('readyData', 'allStyles', 'allColors', 'distinctPoNumbers'));
    // }






}
