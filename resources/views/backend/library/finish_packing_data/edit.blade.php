<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Finish Packing Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Edit Finish Packing Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('finish_packing_data.index') }}">Finish Packing</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <x-backend.layouts.elements.errors />
    @if (session('message'))
        <div class="alert alert-success">
            <span class="close" data-dismiss="alert">&times;</span>
            <strong>{{ session('message') }}</strong>
        </div>
    @endif

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Edit Finish Packing Data</h3>
                        </div>
                        <form action="{{ route('finish_packing_data.update', $finishPackingDatum->id) }}" method="POST" id="finishPackingForm">
                            @csrf
                            @method('patch')
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="date">Date</label>
                                            <input type="date" name="date" id="date" class="form-control"
                                                value="{{ old('date', $finishPackingDatum->date) }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="po_number">PO Number</label>
                                            <input type="text" class="form-control"
                                                value="{{ $finishPackingDatum->po_number }}" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h5>Product Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="buyer">Buyer</label>
                                                    <input type="text" class="form-control"
                                                        value="{{ $finishPackingDatum->productCombination->buyer->name ?? 'N/A' }}"
                                                        readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="style">Style</label>
                                                    <input type="text" class="form-control"
                                                        value="{{ $finishPackingDatum->productCombination->style->name ?? 'N/A' }}"
                                                        readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="color">Color</label>
                                                    <input type="text" class="form-control"
                                                        value="{{ $finishPackingDatum->productCombination->color->name ?? 'N/A' }}"
                                                        readonly>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <table class="table table-bordered mt-4 text-center">
                                    <thead>
                                        <tr>
                                            <th>Size</th>
                                            <th>Order Quantity</th>
                                            <th>Total Output Quantity</th>
                                            <th>Available Quantity</th>
                                            <th>Current Packing Quantity</th>
                                            <th>Current Waste Quantity</th>
                                            <th>New Packing Quantity</th>
                                            <th>New Waste Quantity</th>
                                            <th>Remaining Available</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($sizeData as $size)
                                            <tr data-size-id="{{ $size['id'] }}" 
                                                data-available-quantity="{{ $size['available_quantity'] }}"
                                                data-current-packing="{{ $size['packing_quantity'] }}"
                                                data-current-waste="{{ $size['waste_quantity'] }}">
                                                <td>{{ $size['name'] }}</td>
                                                <td>{{ $size['order_quantity'] }}</td>
                                                <td>{{ $size['total_output_quantity'] }}</td>
                                                <td><span class="available-qty-display">{{ $size['available_quantity'] }}</span></td>
                                                <td>{{ $size['packing_quantity'] }}</td>
                                                <td>{{ $size['waste_quantity'] }}</td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number"
                                                            name="packing_quantities[{{ $size['id'] }}]"
                                                            class="form-control packing-qty-input" 
                                                            min="0"
                                                            value="{{ old('packing_quantities.' . $size['id'], $size['packing_quantity']) }}"
                                                            placeholder="Packing Qty">
                                                    </div>
                                                    @error('packing_quantities.' . $size['id'])
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number"
                                                            name="packing_waste_quantities[{{ $size['id'] }}]"
                                                            class="form-control waste-qty-input" 
                                                            min="0"
                                                            value="{{ old('packing_waste_quantities.' . $size['id'], $size['waste_quantity']) }}"
                                                            placeholder="Waste Qty">
                                                    </div>
                                                    @error('packing_waste_quantities.' . $size['id'])
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <span class="remaining-available text-success font-weight-bold">0</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="4" class="text-right"><strong>Total:</strong></td>
                                            <td><span id="total-current-packing">{{ $finishPackingDatum->total_packing_quantity }}</span></td>
                                            <td><span id="total-current-waste">{{ $finishPackingDatum->total_packing_waste_quantity }}</span></td>
                                            <td><span id="total-new-packing">0</span></td>
                                            <td><span id="total-new-waste">0</span></td>
                                            <td><span id="total-remaining">0</span></td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <a href="{{ route('finish_packing_data.index') }}"
                                            class="btn btn-secondary">Back to List</a>
                                        <button type="submit" class="btn btn-primary">Update Finish Packing Data</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const packingQtyInputs = document.querySelectorAll('.packing-qty-input');
            const wasteQtyInputs = document.querySelectorAll('.waste-qty-input');
            const totalNewPackingSpan = document.getElementById('total-new-packing');
            const totalNewWasteSpan = document.getElementById('total-new-waste');
            const totalRemainingSpan = document.getElementById('total-remaining');

            function calculateRemainingAvailable(row) {
                const availableQty = parseInt(row.getAttribute('data-available-quantity'));
                const currentPacking = parseInt(row.getAttribute('data-current-packing'));
                const currentWaste = parseInt(row.getAttribute('data-current-waste'));
                
                const packingInput = row.querySelector('.packing-qty-input');
                const wasteInput = row.querySelector('.waste-qty-input');
                
                const newPacking = parseInt(packingInput.value) || 0;
                const newWaste = parseInt(wasteInput.value) || 0;
                
                // Calculate remaining available: availableQty - (newPacking - currentPacking) - (newWaste - currentWaste)
                const remaining = availableQty - (newPacking - currentPacking) - (newWaste - currentWaste);
                
                return remaining;
            }

            function updateRowConstraints(row) {
                const remainingAvailable = calculateRemainingAvailable(row);
                const remainingDisplay = row.querySelector('.remaining-available');
                remainingDisplay.textContent = remainingAvailable;
                
                const packingInput = row.querySelector('.packing-qty-input');
                const wasteInput = row.querySelector('.waste-qty-input');
                
                const currentPacking = parseInt(row.getAttribute('data-current-packing'));
                const currentWaste = parseInt(row.getAttribute('data-current-waste'));
                const availableQty = parseInt(row.getAttribute('data-available-quantity'));
                
                const newPacking = parseInt(packingInput.value) || 0;
                const newWaste = parseInt(wasteInput.value) || 0;
                
                // Calculate maximum allowed values
                const maxPacking = currentPacking + availableQty + (currentWaste - newWaste);
                const maxWaste = currentWaste + availableQty + (currentPacking - newPacking);
                
                // Set max attributes
                packingInput.setAttribute('max', Math.max(0, maxPacking));
                wasteInput.setAttribute('max', Math.max(0, maxWaste));
                
                // Add visual feedback
                if (remainingAvailable < 0) {
                    remainingDisplay.classList.remove('text-success');
                    remainingDisplay.classList.add('text-danger');
                    // Show warning for negative remaining
                    remainingDisplay.title = 'Warning: Exceeds available quantity!';
                } else {
                    remainingDisplay.classList.remove('text-danger');
                    remainingDisplay.classList.add('text-success');
                    remainingDisplay.title = '';
                }
                
                return remainingAvailable;
            }

            function validateRowInputs(row) {
                const packingInput = row.querySelector('.packing-qty-input');
                const wasteInput = row.querySelector('.waste-qty-input');
                
                const currentPacking = parseInt(row.getAttribute('data-current-packing'));
                const currentWaste = parseInt(row.getAttribute('data-current-waste'));
                const availableQty = parseInt(row.getAttribute('data-available-quantity'));
                
                let newPacking = parseInt(packingInput.value) || 0;
                let newWaste = parseInt(wasteInput.value) || 0;
                
                // Ensure values are not negative
                if (newPacking < 0) {
                    newPacking = 0;
                    packingInput.value = 0;
                }
                
                if (newWaste < 0) {
                    newWaste = 0;
                    wasteInput.value = 0;
                }
                
                // Calculate total change
                const packingChange = newPacking - currentPacking;
                const wasteChange = newWaste - currentWaste;
                const totalChange = packingChange + wasteChange;
                
                // If total change exceeds available quantity, adjust the inputs
                if (totalChange > availableQty) {
                    const excess = totalChange - availableQty;
                    
                    // Try to reduce waste first, then packing
                    if (wasteChange >= excess) {
                        newWaste = Math.max(0, currentWaste + (wasteChange - excess));
                    } else {
                        const wasteReduction = wasteChange;
                        newWaste = currentWaste;
                        newPacking = Math.max(0, currentPacking + (packingChange - (excess - wasteReduction)));
                    }
                    
                    packingInput.value = newPacking;
                    wasteInput.value = newWaste;
                }
            }

            function updateTotals() {
                let totalPacking = 0;
                let totalWaste = 0;
                let totalRemaining = 0;

                packingQtyInputs.forEach(input => {
                    const value = parseInt(input.value) || 0;
                    totalPacking += value;
                });

                wasteQtyInputs.forEach(input => {
                    const value = parseInt(input.value) || 0;
                    totalWaste += value;
                });

                // Calculate total remaining available
                const rows = document.querySelectorAll('tbody tr[data-size-id]');
                rows.forEach(row => {
                    const remaining = calculateRemainingAvailable(row);
                    totalRemaining += Math.max(0, remaining); // Only count positive remaining
                });

                totalNewPackingSpan.textContent = totalPacking;
                totalNewWasteSpan.textContent = totalWaste;
                totalRemainingSpan.textContent = totalRemaining;
                
                // Add visual feedback to totals
                if (totalRemaining < 0) {
                    totalRemainingSpan.classList.remove('text-success');
                    totalRemainingSpan.classList.add('text-danger');
                } else {
                    totalRemainingSpan.classList.remove('text-danger');
                    totalRemainingSpan.classList.add('text-success');
                }
            }

            function handleInputChange(input) {
                const row = input.closest('tr');
                
                // First validate to ensure values are within bounds
                validateRowInputs(row);
                
                // Then update constraints and display
                updateRowConstraints(row);
                
                // Update totals
                updateTotals();
            }

            // Add event listeners to packing quantity inputs
            packingQtyInputs.forEach(input => {
                input.addEventListener('input', function() {
                    handleInputChange(this);
                });
                
                // Also validate on blur
                input.addEventListener('blur', function() {
                    handleInputChange(this);
                });
            });

            // Add event listeners to waste quantity inputs
            wasteQtyInputs.forEach(input => {
                input.addEventListener('input', function() {
                    handleInputChange(this);
                });
                
                // Also validate on blur
                input.addEventListener('blur', function() {
                    handleInputChange(this);
                });
            });

            // Initialize all rows and totals on page load
            packingQtyInputs.forEach(input => {
                const row = input.closest('tr');
                updateRowConstraints(row);
            });
            updateTotals();
            
            // Form submission validation
            document.getElementById('finishPackingForm').addEventListener('submit', function(e) {
                let hasErrors = false;
                const rows = document.querySelectorAll('tbody tr[data-size-id]');
                
                rows.forEach(row => {
                    const remaining = calculateRemainingAvailable(row);
                    if (remaining < 0) {
                        hasErrors = true;
                        const remainingDisplay = row.querySelector('.remaining-available');
                        remainingDisplay.classList.add('bg-danger', 'text-white');
                    }
                });
                
                if (hasErrors) {
                    e.preventDefault();
                    alert('Please fix the errors before submitting. Some quantities exceed available limits.');
                }
            });
        });
    </script>
</x-backend.layouts.master>