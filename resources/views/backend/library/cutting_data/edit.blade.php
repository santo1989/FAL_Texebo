<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Cutting Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Cutting Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('cutting_data.index') }}">Cutting Data</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <x-backend.layouts.elements.errors />

    <form action="{{ route('cutting_data.update', $cuttingDatum) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" name="date" id="date" class="form-control"
                        value="{{ old('date', $cuttingDatum->date->format('Y-m-d')) }}" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="product_combination_id">Product Combination (Buyer - Style - Color)</label>
                    <select name="product_combination_id" id="product_combination_id" class="form-control" required>
                        <option value="">Select Product Combination</option>
                        @foreach ($productCombinations as $combination)
                            <option value="{{ $combination->id }}" 
                                data-sizes="{{ json_encode($combination->size_ids) }}"
                                {{ old('product_combination_id', $cuttingDatum->product_combination_id) == $combination->id ? 'selected' : '' }}>
                                {{ $combination->buyer->name }} - {{ $combination->style->name }} - {{ $combination->color->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card mt-4" id="size-quantities-card" style="{{ $cuttingDatum->productCombination ? '' : 'display: none;' }}">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Cutting Quantities by Size</h5>
                <div>
                    <strong>Total Quantity: </strong> <span id="total-quantity">0</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="size-quantity-inputs">
                    @if($cuttingDatum->productCombination)
                        @foreach($cuttingDatum->productCombination->sizes as $size)
                            <div class="col-md-2 mb-3">
                                <div class="form-group">
                                    <label for="quantity_{{ $size->id }}">{{ strtoupper($size->name) }}</label>
                                    <input type="number" name="quantities[{{ $size->id }}]" 
                                           id="quantity_{{ $size->id }}" class="form-control quantity-input"
                                           value="{{ $sizeQuantities[$size->id] ?? 0 }}" 
                                           min="0" placeholder="0">
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Update Cutting Data</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const productCombinationSelect = document.getElementById('product_combination_id');
            const sizeQuantitiesCard = document.getElementById('size-quantities-card');
            const sizeQuantityInputsContainer = document.getElementById('size-quantity-inputs');
            const totalQuantitySpan = document.getElementById('total-quantity');
            
            // Create size mapping from PHP
            const sizeMap = @json($sizes->mapWithKeys(fn($size) => [$size->id => $size->name]));
            const existingSizeQuantities = @json($sizeQuantities);

            // Calculate initial total quantity
            function calculateTotal() {
                let total = 0;
                document.querySelectorAll('.quantity-input').forEach(input => {
                    total += parseInt(input.value) || 0;
                });
                totalQuantitySpan.textContent = total;
                return total;
            }

            // Initialize total on page load
            calculateTotal();

            // Update total when quantities change
            document.addEventListener('input', function(e) {
                if (e.target.classList.contains('quantity-input')) {
                    calculateTotal();
                }
            });

            // Load sizes for selected product combination
            function loadSizeInputs(sizeIds) {
                if (!sizeIds || sizeIds.length === 0) {
                    sizeQuantitiesCard.style.display = 'none';
                    sizeQuantityInputsContainer.innerHTML = '';
                    totalQuantitySpan.textContent = '0';
                    return;
                }

                sizeQuantitiesCard.style.display = 'block';
                let inputsHTML = '';
                
                sizeIds.forEach(sizeId => {
                    const sizeName = sizeMap[sizeId] || 'Unknown';
                    const quantity = existingSizeQuantities[sizeId] || 0;
                    
                    inputsHTML += `
                        <div class="col-md-2 mb-3">
                            <div class="form-group">
                                <label for="quantity_${sizeId}">${sizeName.toUpperCase()}</label>
                                <input type="number" name="quantities[${sizeId}]" 
                                       id="quantity_${sizeId}" class="form-control quantity-input"
                                       value="${quantity}" min="0" placeholder="0">
                            </div>
                        </div>
                    `;
                });

                sizeQuantityInputsContainer.innerHTML = inputsHTML;
                calculateTotal();
            }

            // Handle product combination change
            productCombinationSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const sizeIds = selectedOption.dataset.sizes 
                    ? JSON.parse(selectedOption.dataset.sizes) 
                    : [];
                loadSizeInputs(sizeIds);
            });
        });
    </script>
</x-backend.layouts.master>