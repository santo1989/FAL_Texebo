<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Add Shipment Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Add Shipment Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('shipment_data.index') }}">Shipment</a></li>
            <li class="breadcrumb-item active">Add New</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

   <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Add Shipment Data</h3>
                        </div>
                        <form action="{{ route('shipment_data.store') }}" method="POST" id="shipmentForm">
                            @csrf
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="date">Date</label>
                                    <input type="date" name="date" class="form-control" id="date" value="{{ old('date', date('Y-m-d')) }}" required>
                                    @error('date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="product_combination_id">Product Combination (Style - Color)</label>
                                    <select name="product_combination_id" id="product_combination_id" class="form-control" required>
                                        <option value="">Select Product Combination</option>
                                        @foreach ($productCombinations as $pc)
                                            <option value="{{ $pc->id }}" {{ old('product_combination_id') == $pc->id ? 'selected' : '' }}>
                                                {{ $pc->style->name }} - {{ $pc->color->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('product_combination_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div id="sizeShipmentInputs">
                                    <div class="text-center mt-4">
                                        <p class="text-muted">Select a product combination to see available quantities for shipment</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Submit</button>
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
            const productCombinationSelect = document.getElementById('product_combination_id');
            const sizeShipmentInputsContainer = document.getElementById('sizeShipmentInputs');

            productCombinationSelect.addEventListener('change', function() {
                const combinationId = this.value;

                if (!combinationId) {
                    sizeShipmentInputsContainer.innerHTML = `
                        <div class="text-center mt-4">
                            <p class="text-muted">Select a product combination to see available quantities for shipment</p>
                        </div>
                    `;
                    return;
                }

                // Show loading indicator
                sizeShipmentInputsContainer.innerHTML = `
                    <div class="text-center mt-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Loading available quantities...</p>
                    </div>
                `;

                // Fetch available quantities via AJAX
                fetch(`/shipment_data/available_quantities/${combinationId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.availableQuantities && data.sizes) {
                            let html = `
                                <div class="form-group">
                                    <label>Shipment Quantities by Size</label>
                                    <div class="row">
                            `;

                            data.sizes.forEach(size => {
                                const sizeName = size.name.toLowerCase();
                                const availableQty = data.availableQuantities[sizeName] || 0;
                                
                                html += `
                                    <div class="col-md-3 mb-3">
                                        <label for="quantity_${size.id}">
                                            ${size.name} (Max Available: ${availableQty})
                                        </label>
                                        <input type="number"
                                               name="quantities[${size.id}]"
                                               id="quantity_${size.id}"
                                               class="form-control"
                                               value="0"
                                               min="0"
                                               max="${availableQty}"
                                               data-size="${sizeName}"
                                               data-available="${availableQty}">
                                        <small class="form-text text-muted">Max: ${availableQty}</small>
                                        <div class="progress mt-2" style="height: 5px;">
                                            <div class="progress-bar" role="progressbar" style="width: 0%;"></div>
                                        </div>
                                        <div class="text-danger" id="error_quantity_${size.id}"></div>
                                    </div>
                                `;
                            });

                            html += `
                                    </div>
                                </div>
                            `;

                            sizeShipmentInputsContainer.innerHTML = html;

                            // Add event listeners for progress bars
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
                        } else {
                            sizeShipmentInputsContainer.innerHTML = `
                                <div class="alert alert-danger">
                                    Error loading available quantities. Please try again.
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching available quantities:', error);
                        sizeShipmentInputsContainer.innerHTML = `
                            <div class="alert alert-danger">
                                Error loading available quantities: ${error.message}
                            </div>
                        `;
                    });
            });
        });
    </script>
</x-backend.layouts.master>