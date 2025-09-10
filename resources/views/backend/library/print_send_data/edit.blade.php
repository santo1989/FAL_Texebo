<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Print/Embroidery Send Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Print/Embroidery Send Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('print_send_data.index') }}">Print/Embroidery Send Data</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <x-backend.layouts.elements.errors />
    <form action="{{ route('print_send_data.update', $printSendDatum->id) }}" method="post">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" name="date" id="date" class="form-control"
                        value="{{ old('date', $printSendDatum->date) }}" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>PO Number</label>
                    <input type="text" class="form-control" value="{{ $printSendDatum->po_number }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Old Order</label>
                    <input type="text" class="form-control" value="{{ $printSendDatum->old_order }}" readonly>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Buyer</label>
                    <input type="text" class="form-control"
                        value="{{ $printSendDatum->productCombination->buyer->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Style</label>
                    <input type="text" class="form-control"
                        value="{{ $printSendDatum->productCombination->style->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Color</label>
                    <input type="text" class="form-control"
                        value="{{ $printSendDatum->productCombination->color->name ?? 'N/A' }}" readonly>
                </div>
            </div>
        </div>

        <table class="table table-bordered mt-4 text-center">
            <thead>
                <tr>
                    <th>Size</th>
                    <th>Order Quantity</th>
                    <th>Cutting Quantity</th>
                    <th>Already Sent Quantity</th>
                    <th>Available Quantity</th>
                    <th>Send Quantity</th>
                    <th>Waste Quantity</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Get valid sizes for this product combination
                    $validSizeIds = $printSendDatum->productCombination->size_ids;
                    $validSizes = \App\Models\Size::whereIn('id', $validSizeIds)
                        ->where('is_active', 1)
                        ->orderBy('id', 'asc')
                        ->get();
                    
                    // Initialize arrays to store quantities
                    $orderQuantitiesBySize = [];
                    $cuttingQuantitiesBySize = [];
                    $alreadySentQuantitiesBySize = [];
                    
                    // Get order quantities
                    $orderData = \App\Models\OrderData::where('po_number', $printSendDatum->po_number)
                        ->where('product_combination_id', $printSendDatum->product_combination_id)
                        ->get();
                    
                    foreach ($orderData as $order) {
                        // No need to decode as Laravel automatically casts JSON to array
                        $quantities = $order->order_quantities;
                        foreach ($quantities as $sizeId => $qty) {
                            if (in_array($sizeId, $validSizeIds)) {
                                $orderQuantitiesBySize[$sizeId] = ($orderQuantitiesBySize[$sizeId] ?? 0) + $qty;
                            }
                        }
                    }
                    
                    // Get cutting quantities
                    $cuttingData = \App\Models\CuttingData::where('po_number', $printSendDatum->po_number)
                        ->where('product_combination_id', $printSendDatum->product_combination_id)
                        ->get();
                    
                    foreach ($cuttingData as $cutting) {
                        // No need to decode as Laravel automatically casts JSON to array
                        $quantities = $cutting->cut_quantities;
                        foreach ($quantities as $sizeId => $qty) {
                            if (in_array($sizeId, $validSizeIds)) {
                                $cuttingQuantitiesBySize[$sizeId] = ($cuttingQuantitiesBySize[$sizeId] ?? 0) + $qty;
                            }
                        }
                    }
                    
                    // Get already sent quantities (excluding current record)
                    $alreadySentData = \App\Models\PrintSendData::where('po_number', $printSendDatum->po_number)
                        ->where('product_combination_id', $printSendDatum->product_combination_id)
                        ->where('id', '!=', $printSendDatum->id)
                        ->get();
                    
                    foreach ($alreadySentData as $sent) {
                        // No need to decode as Laravel automatically casts JSON to array
                        $quantities = $sent->send_quantities;
                        foreach ($quantities as $sizeId => $qty) {
                            if (in_array($sizeId, $validSizeIds)) {
                                $alreadySentQuantitiesBySize[$sizeId] = ($alreadySentQuantitiesBySize[$sizeId] ?? 0) + $qty;
                            }
                        }
                    }
                    
                    // Calculate total quantities
                    $totalOrderQuantity = array_sum($orderQuantitiesBySize);
                    $totalCuttingQuantity = array_sum($cuttingQuantitiesBySize);
                    $totalAlreadySentQuantity = array_sum($alreadySentQuantitiesBySize);
                    
                    // Current record quantities
                    $currentSendQuantities = $printSendDatum->send_quantities ?? [];
                    $currentWasteQuantities = $printSendDatum->send_waste_quantities ?? [];
                    
                    $totalCurrentSend = array_sum($currentSendQuantities);
                    $totalCurrentWaste = array_sum($currentWasteQuantities);
                @endphp

                @foreach ($validSizes as $size)
                    @php
                        $orderQty = $orderQuantitiesBySize[$size->id] ?? 0;
                        $cuttingQty = $cuttingQuantitiesBySize[$size->id] ?? 0;
                        $alreadySentQty = $alreadySentQuantitiesBySize[$size->id] ?? 0;
                        $availableQty = max(0, $cuttingQty - $alreadySentQty);
                        $currentSendQty = $currentSendQuantities[$size->id] ?? 0;
                        $currentWasteQty = $currentWasteQuantities[$size->id] ?? 0;
                        $maxQty = $availableQty + $currentSendQty;
                    @endphp
                    <tr>
                        <td>{{ $size->name }}</td>
                        <td>{{ $orderQty }} Pcs</td>
                        <td>{{ $cuttingQty }} Pcs</td>
                        <td>{{ $alreadySentQty }} Pcs</td>
                        <td>{{ $availableQty }} Pcs</td>
                        <td>
                            <input type="number" name="send_quantities[{{ $size->id }}]"
                                class="form-control send-qty-input" value="{{ $currentSendQty }}" min="0"
                                max="{{ $maxQty }}" data-max="{{ $maxQty }}"
                                oninput="validateQuantity(this)">
                        </td>
                        <td>
                            <input type="number" name="send_waste_quantities[{{ $size->id }}]"
                                class="form-control waste-qty-input" value="{{ $currentWasteQty }}" min="0"
                                max="{{ $maxQty }}" oninput="validateWasteQuantity(this, {{ $maxQty }})">
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td><strong>Total</strong></td>
                    <td><strong>{{ $totalOrderQuantity }} Pcs</strong></td>
                    <td><strong>{{ $totalCuttingQuantity }} Pcs</strong></td>
                    <td><strong>{{ $totalAlreadySentQuantity }} Pcs</strong></td>
                    <td><strong>{{ $totalCuttingQuantity - $totalAlreadySentQuantity }} Pcs</strong></td>
                    <td><strong id="total-send-quantity">{{ $totalCurrentSend }}</strong></td>
                    <td><strong id="total-waste-quantity">{{ $totalCurrentWaste }}</strong></td>
                </tr>
            </tbody>
        </table>

        <a href="{{ route('print_send_data.index') }}" class="btn btn-secondary mt-3">Back</a>
        <button type="submit" class="btn btn-primary mt-3">Update Print/Embroidery Send Data</button>
    </form>

    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function validateQuantity(input) {
            const max = parseInt(input.getAttribute('data-max')) || 0;
            const value = parseInt(input.value) || 0;

            if (value > max) {
                input.value = max;
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Input',
                    text: `Send quantity cannot exceed available quantity (${max}).`,
                });
            }
            updateTotals();
        }

        function validateWasteQuantity(input, max) {
            const value = parseInt(input.value) || 0;

            if (value > max) {
                input.value = max;
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Input',
                    text: `Waste quantity cannot exceed available quantity (${max}).`,
                });
            }
            updateTotals();
        }

        // Calculate and update totals on input
        function updateTotals() {
            let totalSend = 0;
            let totalWaste = 0;

            document.querySelectorAll('.send-qty-input').forEach(input => {
                totalSend += parseInt(input.value) || 0;
            });

            document.querySelectorAll('.waste-qty-input').forEach(input => {
                totalWaste += parseInt(input.value) || 0;
            });

            // Update the total row
            document.getElementById('total-send-quantity').textContent = totalSend;
            document.getElementById('total-waste-quantity').textContent = totalWaste;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const sendInputs = document.querySelectorAll('.send-qty-input');
            const wasteInputs = document.querySelectorAll('.waste-qty-input');

            sendInputs.forEach(input => {
                input.addEventListener('input', updateTotals);
            });

            wasteInputs.forEach(input => {
                input.addEventListener('input', updateTotals);
            });
        });
    </script>
</x-backend.layouts.master>