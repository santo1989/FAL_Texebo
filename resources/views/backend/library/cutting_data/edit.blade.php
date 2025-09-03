<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Cutting Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Edit Cutting Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('cutting_data.index') }}">Cutting Data</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Editing Cutting Data for PO: <strong
                                    class="text-primary">{{ $cuttingDatum->po_number }}</strong></h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('cutting_data.update', $cuttingDatum) }}" method="POST">
                                @csrf
                                @method('PUT')

                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="date">Date</label>
                                        <input type="date" name="date" class="form-control"
                                            value="{{ old('date', $cuttingDatum->date->format('Y-m-d')) }}" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label>Buyer</label>
                                        <p class="form-control-plaintext">
                                            <strong>{{ $cuttingDatum->productCombination->buyer->name ?? 'N/A' }}</strong>
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <label>Style</label>
                                        <p class="form-control-plaintext">
                                            <strong>{{ $cuttingDatum->productCombination->style->name ?? 'N/A' }}</strong>
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <label>Color</label>
                                        <p class="form-control-plaintext">
                                            <strong>{{ $cuttingDatum->productCombination->color->name ?? 'N/A' }}</strong>
                                        </p>
                                    </div>
                                </div>

                                <table class="table table-bordered table-hover mt-4">
                                    <thead>
                                        <tr>
                                            <th>Size</th>
                                            <th>Order Qty</th>
                                            <th>Max Allowed (Order + 5%)</th>
                                            <th>Already Cut (Other Records)</th>
                                            <th>Available Qty</th>
                                            <th>Cut Quantity</th>
                                            <th>Waste Quantity</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cutting-data-table-body">
                                        @foreach ($orderQuantities as $sizeId => $orderQty)
                                            @php
                                                // Find the Size model for the current size ID
                                                $size = $allSizes->firstWhere('id', $sizeId);
                                                // Skip if for some reason the size isn't found
                                                if (!$size) {
                                                    continue;
                                                }

                                                // Calculate 5% extra
                                                $maxAllowed = ceil($orderQty * 1.05);
                                                
                                                // Get already cut from other records
                                                $alreadyCut = $totalExistingCutQuantities[$sizeId] ?? 0;

                                                // Get current cut quantity
                                                $currentCutQty = $cuttingDatum->cut_quantities[$sizeId] ?? 0;

                                                // Calculate available quantity
                                                $availableQty = max(0, $maxAllowed - $alreadyCut - $currentCutQty);

                                                $currentCutQty = $cuttingDatum->cut_quantities[$sizeId] ?? 0;
                                                $currentWasteQty = $cuttingDatum->cut_waste_quantities[$sizeId] ?? 0;
                                            @endphp
                                            <tr>
                                                <td>{{ strtoupper($size->name) }}</td>
                                                <td>{{ $orderQty }}</td>
                                                <td>{{ $maxAllowed }}</td>
                                                <td>{{ $alreadyCut }}</td>
                                                <td class="available-qty"
                                                    data-max-allowed="{{ $maxAllowed }}"
                                                    data-already-cut="{{ $alreadyCut }}"
                                                    data-current-cut="{{ $currentCutQty }}"
                                                    data-current-waste="{{ $currentWasteQty }}">
                                                    {{ $availableQty }}
                                                </td>
                                                <td>
                                                    <input type="number" name="cut_quantities[{{ $sizeId }}]"
                                                        class="form-control form-control-sm cut-qty-input"
                                                        value="{{ old('cut_quantities.' . $sizeId, $currentCutQty) }}"
                                                        min="0" max="{{ $availableQty + $currentCutQty }}">
                                                </td>
                                                <td>
                                                    <input type="number" name="waste_quantities[{{ $sizeId }}]"
                                                        class="form-control form-control-sm waste-qty-input"
                                                        value="{{ old('waste_quantities.' . $sizeId, $currentWasteQty) }}"
                                                        min="0" max="{{ $availableQty + $currentWasteQty }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                <div class="d-flex justify-content-end mt-4">
                                    <!--back/cancel button-->
                                    <a href="{{ route('cutting_data.index') }}" class="btn btn-lg btn-secondary me-2">Back</a>
                                    <button type="submit" class="btn btn-lg btn-success">Update Data</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tableBody = document.getElementById('cutting-data-table-body');
        if (!tableBody) return;

        // Function to update the available quantity for a single row
        function updateAvailableQuantity(row) {
            const availableQtyCell = row.querySelector('.available-qty');
            const cutQtyInput = row.querySelector('.cut-qty-input');
            const wasteQtyInput = row.querySelector('.waste-qty-input');

            // Get the values from the data attributes
            const maxAllowed = parseInt(availableQtyCell.dataset.maxAllowed, 10);
            const alreadyCut = parseInt(availableQtyCell.dataset.alreadyCut, 10);
            const currentCut = parseInt(cutQtyInput.value) || 0;
            const currentWaste = parseInt(wasteQtyInput.value) || 0;

            // Calculate the new available quantity
            const newAvailable = maxAllowed - alreadyCut - currentCut - currentWaste;

            // Update the cell text with the new value
            availableQtyCell.textContent = Math.max(0, newAvailable);
            
            // Update the max attributes for the inputs
            cutQtyInput.setAttribute('max', Math.max(0, newAvailable + currentCut));
            wasteQtyInput.setAttribute('max', Math.max(0, newAvailable + currentWaste));

            
        }

        // Get all rows in the table body
        const rows = tableBody.querySelectorAll('tr');

        // Add event listeners to each row's input fields
        rows.forEach(row => {
            const cutQtyInput = row.querySelector('.cut-qty-input');
            const wasteQtyInput = row.querySelector('.waste-qty-input');

            if (cutQtyInput) {
                cutQtyInput.addEventListener('input', () => updateAvailableQuantity(row));
            }
            if (wasteQtyInput) {
                wasteQtyInput.addEventListener('input', () => updateAvailableQuantity(row));
            }
        });
    });
</script>