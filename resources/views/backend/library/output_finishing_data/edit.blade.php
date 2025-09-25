<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Output Finishing Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Edit Output Finishing Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('output_finishing_data.index') }}">Output Finishing</a></li>
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
                            <h3 class="card-title">Edit Output Finishing Data</h3>
                        </div>
                        <form action="{{ route('output_finishing_data.update', $outputFinishingDatum->id) }}"
                            method="POST" id="outputFinishingForm">
                            @csrf
                            @method('patch')
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="date">Date</label>
                                            <input type="date" name="date" id="date" class="form-control"
                                                value="{{ old('date', $outputFinishingDatum->date) }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="po_number">PO Number</label>
                                            <input type="text" class="form-control"
                                                value="{{ $outputFinishingDatum->po_number }}" readonly>
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
                                                        value="{{ $outputFinishingDatum->productCombination->buyer->name ?? 'N/A' }}"
                                                        readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="style">Style</label>
                                                    <input type="text" class="form-control"
                                                        value="{{ $outputFinishingDatum->productCombination->style->name ?? 'N/A' }}"
                                                        readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="color">Color</label>
                                                    <input type="text" class="form-control"
                                                        value="{{ $outputFinishingDatum->productCombination->color->name ?? 'N/A' }}"
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
                                            <th>Total Input Quantity</th>
                                            <th>Available Quantity</th>
                                            <th>Current Output Quantity</th>
                                            <th>Current Waste Quantity</th>
                                            <th>New Output Quantity</th>
                                            <th>New Waste Quantity</th>
                                            <th>Remaining Available</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($sizeData as $size)
                                            <tr data-size-id="{{ $size['id'] }}" 
                                                data-available-quantity="{{ $size['available_quantity'] }}"
                                                data-current-output="{{ $size['output_quantity'] }}"
                                                data-current-waste="{{ $size['waste_quantity'] }}">
                                                <td>{{ $size['name'] }}</td>
                                                <td>{{ $size['order_quantity'] }}</td>
                                                <td>{{ $size['total_input_quantity'] }}</td>
                                                <td><span class="available-qty-display">{{ $size['available_quantity'] }}</span></td>
                                                <td>{{ $size['output_quantity'] }}</td>
                                                <td>{{ $size['waste_quantity'] }}</td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number"
                                                            name="output_quantities[{{ $size['id'] }}]"
                                                            class="form-control output-qty-input" min="0"
                                                            value="{{ old('output_quantities.' . $size['id'], $size['output_quantity']) }}"
                                                            placeholder="Output Qty">
                                                    </div>
                                                    @error('output_quantities.' . $size['id'])
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number"
                                                            name="output_waste_quantities[{{ $size['id'] }}]"
                                                            class="form-control waste-qty-input" min="0"
                                                            value="{{ old('output_waste_quantities.' . $size['id'], $size['waste_quantity']) }}"
                                                            placeholder="Waste Qty">
                                                    </div>
                                                    @error('output_waste_quantities.' . $size['id'])
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
                                            <td><span id="total-current-output">{{ $outputFinishingDatum->total_output_quantity }}</span></td>
                                            <td><span id="total-current-waste">{{ $outputFinishingDatum->total_output_waste_quantity }}</span></td>
                                            <td><span id="total-new-output">0</span></td>
                                            <td><span id="total-new-waste">0</span></td>
                                            <td><span id="total-remaining">0</span></td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <a href="{{ route('output_finishing_data.index') }}"
                                            class="btn btn-secondary">Back to List</a>
                                        <button type="submit" class="btn btn-primary">Update Output Finishing Data</button>
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
            const outputQtyInputs = document.querySelectorAll('.output-qty-input');
            const wasteQtyInputs = document.querySelectorAll('.waste-qty-input');
            const totalNewOutputSpan = document.getElementById('total-new-output');
            const totalNewWasteSpan = document.getElementById('total-new-waste');
            const totalRemainingSpan = document.getElementById('total-remaining');

            function calculateRemainingAvailable(row) {
                const availableQty = parseInt(row.getAttribute('data-available-quantity'));
                const currentOutput = parseInt(row.getAttribute('data-current-output'));
                const currentWaste = parseInt(row.getAttribute('data-current-waste'));
                
                const outputInput = row.querySelector('.output-qty-input');
                const wasteInput = row.querySelector('.waste-qty-input');
                
                const newOutput = parseInt(outputInput.value) || 0;
                const newWaste = parseInt(wasteInput.value) || 0;
                
                // Calculate remaining available: availableQty - (newOutput - currentOutput) - (newWaste - currentWaste)
                const remaining = availableQty - (newOutput - currentOutput) - (newWaste - currentWaste);
                
                return Math.max(0, remaining);
            }

            function updateRowConstraints(row) {
                const remainingAvailable = calculateRemainingAvailable(row);
                const remainingDisplay = row.querySelector('.remaining-available');
                remainingDisplay.textContent = remainingAvailable;
                
                const outputInput = row.querySelector('.output-qty-input');
                const wasteInput = row.querySelector('.waste-qty-input');
                
                const currentOutput = parseInt(row.getAttribute('data-current-output'));
                const currentWaste = parseInt(row.getAttribute('data-current-waste'));
                const availableQty = parseInt(row.getAttribute('data-available-quantity'));
                
                // Calculate max values based on remaining available
                const newOutput = parseInt(outputInput.value) || 0;
                const newWaste = parseInt(wasteInput.value) || 0;
                
                // Max output = current output + remaining available + (current waste - new waste)
                const maxOutput = currentOutput + remainingAvailable + (currentWaste - newWaste);
                outputInput.setAttribute('max', maxOutput);
                
                // Max waste = current waste + remaining available + (current output - new output)
                const maxWaste = currentWaste + remainingAvailable + (currentOutput - newOutput);
                wasteInput.setAttribute('max', maxWaste);
                
                // Add visual feedback
                if (remainingAvailable < 0) {
                    remainingDisplay.classList.remove('text-success');
                    remainingDisplay.classList.add('text-danger');
                } else {
                    remainingDisplay.classList.remove('text-danger');
                    remainingDisplay.classList.add('text-success');
                }
                
                return remainingAvailable;
            }

            function validateRowInputs(row) {
                const outputInput = row.querySelector('.output-qty-input');
                const wasteInput = row.querySelector('.waste-qty-input');
                
                const currentOutput = parseInt(row.getAttribute('data-current-output'));
                const currentWaste = parseInt(row.getAttribute('data-current-waste'));
                const availableQty = parseInt(row.getAttribute('data-available-quantity'));
                
                let newOutput = parseInt(outputInput.value) || 0;
                let newWaste = parseInt(wasteInput.value) || 0;
                
                // Calculate total used
                const totalUsed = (newOutput - currentOutput) + (newWaste - currentWaste);
                
                // If total used exceeds available quantity, adjust the inputs
                if (totalUsed > availableQty) {
                    const excess = totalUsed - availableQty;
                    
                    // Try to reduce waste first, then output
                    if (newWaste - currentWaste >= excess) {
                        newWaste = Math.max(0, newWaste - excess);
                    } else {
                        const wasteReduction = newWaste - currentWaste;
                        newWaste = currentWaste;
                        newOutput = Math.max(0, newOutput - (excess - wasteReduction));
                    }
                    
                    outputInput.value = newOutput;
                    wasteInput.value = newWaste;
                }
                
                // Ensure values are not negative
                if (newOutput < 0) outputInput.value = 0;
                if (newWaste < 0) wasteInput.value = 0;
            }

            function updateTotals() {
                let totalOutput = 0;
                let totalWaste = 0;
                let totalRemaining = 0;

                outputQtyInputs.forEach(input => {
                    const value = parseInt(input.value) || 0;
                    totalOutput += value;
                });

                wasteQtyInputs.forEach(input => {
                    const value = parseInt(input.value) || 0;
                    totalWaste += value;
                });

                // Calculate total remaining available
                const rows = document.querySelectorAll('tbody tr[data-size-id]');
                rows.forEach(row => {
                    totalRemaining += calculateRemainingAvailable(row);
                });

                totalNewOutputSpan.textContent = totalOutput;
                totalNewWasteSpan.textContent = totalWaste;
                totalRemainingSpan.textContent = totalRemaining;
            }

            function handleInputChange(input) {
                const row = input.closest('tr');
                
                // First validate to ensure sum doesn't exceed available
                validateRowInputs(row);
                
                // Then update constraints and display
                updateRowConstraints(row);
                
                // Update totals
                updateTotals();
            }

            // Add event listeners to output quantity inputs
            outputQtyInputs.forEach(input => {
                input.addEventListener('input', function() {
                    handleInputChange(this);
                });
            });

            // Add event listeners to waste quantity inputs
            wasteQtyInputs.forEach(input => {
                input.addEventListener('input', function() {
                    handleInputChange(this);
                });
            });

            // Initialize all rows and totals
            outputQtyInputs.forEach(input => {
                const row = input.closest('tr');
                updateRowConstraints(row);
            });
            updateTotals();
        });
    </script>
</x-backend.layouts.master>