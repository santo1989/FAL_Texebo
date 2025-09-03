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

    <form action="{{ route('sublimation_print_receive_data.update', $sublimationPrintReceiveDatum->id) }}" method="post">
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

        <table class="table table-bordered mt-4 text-center">
            <thead>
                <tr>
                    <th>Size</th>
                    <th>Sent Quantity</th>
                    <th>Already Received</th>
                    <th>Available to Receive</th>
                    <th>Receive Quantity</th>
                    <th>Waste Quantity</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Get the corresponding send data
                    $sendData = App\Models\SublimationPrintSend::where('po_number', $sublimationPrintReceiveDatum->po_number)
                        ->where('product_combination_id', $sublimationPrintReceiveDatum->product_combination_id)
                        ->first();
                    
                    // Calculate already received quantities for this product combination and PO
                    $alreadyReceived = App\Models\SublimationPrintReceive::where('po_number', $sublimationPrintReceiveDatum->po_number)
                        ->where('product_combination_id', $sublimationPrintReceiveDatum->product_combination_id)
                        ->where('id', '!=', $sublimationPrintReceiveDatum->id) // Exclude current record
                        ->get()
                        ->reduce(function ($carry, $receive) {
                            foreach ($receive->sublimation_print_receive_quantities as $sizeId => $qty) {
                                $carry[$sizeId] = ($carry[$sizeId] ?? 0) + $qty;
                            }
                            return $carry;
                        }, []);
                @endphp
                
                @if($sendData)
                    @foreach ($allSizes as $size)
                        @php
                            $sizeId = $size->id;
                            $sentQty = $sendData->sublimation_print_send_quantities[$sizeId] ?? 0;
                            $alreadyReceivedQty = $alreadyReceived[$sizeId] ?? 0;
                            $availableQty = max(0, $sentQty - $alreadyReceivedQty);
                            $currentReceiveQty = $sublimationPrintReceiveDatum->sublimation_print_receive_quantities[$sizeId] ?? 0;
                            $currentWasteQty = $sublimationPrintReceiveDatum->sublimation_print_receive_waste_quantities[$sizeId] ?? 0;
                        @endphp
                        
                        @if($sentQty > 0)
                            <tr>
                                <td>{{ $size->name }}</td>
                                <td>{{ $sentQty }}</td>
                                <td>{{ $alreadyReceivedQty }}</td>
                                <td>{{ $availableQty }}</td>
                                <td>
                                    <input type="number" 
                                           name="sublimation_print_receive_quantities[{{ $sizeId }}]"
                                           class="form-control receive-qty-input" 
                                           min="0" 
                                           max="{{ $availableQty + $currentReceiveQty }}"
                                           value="{{ old('sublimation_print_receive_quantities.' . $sizeId, $currentReceiveQty) }}"
                                           placeholder="Receive Qty">
                                </td>
                                <td>
                                    <input type="number" 
                                           name="sublimation_print_receive_waste_quantities[{{ $sizeId }}]"
                                           class="form-control waste-qty-input" 
                                           min="0"
                                           value="{{ old('sublimation_print_receive_waste_quantities.' . $sizeId, $currentWasteQty) }}"
                                           placeholder="Waste Qty">
                                </td>
                            </tr>
                        @endif
                    @endforeach
                @else
                    <tr>
                        <td colspan="6" class="text-center">No send data found for this PO and product combination</td>
                    </tr>
                @endif
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4"><strong>Totals</strong></td>
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

            // Add event listeners to all quantity inputs
            receiveInputs.forEach(input => {
                input.addEventListener('input', calculateTotals);
                
                // Add validation to ensure receive quantity doesn't exceed available + current
                input.addEventListener('change', function() {
                    const max = parseInt(this.getAttribute('max')) || 0;
                    const value = parseInt(this.value) || 0;
                    
                    if (value > max) {
                        this.value = max;
                        alert(`Receive quantity cannot exceed available quantity (${max})`);
                    }
                    
                    calculateTotals();
                });
            });

            wasteInputs.forEach(input => {
                input.addEventListener('input', calculateTotals);
            });

            // Initial calculation
            calculateTotals();
        });
    </script>
</x-backend.layouts.master>