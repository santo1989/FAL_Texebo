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
                    <th>Available Quantity</th>
                    <th>Send Quantity</th>
                    <th>Waste Quantity</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Get available quantities for this product combination
                    $availableResponse = app('App\Http\Controllers\PrintSendDataController')
                        ->getAvailableSendQuantities($printSendDatum->productCombination);
                    $availableData = json_decode($availableResponse->content(), true);
                    $availableQuantities = $availableData['availableQuantities'] ?? [];
                    
                    // Get the size IDs for this product combination
                    $validSizeIds = $printSendDatum->productCombination->size_ids;
                    $validSizes = \App\Models\Size::whereIn('id', $validSizeIds)
                        ->where('is_active', 1)
                        ->orderBy('id', 'asc')
                        ->get();
                @endphp
                
                @foreach ($validSizes as $size)
                    @php
                        $availableQty = $availableQuantities[$size->name] ?? 0;
                        $sendQty = $printSendDatum->send_quantities[$size->id] ?? 0;
                        $wasteQty = $printSendDatum->send_waste_quantities[$size->id] ?? 0;
                        $maxQty = $availableQty + $sendQty;
                    @endphp
                    <tr>
                        <td>{{ $size->name }}</td>
                        <td>{{ $availableQty }} Pcs</td>
                        <td>
                            <input type="number" 
                                name="send_quantities[{{ $size->id }}]" 
                                class="form-control send-qty-input" 
                                value="{{ $sendQty }}" 
                                min="0" 
                                max="{{ $maxQty }}"
                                data-max="{{ $maxQty }}"
                                oninput="validateQuantity(this)">
                        </td>
                        <td>
                            <input type="number" 
                                name="send_waste_quantities[{{ $size->id }}]" 
                                class="form-control waste-qty-input" 
                                value="{{ $wasteQty }}" 
                                min="0" 
                                max="{{ $maxQty }}"
                                oninput="validateWasteQuantity(this, {{ $maxQty }})">
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td><strong>Total</strong></td>
                    <td><strong>{{ array_sum($availableQuantities) }} Pcs</strong></td>
                    <td><strong>{{ $printSendDatum->total_send_quantity }}</strong></td>
                    <td><strong>{{ $printSendDatum->total_send_waste_quantity }}</strong></td>
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
        }
        
        // Calculate and update totals on input
        document.addEventListener('DOMContentLoaded', function() {
            const sendInputs = document.querySelectorAll('.send-qty-input');
            const wasteInputs = document.querySelectorAll('.waste-qty-input');
            
            function updateTotals() {
                let totalSend = 0;
                let totalWaste = 0;
                
                sendInputs.forEach(input => {
                    totalSend += parseInt(input.value) || 0;
                });
                
                wasteInputs.forEach(input => {
                    totalWaste += parseInt(input.value) || 0;
                });
                
                // Update the total row
                const totalCells = document.querySelectorAll('tbody tr:last-child td');
                totalCells[2].textContent = totalSend;
                totalCells[3].textContent = totalWaste;
            }
            
            sendInputs.forEach(input => {
                input.addEventListener('input', updateTotals);
            });
            
            wasteInputs.forEach(input => {
                input.addEventListener('input', updateTotals);
            });
        });
    </script>
</x-backend.layouts.master>