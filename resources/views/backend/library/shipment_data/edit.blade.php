<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Shipment Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Edit Shipment Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('shipment_data.index') }}">Shipment</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Edit Shipment Data</h3>
                        </div>
                        <form action="{{ route('shipment_data.update', $shipmentDatum->id) }}" method="POST" id="editShipmentForm">
                            @csrf
                            @method('PATCH')
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="date">Date</label>
                                    <input type="date" name="date" class="form-control" id="date" value="{{ old('date', $shipmentDatum->date) }}" required>
                                    @error('date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="po_number">PO Number</label>
                                    <select name="po_number[]" id="po_number" class="form-control" multiple>
                                        @foreach ($distinctPoNumbers as $poNumber)
                                            <option value="{{ $poNumber }}"
                                                {{ in_array($poNumber, $poNumbers) ? 'selected' : '' }}>
                                                {{ $poNumber }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('po_number')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Product Combination</label>
                                    <input type="text" class="form-control"
                                        value="{{ $shipmentDatum->productCombination->style->name }} - {{ $shipmentDatum->productCombination->color->name }}" readonly>
                                </div>

                                <div class="form-group">
                                    <label>Shipment Quantities by Size</label>
                                    <div class="row">
                                        @foreach($sizeData as $size)
                                            <div class="col-md-3 mb-3">
                                                <label for="quantity_{{ $size['id'] }}">
                                                    {{ $size['name'] }} (Max Available: {{ $size['max_available'] }})
                                                    @if($size['order_quantity'] > 0)
                                                        <br><small>Order Qty: {{ $size['order_quantity'] }}</small>
                                                    @endif
                                                </label>
                                                <input type="number"
                                                       name="shipment_quantities[{{ $size['id'] }}]"
                                                       id="quantity_{{ $size['id'] }}"
                                                       class="form-control"
                                                       value="{{ old('shipment_quantities.' . $size['id'], $size['shipment_quantity']) }}"
                                                       min="0"
                                                       max="{{ $size['max_allowed'] }}"
                                                       data-available="{{ $size['max_allowed'] }}"
                                                       data-size="{{ strtolower($size['name']) }}">
                                                <small class="form-text text-muted">Current: {{ $size['shipment_quantity'] }}, Max: {{ $size['max_allowed'] }}</small>
                                                
                                                <label for="waste_{{ $size['id'] }}" class="mt-2">Waste Quantity</label>
                                                <input type="number"
                                                       name="shipment_waste_quantities[{{ $size['id'] }}]"
                                                       id="waste_{{ $size['id'] }}"
                                                       class="form-control"
                                                       value="{{ old('shipment_waste_quantities.' . $size['id'], $size['waste_quantity']) }}"
                                                       min="0"
                                                       max="{{ $size['max_allowed'] }}">
                                                
                                                <div class="progress mt-2" style="height: 5px;">
                                                    <div class="progress-bar" role="progressbar" style="width: 0%;"></div>
                                                </div>
                                                @error('shipment_quantities.' . $size['id'])
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                                <div class="text-danger" id="error_quantity_{{ $size['id'] }}"></div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Update</button>
                                <a href="{{ route('shipment_data.index') }}" class="btn btn-danger">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('input[type="number"]').forEach(input => {
                if (input.name.includes('shipment_quantities')) {
                    const updateProgressBar = () => {
                        const max = parseInt(input.getAttribute('data-available'));
                        const value = parseInt(input.value) || 0;
                        const percent = max > 0 ? Math.min(100, (value / max) * 100) : 0;
                        const progressBar = input.nextElementSibling.nextElementSibling.querySelector('.progress-bar');
                        if (progressBar) {
                            progressBar.style.width = `${percent}%`;
                        }

                        const errorDiv = document.getElementById(`error_quantity_${input.id.split('_')[1]}`);
                        if (value > max) {
                            input.classList.add('is-invalid');
                            if (errorDiv) {
                                errorDiv.textContent = `Quantity for ${input.getAttribute('data-size').toUpperCase()} exceeds available limit (${max})`;
                            }
                        } else {
                            input.classList.remove('is-invalid');
                            if (errorDiv) {
                                errorDiv.textContent = '';
                            }
                        }
                    };
                    input.addEventListener('input', updateProgressBar);
                    updateProgressBar(); // Initial update
                }
            });
        });
    </script>
</x-backend.layouts.master>




{{-- <x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Shipment Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Edit Shipment Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('shipment_data.index') }}">Shipment</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Edit Shipment Data</h3>
                        </div>
                        <form action="{{ route('shipment_data.update', $shipmentDatum->id) }}" method="POST" id="editShipmentForm">
                            @csrf
                            @method('PATCH')
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="date">Date</label>
                                    <input type="date" name="date" class="form-control" id="date" value="{{ old('date', $shipmentDatum->date) }}" required>
                                    @error('date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="product_combination">Product Combination (Style - Color)</label>
                                    <input type="text" class="form-control" id="product_combination"
                                        value="{{ $shipmentDatum->productCombination->style->name }} - {{ $shipmentDatum->productCombination->color->name }}" readonly>
                                    <input type="hidden" name="product_combination_id" value="{{ $shipmentDatum->product_combination_id }}">
                                </div>

                                <div class="form-group">
                                    <label>Shipment Quantities by Size</label>
                                    <div class="row">
                                        @foreach($sizeData as $size)
                                            <div class="col-md-3 mb-3">
                                                <label for="quantity_{{ $size['id'] }}">
                                                    {{ $size['name'] }} (Max Available: {{ $size['available'] }})
                                                </label>
                                                <input type="number"
                                                       name="quantities[{{ $size['id'] }}]"
                                                       id="quantity_{{ $size['id'] }}"
                                                       class="form-control"
                                                       value="{{ old('quantities.' . $size['id'], $size['current_quantity']) }}"
                                                       min="0"
                                                       max="{{ $size['available'] }}"
                                                       data-available="{{ $size['available'] }}"
                                                       data-size="{{ strtolower($size['name']) }}">
                                                <small class="form-text text-muted">Current: {{ $size['current_quantity'] }}, Max: {{ $size['available'] }}</small>
                                                <div class="progress mt-2" style="height: 5px;">
                                                    <div class="progress-bar" role="progressbar" style="width: 0%;"></div>
                                                </div>
                                                @error('quantities.' . $size['id'])
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                                <div class="text-danger" id="error_quantity_{{ $size['id'] }}"></div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Update</button>
                                <a href="{{ route('shipment_data.index') }}" class="btn btn-danger">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('input[type="number"]').forEach(input => {
                const updateProgressBar = () => {
                    const max = parseInt(input.getAttribute('data-available'));
                    const value = parseInt(input.value) || 0;
                    const percent = max > 0 ? Math.min(100, (value / max) * 100) : 0;
                    const progressBar = input.nextElementSibling.nextElementSibling.querySelector('.progress-bar');
                    progressBar.style.width = `${percent}%`;

                    const errorDiv = document.getElementById(`error_quantity_${input.id.split('_')[1]}`);
                    if (value > max) {
                        input.classList.add('is-invalid');
                        errorDiv.textContent = `Quantity for ${input.getAttribute('data-size').toUpperCase()} exceeds available limit (${max})`;
                    } else {
                        input.classList.remove('is-invalid');
                        errorDiv.textContent = '';
                    }
                };
                input.addEventListener('input', updateProgressBar);
                updateProgressBar(); // Initial update
            });
        });
    </script>
</x-backend.layouts.master> --}}