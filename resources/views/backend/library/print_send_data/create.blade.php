<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Add Print/Embroidery Send Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Print/Emb Send Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('print_send_data.index') }}">Print/Emb Send</a></li>
            <li class="breadcrumb-item active">Add</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <x-backend.layouts.elements.errors />
    <form action="{{ route('print_send_data.store') }}" method="post">
        @csrf
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" name="date" id="date" class="form-control" value="{{ old('date', date('Y-m-d')) }}" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="product_combination_id">Product Combination (Buyer - Style - Color)</label>
                    <select name="product_combination_id" id="product_combination_id" class="form-control" required>
                        <option value="">Select Product Combination</option>
                        @foreach ($productCombinations as $combination)
                            @if ($combination->print_embroidery)
                                <option value="{{ $combination->id }}" 
                                    data-sizes="{{ json_encode($combination->size_ids) }}" 
                                    {{ old('product_combination_id') == $combination->id ? 'selected' : '' }}>
                                    {{ $combination->buyer->name }} - {{ $combination->style->name }} - {{ $combination->color->name }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="alert alert-info">
            <strong>Available Quantity: </strong> <span id="available-quantity">0</span>
        </div>

        <div class="card mt-4" id="size-quantities-card" style="display: none;">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Send Quantities by Size</h5>
                <div>
                    <strong>Total Quantity: </strong> <span id="total-quantity">0</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="size-quantity-inputs">
                    <!-- Dynamic inputs will be inserted here -->
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Save Print/Emb Send Data</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const productCombinationSelect = document.getElementById('product_combination_id');
            const sizeQuantitiesCard = document.getElementById('size-quantities-card');
            const sizeQuantityInputsContainer = document.getElementById('size-quantity-inputs');
            const totalQuantitySpan = document.getElementById('total-quantity');
            const availableQuantitySpan = document.getElementById('available-quantity');
            const allSizes = @json($sizes->pluck('name', 'id')); // { 'id': 'name' }

            function calculateTotalQuantity() {
                let total = 0;
                document.querySelectorAll('#size-quantity-inputs input[name^="quantities["]').forEach(input => {
                    total += parseInt(input.value) || 0;
                });
                totalQuantitySpan.textContent = total;
                
                // Check against available quantity
                const available = parseInt(availableQuantitySpan.textContent) || 0;
                if (total > available) {
                    totalQuantitySpan.parentElement.classList.add('text-danger');
                } else {
                    totalQuantitySpan.parentElement.classList.remove('text-danger');
                }
            }

            async function fetchAvailableQuantity(pcId) {
                try {
                    const response = await fetch(`/api/print_send_data/available/${pcId}`);
                    const data = await response.json();
                    availableQuantitySpan.textContent = data.available;
                } catch (error) {
                    console.error('Error fetching available quantity:', error);
                    availableQuantitySpan.textContent = '0';
                }
            }

            function loadSizeInputs(selectedSizeIds) {
                sizeQuantityInputsContainer.innerHTML = '';
                if (selectedSizeIds && selectedSizeIds.length > 0) {
                    sizeQuantitiesCard.style.display = 'block';
                    selectedSizeIds.forEach(sizeId => {
                        const sizeName = allSizes[sizeId];
                        if (sizeName) {
                            const colDiv = document.createElement('div');
                            colDiv.classList.add('col-md-2', 'mb-3');

                            const formGroupDiv = document.createElement('div');
                            formGroupDiv.classList.add('form-group');

                            const label = document.createElement('label');
                            label.setAttribute('for', `quantity_${sizeId}`);
                            label.textContent = sizeName.toUpperCase();

                            const input = document.createElement('input');
                            input.setAttribute('type', 'number');
                            input.setAttribute('name', `quantities[${sizeId}]`);
                            input.setAttribute('id', `quantity_${sizeId}`);
                            input.classList.add('form-control', 'quantity-input');
                            input.setAttribute('placeholder', `Enter ${sizeName} quantity`);
                            input.setAttribute('min', '0');
                            input.setAttribute('max', availableQuantitySpan.textContent);
                            
                            input.addEventListener('input', calculateTotalQuantity);

                            formGroupDiv.appendChild(label);
                            formGroupDiv.appendChild(input);
                            colDiv.appendChild(formGroupDiv);
                            sizeQuantityInputsContainer.appendChild(colDiv);
                        }
                    });
                    calculateTotalQuantity();
                } else {
                    sizeQuantitiesCard.style.display = 'none';
                    totalQuantitySpan.textContent = '0';
                }
            }

            productCombinationSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.value) {
                    const selectedSizeIds = JSON.parse(selectedOption.dataset.sizes || '[]');
                    fetchAvailableQuantity(selectedOption.value)
                        .then(() => loadSizeInputs(selectedSizeIds));
                } else {
                    sizeQuantitiesCard.style.display = 'none';
                    availableQuantitySpan.textContent = '0';
                    totalQuantitySpan.textContent = '0';
                }
            });

            // Load initial data if returning from validation error
            const initialOption = productCombinationSelect.options[productCombinationSelect.selectedIndex];
            if (initialOption && initialOption.value) {
                const initialSizeIds = JSON.parse(initialOption.dataset.sizes || '[]');
                fetchAvailableQuantity(initialOption.value)
                    .then(() => loadSizeInputs(initialSizeIds));
            }
        });
    </script>
</x-backend.layouts.master>