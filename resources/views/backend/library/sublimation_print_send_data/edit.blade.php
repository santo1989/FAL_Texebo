<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Sublimation Print/Send Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Sublimation Print/Send Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('sublimation_print_send_data.index') }}">Sublimation Print/Send
                    Data</a></li>
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

    <x-backend.layouts.elements.errors />
    <form action="{{ route('sublimation_print_send_data.update', $sublimationPrintSendDatum->id) }}" method="post">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" name="date" id="date" class="form-control"
                        value="{{ old('date', $sublimationPrintSendDatum->date) }}" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="po_number">PO Number</label>
                    <input type="text" class="form-control" value="{{ $sublimationPrintSendDatum->po_number }}"
                        readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="old_order">Old Order</label>
                    <input type="text" class="form-control" value="{{ $sublimationPrintSendDatum->old_order }}"
                        readonly>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="buyer">Buyer</label>
                    <input type="text" class="form-control"
                        value="{{ $sublimationPrintSendDatum->productCombination->buyer->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="style">Style</label>
                    <input type="text" class="form-control"
                        value="{{ $sublimationPrintSendDatum->productCombination->style->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="color">Color</label>
                    <input type="text" class="form-control"
                        value="{{ $sublimationPrintSendDatum->productCombination->color->name ?? 'N/A' }}" readonly>
                </div>
            </div>
        </div>

        <table class="table table-bordered mt-4 text-center">
            <thead>
                <tr>
                    <th>Size</th>
                    <th>Order Quantity</th>
                    <th>Cutting Quantity</th>
                    <th>Available Quantity</th>
                    <th>Send Quantity</th>
                    <th>Waste Quantity</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $poNumbers = explode(',', $sublimationPrintSendDatum->po_number);
                @endphp
                @foreach ($allSizes as $size)
                    @php
                        $sizeName = $size->name;
                        $sizeId = $size->id;
                        $availableQty = $availableQuantities[$sizeName] ?? 0;
                        $currentSendQty = $sendQuantities[$sizeId] ?? 0;
                        $currentWasteQty = $wasteQuantities[$sizeId] ?? 0;
                        $maxAllowed = $availableQty + $currentSendQty;
                        
                        // Calculate order quantity based on PO numbers
                        $orderQty = 0;
                        $orderData = App\Models\OrderData::where('product_combination_id', $sublimationPrintSendDatum->productCombination->id)
                            ->whereIn('po_number', $poNumbers)
                            ->get();
                        foreach ($orderData as $data) {
                            $orderQuantities = (array) $data->order_quantities;
                            foreach ($orderQuantities as $key => $value) {
                                if (is_numeric($key)) {
                                    if ($key == $sizeId) {
                                        $orderQty += $value;
                                    }
                                } else {
                                    if (strtolower(trim($key)) == strtolower(trim($sizeName))) {
                                        $orderQty += $value;
                                    }
                                }
                            }
                        }
                        
                        // Calculate cutting quantity based on PO numbers
                        $cuttingQty = 0;
                        $cuttingData = App\Models\CuttingData::where('product_combination_id', $sublimationPrintSendDatum->productCombination->id)
                            ->whereIn('po_number', $poNumbers)
                            ->get();
                        foreach ($cuttingData as $data) {
                            $cutQuantities = (array) $data->cut_quantities;
                            foreach ($cutQuantities as $key => $value) {
                                if (is_numeric($key)) {
                                    if ($key == $sizeId) {
                                        $cuttingQty += $value;
                                    }
                                } else {
                                    if (strtolower(trim($key)) == strtolower(trim($sizeName))) {
                                        $cuttingQty += $value;
                                    }
                                }
                            }
                        }
                    @endphp
                    <tr>
                        <td>{{ $sizeName }}</td>
                        <td>{{ $orderQty }}</td>
                        <td>{{ $cuttingQty }}</td>
                        <td>
                            <span class="available-qty" data-size="{{ $sizeName }}">{{ $availableQty }}</span>
                        </td>
                        <td>
                            <input type="number" name="sublimation_print_send_quantities[{{ $sizeId }}]"
                                class="form-control send-qty-input" min="0" max="{{ $maxAllowed }}"
                                value="{{ old('sublimation_print_send_quantities.' . $sizeId, $currentSendQty) }}"
                                placeholder="Send Qty" data-size="{{ $sizeName }}">
                        </td>
                        <td>
                            <input type="number" name="sublimation_print_send_waste_quantities[{{ $sizeId }}]"
                                class="form-control waste-qty-input" min="0"
                                value="{{ old('sublimation_print_send_waste_quantities.' . $sizeId, $currentWasteQty) }}"
                                placeholder="Waste Qty">
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4"><strong>Totals</strong></td>
                    <td><span
                            id="total-send-qty">{{ $sublimationPrintSendDatum->total_sublimation_print_send_quantity }}</span>
                    </td>
                    <td><span
                            id="total-waste-qty">{{ $sublimationPrintSendDatum->total_sublimation_print_send_waste_quantity }}</span>
                    </td>
                </tr>
            </tfoot>
        </table>

        <a href="{{ route('sublimation_print_send_data.index') }}" class="btn btn-secondary mt-3">Back</a>
        <button type="submit" class="btn btn-primary mt-3">Update Sublimation Print/Send Data</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sendInputs = document.querySelectorAll('.send-qty-input');
            const wasteInputs = document.querySelectorAll('.waste-qty-input');
            const totalSendSpan = document.getElementById('total-send-qty');
            const totalWasteSpan = document.getElementById('total-waste-qty');
            const availableQuantities = @json($availableQuantities);

            function calculateTotals() {
                let totalSend = 0;
                let totalWaste = 0;

                sendInputs.forEach(input => {
                    totalSend += parseInt(input.value) || 0;
                });

                wasteInputs.forEach(input => {
                    totalWaste += parseInt(input.value) || 0;
                });

                totalSendSpan.textContent = totalSend;
                totalWasteSpan.textContent = totalWaste;
            }

            // Add event listeners to all quantity inputs
            sendInputs.forEach(input => {
                input.addEventListener('input', function() {
                    calculateTotals();
                    validateQuantity(this);
                });
            });

            wasteInputs.forEach(input => {
                input.addEventListener('input', calculateTotals);
            });

            // Validate quantity against available quantity
            function validateQuantity(input) {
                const sizeName = input.getAttribute('data-size');
                const availableQty = parseInt(document.querySelector(`.available-qty[data-size="${sizeName}"]`).textContent);
                const currentValue = parseInt(input.value) || 0;
                const currentSendQty = parseInt("{{ $currentSendQty }}") || 0;
                const maxAllowed = availableQty;

                if (currentValue > maxAllowed) {
                    input.setCustomValidity(`Quantity cannot exceed ${maxAllowed} for ${sizeName}`);
                    input.reportValidity();
                } else {
                    input.setCustomValidity('');
                }
            }

            // Initial calculation
            calculateTotals();
        });
    </script>
