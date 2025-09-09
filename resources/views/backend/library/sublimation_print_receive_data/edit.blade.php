<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Sublimation Print/Receive Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Sublimation Print/Receive Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('sublimation_print_receive_data.index') }}">Sublimation Print/Receive Data</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    @if (session('message'))
        <div class="alert alert-success">
            <span class="close" data-dismiss="alert">&times;</span>
            <strong>{{ session('message') }}.</strong>
        </div>
    @elseif (session('error'))
        <div class="alert alert-danger">
            <span class="close" data-dismiss="alert">&times;</span>
            <strong>{{ session('error') }}.</strong>
        </div>
    @endif

    <x-backend.layouts.elements.errors />

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <form action="{{ route('sublimation_print_receive_data.update', $sublimationPrintReceiveDatum->id) }}" method="post" id="receiveForm">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" name="date" id="date" class="form-control"
                        value="{{ old('date', $sublimationPrintReceiveDatum->date) }}" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="po_number">PO Number</label>
                    <input type="text" class="form-control" value="{{ $sublimationPrintReceiveDatum->po_number }}"
                        readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="old_order">Old Order</label>
                    <input type="text" class="form-control" value="{{ $sublimationPrintReceiveDatum->old_order }}"
                        readonly>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="buyer">Buyer</label>
                    <input type="text" class="form-control"
                        value="{{ $sublimationPrintReceiveDatum->productCombination->buyer->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="style">Style</label>
                    <input type="text" class="form-control"
                        value="{{ $sublimationPrintReceiveDatum->productCombination->style->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="color">Color</label>
                    <input type="text" class="form-control"
                        value="{{ $sublimationPrintReceiveDatum->productCombination->color->name ?? 'N/A' }}" readonly>
                </div>
            </div>
        </div>

        <!-- Order Quantity and Cutting Quantity Information -->
        <div class="row mt-3">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="order_quantity">Total Order Quantity</label>
                    @php
                         $orderQuantities = App\Models\OrderData::where('po_number', $sublimationPrintReceiveDatum->po_number)
                        ->where('product_combination_id', $sublimationPrintReceiveDatum->product_combination_id)
                        ->get()->reduce(function ($carry, $order) {
                            $quantities = $order->order_quantities ?? [];
                            foreach ($quantities as $sizeId => $qty) {
                                $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                            }
                            return $carry;
                        }, []);
                        $totalOrderQuantity = array_sum($orderQuantities);
                    @endphp
                    <input type="text" class="form-control"
                        value="{{ $totalOrderQuantity }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="cutting_quantity">Total Cutting Quantity</label>
                    @php
                        $cuttingData = App\Models\CuttingData::where('product_combination_id', $sublimationPrintReceiveDatum->product_combination_id)
                            ->where('po_number', $sublimationPrintReceiveDatum->po_number)
                            ->get();
                        $cuttingQuantity = $cuttingData->reduce(function ($carry, $item) {
                            $quantities = $item->cut_quantities ?? [];
                            return $carry + array_sum($quantities);
                        }, 0);
                    @endphp
                    <input type="text" class="form-control" value="{{ $cuttingQuantity }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="total_quantity">Total Sent Quantity</label>
                    @php
                        // Get all send data records and sum quantities
                        $sendData = App\Models\SublimationPrintSend::where('po_number', $sublimationPrintReceiveDatum->po_number)
                            ->where('product_combination_id', $sublimationPrintReceiveDatum->product_combination_id)
                            ->get();
                        
                        $totalSentQuantity = 0;
                        $sentQuantitiesBySize = [];
                        
                        foreach ($sendData as $send) {
                            $quantities = $send->sublimation_print_send_quantities ?? [];
                            foreach ($quantities as $sizeId => $qty) {
                                $sentQuantitiesBySize[$sizeId] = ($sentQuantitiesBySize[$sizeId] ?? 0) + $qty;
                                $totalSentQuantity += $qty;
                            }
                        }
                    @endphp
                    <input type="text" class="form-control" value="{{ $totalSentQuantity }}" readonly>
                </div>
            </div>
        </div>

        <table class="table table-bordered mt-4 text-center">
            <thead>
                <tr>
                    <th>Size</th>
                    <th>Order Quantity</th>
                    <th>Cutting Quantity</th>
                    <th>Sent Quantity</th>
                    <th>Already Received</th>
                    <th>Available to Receive</th>
                    <th>Receive Quantity</th>
                    <th>Waste Quantity</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Calculate already received quantities for this product combination and PO
                    $alreadyReceived = App\Models\SublimationPrintReceive::where('po_number', $sublimationPrintReceiveDatum->po_number)
                        ->where('product_combination_id', $sublimationPrintReceiveDatum->product_combination_id)
                        ->where('id', '!=', $sublimationPrintReceiveDatum->id)
                        ->get()
                        ->reduce(function ($carry, $receive) {
                            $quantities = $receive->sublimation_print_receive_quantities ?? [];
                            foreach ($quantities as $sizeId => $qty) {
                                $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                            }
                            return $carry;
                        }, []);

                    $orderQuantities = App\Models\OrderData::where('po_number', $sublimationPrintReceiveDatum->po_number)
                        ->where('product_combination_id', $sublimationPrintReceiveDatum->product_combination_id)
                        ->get()->reduce(function ($carry, $order) {
                            $quantities = $order->order_quantities ?? [];
                            foreach ($quantities as $sizeId => $qty) {
                                $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                            }
                            return $carry;
                        }, []);

                    $cuttingData = App\Models\CuttingData::where('po_number', $sublimationPrintReceiveDatum->po_number)
                        ->where('product_combination_id', $sublimationPrintReceiveDatum->product_combination_id)
                        ->get()->reduce(function ($carry, $cutting) {
                            $quantities = $cutting->cut_quantities ?? [];
                            foreach ($quantities as $sizeId => $qty) {
                                $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                            }
                            return $carry;
                        }, []);
                @endphp
                
                @if(count($sentQuantitiesBySize) > 0)
                    @foreach ($allSizes as $size)
                        @php
                            $sizeId = $size->id;
                            $sentQty = $sentQuantitiesBySize[$sizeId] ?? 0;
                            $alreadyReceivedQty = $alreadyReceived[$sizeId] ?? 0;
                            $availableQty = max(0, $sentQty - $alreadyReceivedQty);
                            $currentReceiveQty = $sublimationPrintReceiveDatum->sublimation_print_receive_quantities[$sizeId] ?? 0;
                            $currentWasteQty = $sublimationPrintReceiveDatum->sublimation_print_receive_waste_quantities[$sizeId] ?? 0;
                            
                            // Calculate max allowed
                            $maxAllowed = $availableQty;
                            $orderQty = $orderQuantities[$sizeId] ?? 0;
                            $cuttingQty = $cuttingData[$sizeId] ?? 0;
                        @endphp
                        
                        @if($sentQty > 0)
                            <tr data-size-id="{{ $sizeId }}">
                                <td>{{ $size->name }}</td>
                                <td>{{ $orderQty }}</td>
                                <td>{{ $cuttingQty }}</td>
                                <td class="sent-qty">{{ $sentQty }}</td>
                                <td class="already-received">{{ $alreadyReceivedQty }}</td>
                                <td class="available-qty">{{ $availableQty }}</td>
                                <td>
                                    <input type="number" 
                                           name="sublimation_print_receive_quantities[{{ $sizeId }}]"
                                           class="form-control receive-qty-input" 
                                           min="0" 
                                           max="{{ $maxAllowed }}"
                                           data-max-allowed="{{ $maxAllowed }}"
                                           data-sent-qty="{{ $sentQty }}"
                                           data-already-received="{{ $alreadyReceivedQty }}"
                                           value="{{ old('sublimation_print_receive_quantities.' . $sizeId, $currentReceiveQty) }}"
                                           placeholder="Receive Qty"
                                           onchange="validateQuantity(this)">
                                    <div class="invalid-feedback" id="error-{{ $sizeId }}" style="display: none;">
                                        Receive quantity cannot exceed available quantity ({{ $maxAllowed }})
                                    </div>
                                </td>
                                <td>
                                    <input type="number" 
                                           name="sublimation_print_receive_waste_quantities[{{ $sizeId }}]"
                                           class="form-control waste-qty-input" 
                                           min="0"
                                           max="{{ $maxAllowed + $currentReceiveQty }}"
                                           value="{{ old('sublimation_print_receive_waste_quantities.' . $sizeId, $currentWasteQty) }}"
                                           placeholder="Waste Qty">
                                </td>
                            </tr>
                        @endif
                    @endforeach
                @else
                    <tr>
                        <td colspan="8" class="text-center">No send data found for this PO and product combination</td>
                    </tr>
                @endif
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6"><strong>Totals</strong></td>
                    <td><span id="total-receive-qty">{{ $sublimationPrintReceiveDatum->total_sublimation_print_receive_quantity }}</span></td>
                    <td><span id="total-waste-qty">{{ $sublimationPrintReceiveDatum->total_sublimation_print_receive_waste_quantity }}</span></td>
                </tr>
            </tfoot>
        </table>

        <a href="{{ route('sublimation_print_receive_data.index') }}" class="btn btn-secondary mt-3">Back</a>
        <button type="submit" class="btn btn-primary mt-3">Update Sublimation Print/Receive Data</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const receiveInputs = document.querySelectorAll('.receive-qty-input');
            const wasteInputs = document.querySelectorAll('.waste-qty-input');
            const totalReceiveSpan = document.getElementById('total-receive-qty');
            const totalWasteSpan = document.getElementById('total-waste-qty');
            const form = document.getElementById('receiveForm');

            // Adjust values on load if they exceed max allowed
            receiveInputs.forEach(input => {
                const maxAllowed = parseInt(input.getAttribute('data-max-allowed')) || 0;
                let value = parseInt(input.value) || 0;
                if (value > maxAllowed) {
                    input.value = maxAllowed;
                    const sizeId = input.name.match(/\d+/)[0];
                    const errorElement = document.getElementById(`error-${sizeId}`);
                    errorElement.style.display = 'block';
                }
            });

            function calculateTotals() {
                let totalReceive = 0;
                let totalWaste = 0;

                receiveInputs.forEach(input => {
                    totalReceive += parseInt(input.value) || 0;
                });

                wasteInputs.forEach(input => {
                    totalWaste += parseInt(input.value) || 0;
                });

                totalReceiveSpan.textContent = totalReceive;
                totalWasteSpan.textContent = totalWaste;
            }

            // Validate quantity input
            window.validateQuantity = function(input) {
                const maxAllowed = parseInt(input.getAttribute('data-max-allowed')) || 0;
                const value = parseInt(input.value) || 0;
                const sizeId = input.name.match(/\d+/)[0];
                const errorElement = document.getElementById(`error-${sizeId}`);
                
                if (value > maxAllowed) {
                    input.value = maxAllowed;
                    errorElement.style.display = 'block';
                } else {
                    errorElement.style.display = 'none';
                }
                
                calculateTotals();
            };

            // Add event listeners to all quantity inputs
            receiveInputs.forEach(input => {
                input.addEventListener('input', function() {
                    validateQuantity(this);
                });
            });

            wasteInputs.forEach(input => {
                input.addEventListener('input', calculateTotals);
            });

            // Form validation before submission
            form.addEventListener('submit', function(e) {
                let isValid = true;
                let errorMessage = '';
                
                receiveInputs.forEach(input => {
                    const maxAllowed = parseInt(input.getAttribute('data-max-allowed')) || 0;
                    const value = parseInt(input.value) || 0;
                    
                    if (value > maxAllowed) {
                        isValid = false;
                        const sizeName = input.closest('tr').querySelector('td:first-child').textContent;
                        errorMessage = `Receive quantity for ${sizeName} cannot exceed ${maxAllowed}`;
                        return false; // Break out of loop
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert(errorMessage);
                }
            });

            // Initial calculation
            calculateTotals();
        });
    </script>
</x-backend.layouts.master>