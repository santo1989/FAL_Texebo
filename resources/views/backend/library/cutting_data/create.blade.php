<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Add Cutting Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Cutting Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('cutting_data.index') }}">Cutting Data</a></li>
            <li class="breadcrumb-item active">Add</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <x-backend.layouts.elements.errors />
    <form action="{{ route('cutting_data.store') }}" method="post">
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
                            <option value="{{ $combination->id }}" data-sizes="{{ json_encode($combination->size_ids) }}" {{ old('product_combination_id') == $combination->id ? 'selected' : '' }}>
                                {{ $combination->buyer->name }} - {{ $combination->style->name }} - {{ $combination->color->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card mt-4" id="size-quantities-card" style="display: none;">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Cutting Quantities by Size</h5>
                <div>
                    <strong>Total Quantity: </strong> <span id="total-quantity">0</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="size-quantity-inputs">
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Save Cutting Data</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const productCombinationSelect = document.getElementById('product_combination_id');
            const sizeQuantitiesCard = document.getElementById('size-quantities-card');
            const sizeQuantityInputsContainer = document.getElementById('size-quantity-inputs');
            const totalQuantitySpan = document.getElementById('total-quantity');
            const allSizes = @json($sizes->pluck('name', 'id')); // { 'id': 'name' }

            function calculateTotalQuantity() {
                let total = 0;
                // Select all input fields that are part of the quantities array
                document.querySelectorAll('#size-quantity-inputs input[name^="quantities["]').forEach(input => {
                    total += parseInt(input.value) || 0; // Add value, default to 0 if not a number
                });
                totalQuantitySpan.textContent = total;
            }

            function loadSizeInputs(selectedSizeIds) {
                sizeQuantityInputsContainer.innerHTML = ''; // Clear previous inputs
                if (selectedSizeIds && selectedSizeIds.length > 0) {
                    sizeQuantitiesCard.style.display = 'block';
                    selectedSizeIds.forEach(sizeId => {
                        const sizeName = allSizes[sizeId];
                        if (sizeName) { // Ensure sizeName exists
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
                            input.classList.add('form-control', 'quantity-input'); // Add a class for easy selection
                            input.setAttribute('placeholder', `Enter ${sizeName} quantity`);
                            input.setAttribute('min', '0');
                            
                            // Set old value if available
                            const oldQuantities = @json(old('quantities'));
                            if (oldQuantities && oldQuantities[sizeId] !== undefined) {
                                input.value = oldQuantities[sizeId];
                            }

                            // Add event listener to each quantity input
                            input.addEventListener('input', calculateTotalQuantity);

                            formGroupDiv.appendChild(label);
                            formGroupDiv.appendChild(input);
                            colDiv.appendChild(formGroupDiv);
                            sizeQuantityInputsContainer.appendChild(colDiv);
                        }
                    });
                    calculateTotalQuantity(); // Calculate initial total after loading inputs
                } else {
                    sizeQuantitiesCard.style.display = 'none';
                    totalQuantitySpan.textContent = '0'; // Reset total when no sizes are selected
                }
            }

            productCombinationSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const selectedSizeIds = JSON.parse(selectedOption.dataset.sizes || '[]');
                loadSizeInputs(selectedSizeIds);
            });

            // Load initial sizes if an old product_combination_id is present (e.g., after validation error)
            const initialSelectedOption = productCombinationSelect.options[productCombinationSelect.selectedIndex];
            if (initialSelectedOption && initialSelectedOption.value) {
                const initialSelectedSizeIds = JSON.parse(initialSelectedOption.dataset.sizes || '[]');
                loadSizeInputs(initialSelectedSizeIds);
            }
        });
    </script>
</x-backend.layouts.master>