</x-backend.layouts.master>

{{-- <x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Sublimation Print/Send Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Sublimation Print/Send Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('sublimation_print_send_data.index') }}">Sublimation Print/Send
                    Data</a></li>
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

    <x-backend.layouts.elements.errors />
    <form action="{{ route('sublimation_print_send_data.update', $sublimationPrintSendDatum->id) }}" method="post">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" name="date" id="date" class="form-control"
                        value="{{ old('date', $sublimationPrintSendDatum->date) }}" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="po_number">PO Number</label>
                    <input type="text" class="form-control" value="{{ $sublimationPrintSendDatum->po_number }}"
                        readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="old_order">Old Order</label>
                    <input type="text" class="form-control" value="{{ $sublimationPrintSendDatum->old_order }}"
                        readonly>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="buyer">Buyer</label>
                    <input type="text" class="form-control"
                        value="{{ $sublimationPrintSendDatum->productCombination->buyer->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="style">Style</label>
                    <input type="text" class="form-control"
                        value="{{ $sublimationPrintSendDatum->productCombination->style->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="color">Color</label>
                    <input type="text" class="form-control"
                        value="{{ $sublimationPrintSendDatum->productCombination->color->name ?? 'N/A' }}" readonly>
                </div>
            </div>
        </div>

        <table class="table table-bordered mt-4 text-center">
            <thead>
                <tr>
                    <th>Size</th>
                    <th>Order Quantity</th>
                    <th>Cutting Quantity</th>
                    <th>Available Quantity</th>
                    <th>Send Quantity</th>
                    <th>Waste Quantity</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($allSizes as $size)
                    @php
                        $sizeName = $size->name;
                        $sizeId = $size->id;
                        $availableQty = $availableQuantities[$sizeName] ?? 0;
                        $currentSendQty = $sendQuantities[$sizeId] ?? 0;
                        $currentWasteQty = $wasteQuantities[$sizeId] ?? 0;
                        $maxAllowed = $availableQty + $currentSendQty;
                        
                        // Fixed: Properly retrieve order and cutting quantities
                        $orderQty = 0;
                        $cuttingQty = 0;
                        
                        // Get order quantities
                        $orderData = App\Models\OrderData::where('product_combination_id', $sublimationPrintSendDatum->productCombination->id)->get();
                        foreach ($orderData as $data) {
                            $orderQuantities = (array) $data->order_quantities;
                            foreach ($orderQuantities as $key => $value) {
                                if (is_numeric($key)) {
                                    // If key is numeric (size ID), check if it matches current size ID
                                    if ($key == $sizeId) {
                                        $orderQty += $value;
                                    }
                                } else {
                                    // If key is string (size name), check if it matches current size name
                                    if (strtolower(trim($key)) == strtolower(trim($sizeName))) {
                                        $orderQty += $value;
                                    }
                                }
                            }
                        }
                        
                        // Get cutting quantities
                        $cuttingData = App\Models\CuttingData::where('product_combination_id', $sublimationPrintSendDatum->productCombination->id)->get();
                        foreach ($cuttingData as $data) {
                            $cutQuantities = (array) $data->cut_quantities;
                            foreach ($cutQuantities as $key => $value) {
                                if (is_numeric($key)) {
                                    // If key is numeric (size ID), check if it matches current size ID
                                    if ($key == $sizeId) {
                                        $cuttingQty += $value;
                                    }
                                } else {
                                    // If key is string (size name), check if it matches current size name
                                    if (strtolower(trim($key)) == strtolower(trim($sizeName))) {
                                        $cuttingQty += $value;
                                    }
                                }
                            }
                        }
                    @endphp
                    <tr>
                        <td>{{ $sizeName }}</td>
                        <td>{{ $orderQty }}</td>
                        <td>{{ $cuttingQty }}</td>
                        <td>{{ $availableQty }}</td>
                        <td>
                            <input type="number" name="sublimation_print_send_quantities[{{ $sizeId }}]"
                                class="form-control send-qty-input" min="0" max="{{ $maxAllowed }}"
                                value="{{ old('sublimation_print_send_quantities.' . $sizeId, $currentSendQty) }}"
                                placeholder="Send Qty">
                        </td>
                        <td>
                            <input type="number" name="sublimation_print_send_waste_quantities[{{ $sizeId }}]"
                                class="form-control waste-qty-input" min="0"
                                value="{{ old('sublimation_print_send_waste_quantities.' . $sizeId, $currentWasteQty) }}"
                                placeholder="Waste Qty">
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4"><strong>Totals</strong></td>
                    <td><span
                            id="total-send-qty">{{ $sublimationPrintSendDatum->total_sublimation_print_send_quantity }}</span>
                    </td>
                    <td><span
                            id="total-waste-qty">{{ $sublimationPrintSendDatum->total_sublimation_print_send_waste_quantity }}</span>
                    </td>
                </tr>
            </tfoot>
        </table>

        <a href="{{ route('sublimation_print_send_data.index') }}" class="btn btn-secondary mt-3">Back</a>
        <button type="submit" class="btn btn-primary mt-3">Update Sublimation Print/Send Data</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sendInputs = document.querySelectorAll('.send-qty-input');
            const wasteInputs = document.querySelectorAll('.waste-qty-input');
            const totalSendSpan = document.getElementById('total-send-qty');
            const totalWasteSpan = document.getElementById('total-waste-qty');
            const availableQuantities = @json($availableQuantities);

            function calculateTotals() {
                let totalSend = 0;
                let totalWaste = 0;

                sendInputs.forEach(input => {
                    totalSend += parseInt(input.value) || 0;
                });

                wasteInputs.forEach(input => {
                    totalWaste += parseInt(input.value) || 0;
                });

                totalSendSpan.textContent = totalSend;
                totalWasteSpan.textContent = totalWaste;
            }

            // Add event listeners to all quantity inputs
            sendInputs.forEach(input => {
                input.addEventListener('input', calculateTotals);
            });

            wasteInputs.forEach(input => {
                input.addEventListener('input', calculateTotals);
            });

            // Initial calculation
            calculateTotals();
        });
    </script>
</x-backend.layouts.master> --}}