<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Print/Embroidery Receive Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Edit Print/Embroidery Receive Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('print_receive_data.index') }}">Print/Emb Receive</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <x-backend.layouts.elements.errors />
    <form action="{{ route('print_receive_data.update', $printReceiveDatum->id) }}" method="post">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" name="date" id="date" class="form-control"
                        value="{{ old('date', $printReceiveDatum->date) }}" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>PO Number</label>
                    <input type="text" class="form-control" value="{{ $printReceiveDatum->po_number }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Old Order</label>
                    <input type="text" class="form-control" value="{{ $printReceiveDatum->old_order ?? 'N/A' }}" readonly>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Buyer</label>
                    <input type="text" class="form-control"
                        value="{{ $printReceiveDatum->productCombination->buyer->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Style</label>
                    <input type="text" class="form-control"
                        value="{{ $printReceiveDatum->productCombination->style->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Color</label>
                    <input type="text" class="form-control"
                        value="{{ $printReceiveDatum->productCombination->color->name ?? 'N/A' }}" readonly>
                </div>
            </div>
        </div>

        <table class="table table-bordered mt-4 text-center">
            <thead>
                <tr>
                    <th>Size</th>
                    <th>Order Quantity</th>
                    <th>Cutting Quantity</th>
                    <th>Already Received Quantity</th>
                    <th>Available Quantity</th>
                    <th>Receive Quantity</th>
                    <th>Waste Quantity</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Get valid sizes for this product combination
                    $validSizeIds = $printReceiveDatum->productCombination->size_ids;
                    $validSizes = \App\Models\Size::whereIn('id', $validSizeIds)
                        ->where('is_active', 1)
                        ->orderBy('id', 'asc')
                        ->get();
                    
                    // Initialize arrays to store quantities
                    $orderQuantitiesBySize = [];
                    $cuttingQuantitiesBySize = [];
                    $alreadyReceivedQuantitiesBySize = [];
                    
                    // Get order quantities
                    $orderData = \App\Models\OrderData::where('po_number', $printReceiveDatum->po_number)
                        ->where('product_combination_id', $printReceiveDatum->product_combination_id)
                        ->get();
                    
                    foreach ($orderData as $order) {
                        $quantities = $order->order_quantities;
                        foreach ($quantities as $sizeId => $qty) {
                            if (in_array($sizeId, $validSizeIds)) {
                                $orderQuantitiesBySize[$sizeId] = ($orderQuantitiesBySize[$sizeId] ?? 0) + $qty;
                            }
                        }
                    }
                    
                    // Get cutting quantities
                    $cuttingData = \App\Models\CuttingData::where('po_number', $printReceiveDatum->po_number)
                        ->where('product_combination_id', $printReceiveDatum->product_combination_id)
                        ->get();
                    
                    foreach ($cuttingData as $cutting) {
                        $quantities = $cutting->cut_quantities;
                        foreach ($quantities as $sizeId => $qty) {
                            if (in_array($sizeId, $validSizeIds)) {
                                $cuttingQuantitiesBySize[$sizeId] = ($cuttingQuantitiesBySize[$sizeId] ?? 0) + $qty;
                            }
                        }
                    }
                    
                    // Get already received quantities (excluding current record)
                    $alreadyReceivedData = \App\Models\PrintReceiveData::where('po_number', $printReceiveDatum->po_number)
                        ->where('product_combination_id', $printReceiveDatum->product_combination_id)
                        ->where('id', '!=', $printReceiveDatum->id)
                        ->get();
                    
                    foreach ($alreadyReceivedData as $received) {
                        $quantities = $received->receive_quantities;
                        foreach ($quantities as $sizeId => $qty) {
                            if (in_array($sizeId, $validSizeIds)) {
                                $alreadyReceivedQuantitiesBySize[$sizeId] = ($alreadyReceivedQuantitiesBySize[$sizeId] ?? 0) + $qty;
                            }
                        }
                    }
                    
                    // Calculate total quantities
                    $totalOrderQuantity = array_sum($orderQuantitiesBySize);
                    $totalCuttingQuantity = array_sum($cuttingQuantitiesBySize);
                    $totalAlreadyReceivedQuantity = array_sum($alreadyReceivedQuantitiesBySize);
                    
                    // Current record quantities
                    $currentReceiveQuantities = $printReceiveDatum->receive_quantities ?? [];
                    $currentWasteQuantities = $printReceiveDatum->receive_waste_quantities ?? [];
                    
                    $totalCurrentReceive = array_sum($currentReceiveQuantities);
                    $totalCurrentWaste = array_sum($currentWasteQuantities);
                @endphp

                @foreach ($validSizes as $size)
                    @php
                        $orderQty = $orderQuantitiesBySize[$size->id] ?? 0;
                        $cuttingQty = $cuttingQuantitiesBySize[$size->id] ?? 0;
                        $alreadyReceivedQty = $alreadyReceivedQuantitiesBySize[$size->id] ?? 0;
                        
                        // Convert to array if it's an object
                        $receiveQty = is_object($printReceiveDatum->receive_quantities) ? 
                            ($printReceiveDatum->receive_quantities->{$size->id} ?? 0) : 
                            ($printReceiveDatum->receive_quantities[$size->id] ?? 0);
                        
                        $wasteQty = is_object($printReceiveDatum->receive_waste_quantities) ? 
                            ($printReceiveDatum->receive_waste_quantities->{$size->id} ?? 0) : 
                            ($printReceiveDatum->receive_waste_quantities[$size->id] ?? 0);
                        
                        $currentReceiveQty = $receiveQty;
                        $currentWasteQty = $wasteQty;
                        $maxQty = $availableQuantities[$size->id] ?? 0;
                        $availableQty = $maxQty - ($currentReceiveQty + $currentWasteQty);
                    @endphp
                    <tr>
                        <td>{{ $size->name }}</td>
                        <td>{{ $orderQty }} Pcs</td>
                        <td>{{ $cuttingQty }} Pcs</td>
                        <td>{{ $alreadyReceivedQty }} Pcs</td>
                        <td id="available-quantity-{{ $size->id }}" data-max="{{ $maxQty }}">
                            {{ $availableQty }} Pcs
                        </td>
                        <td>
                            <input type="number" name="receive_quantities[{{ $size->id }}]"
                                class="form-control receive-qty-input" 
                                value="{{ $currentReceiveQty }}" 
                                min="0"
                                max="{{ $maxQty }}" 
                                data-size="{{ $size->id }}"
                                oninput="updateQuantities(this)">
                        </td>
                        <td>
                            <input type="number" name="receive_waste_quantities[{{ $size->id }}]"
                                class="form-control waste-qty-input" 
                                value="{{ $currentWasteQty }}" 
                                min="0"
                                max="{{ $maxQty }}" 
                                data-size="{{ $size->id }}"
                                oninput="updateQuantities(this)">
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td><strong>Total</strong></td>
                    <td><strong>{{ $totalOrderQuantity }} Pcs</strong></td>
                    <td><strong>{{ $totalCuttingQuantity }} Pcs</strong></td>
                    <td><strong>{{ $totalAlreadyReceivedQuantity }} Pcs</strong></td>
                    <td><strong id="total-available-quantity">{{ array_sum($availableQuantities) }} Pcs</strong></td>
                    <td><strong id="total-receive-quantity">{{ $totalCurrentReceive }}</strong></td>
                    <td><strong id="total-waste-quantity">{{ $totalCurrentWaste }}</strong></td>
                </tr>
            </tbody>
        </table>

        <a href="{{ route('print_receive_data.index') }}" class="btn btn-secondary mt-3">Back</a>
        <button type="submit" class="btn btn-primary mt-3">Update Print/Embroidery Receive Data</button>
    </form>

    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function updateQuantities(input) {
            const sizeId = input.getAttribute('data-size');
            const isReceiveInput = input.classList.contains('receive-qty-input');
            const maxQty = parseInt(document.getElementById(`available-quantity-${sizeId}`).getAttribute('data-max')) || 0;
            
            // Get current values
            const receiveInput = document.querySelector(`.receive-qty-input[data-size="${sizeId}"]`);
            const wasteInput = document.querySelector(`.waste-qty-input[data-size="${sizeId}"]`);
            
            const receiveValue = parseInt(receiveInput.value) || 0;
            const wasteValue = parseInt(wasteInput.value) || 0;
            
            // Validate that the sum doesn't exceed max quantity
            if (receiveValue + wasteValue > maxQty) {
                if (isReceiveInput) {
                    receiveInput.value = maxQty - wasteValue;
                    showWarning(`Total quantity cannot exceed available quantity (${maxQty}).`);
                } else {
                    wasteInput.value = maxQty - receiveValue;
                    showWarning(`Total quantity cannot exceed available quantity (${maxQty}).`);
                }
            }
            
            // Update available quantity for this size
            const newAvailable = maxQty - (parseInt(receiveInput.value) || 0) - (parseInt(wasteInput.value) || 0);
            document.getElementById(`available-quantity-${sizeId}`).textContent = `${newAvailable} Pcs`;
            
            // Update totals
            updateTotals();
        }
        
        function updateTotals() {
            let totalReceive = 0;
            let totalWaste = 0;
            let totalAvailable = 0;
            
            // Calculate totals from all size inputs
            document.querySelectorAll('.receive-qty-input').forEach(input => {
                totalReceive += parseInt(input.value) || 0;
            });
            
            document.querySelectorAll('.waste-qty-input').forEach(input => {
                totalWaste += parseInt(input.value) || 0;
            });
            
            document.querySelectorAll('[id^="available-quantity-"]').forEach(element => {
                totalAvailable += parseInt(element.textContent) || 0;
            });
            
            // Update the total row
            document.getElementById('total-receive-quantity').textContent = totalReceive;
            document.getElementById('total-waste-quantity').textContent = totalWaste;
            document.getElementById('total-available-quantity').textContent = `${totalAvailable} Pcs`;
        }
        
        function showWarning(message) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Input',
                text: message,
                timer: 2000,
                showConfirmButton: false
            });
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set up event listeners for all inputs
            document.querySelectorAll('.receive-qty-input, .waste-qty-input').forEach(input => {
                input.addEventListener('input', function() {
                    updateQuantities(this);
                });
            });
            
            // Initial calculation
            updateTotals();
        });
    </script>
</x-backend.layouts.master>