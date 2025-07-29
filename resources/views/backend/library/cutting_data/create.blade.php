<!-- resources/views/backend/library/cutting_data/create.blade.php -->
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
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="style_id">Style</label>
                    <select name="style_id" id="style_id" class="form-control" required>
                        <option value="">Select Style</option>
                        @foreach ($styles as $style)
                            <option value="{{ $style->id }}" {{ old('style_id') == $style->id ? 'selected' : '' }}>
                                {{ $style->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="color_id">Color</label>
                    <select name="color_id" id="color_id" class="form-control" required disabled>
                        <option value="">Select Style first</option>
                        @if(old('color_id'))
                            <option value="{{ old('color_id') }}" selected>
                                {{ \App\Models\Color::find(old('color_id'))->name ?? 'Selected' }}
                            </option>
                        @endif
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Product Combination (Buyer)</label>
                    <div class="form-control bg-light" id="combination-display">
                        @if(old('product_combination_id'))
                            @php
                                $oldCombination = \App\Models\ProductCombination::with('buyer')
                                    ->find(old('product_combination_id'));
                            @endphp
                            {{ $oldCombination->buyer->name ?? 'Invalid combination' }}
                        @else
                            Select Style and Color
                        @endif
                    </div>
                    <input type="hidden" name="product_combination_id" id="product_combination_id" 
                           value="{{ old('product_combination_id') }}">
                </div>
            </div>
        </div>

        <div class="card mt-4" id="size-quantities-card" style="display: {{ old('product_combination_id') ? 'block' : 'none' }};">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Cutting Quantities by Size</h5>
                <div>
                    <strong>Total Quantity: </strong> <span id="total-quantity">0</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="size-quantity-inputs">
                    @if(old('product_combination_id') && old('quantities'))
                        @php
                            $oldCombination = \App\Models\ProductCombination::find(old('product_combination_id'));
                            $oldSizeIds = $oldCombination ? $oldCombination->size_ids : [];
                        @endphp
                        @foreach($oldSizeIds as $sizeId)
                            @php
                                $size = \App\Models\Size::find($sizeId);
                            @endphp
                            @if($size)
                                <div class="col-md-2 mb-3">
                                    <div class="form-group">
                                        <label for="quantity_{{ $sizeId }}">{{ $size->name }}</label>
                                        <input type="number" name="quantities[{{ $sizeId }}]" 
                                               id="quantity_{{ $sizeId }}" 
                                               class="form-control quantity-input"
                                               placeholder="Enter quantity"
                                               min="0"
                                               value="{{ old('quantities.' . $sizeId) }}">
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Save Cutting Data</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const styleSelect = document.getElementById('style_id');
            const colorSelect = document.getElementById('color_id');
            const combinationDisplay = document.getElementById('combination-display');
            const combinationInput = document.getElementById('product_combination_id');
            const sizeQuantitiesCard = document.getElementById('size-quantities-card');
            const sizeQuantityInputsContainer = document.getElementById('size-quantity-inputs');
            const totalQuantitySpan = document.getElementById('total-quantity');
            
            // Function to fetch colors for a style
            function fetchColors(styleId) {
                if (!styleId) {
                    colorSelect.innerHTML = '<option value="">Select Style first</option>';
                    colorSelect.disabled = true;
                    combinationDisplay.textContent = 'Select Style and Color';
                    combinationInput.value = '';
                    sizeQuantitiesCard.style.display = 'none';
                    return;
                }

                fetch(`/get-colors/${styleId}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(colors => {
                        colorSelect.innerHTML = '<option value="">Select Color</option>';
                        colors.forEach(color => {
                            const option = document.createElement('option');
                            option.value = color.id;
                            option.textContent = color.name;
                            colorSelect.appendChild(option);
                        });
                        
                        colorSelect.disabled = false;
                        
                        // Set old value if exists
                        const oldColorId = "{{ old('color_id') }}";
                        if (oldColorId) {
                            colorSelect.value = oldColorId;
                            // Trigger combination fetch if we have both values
                            if (styleSelect.value && colorSelect.value) {
                                fetchCombination(styleSelect.value, colorSelect.value);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching colors:', error);
                        colorSelect.innerHTML = '<option value="">Error loading colors</option>';
                    });
            }

            // Function to fetch product combination
            function fetchCombination(styleId, colorId) {
                combinationDisplay.textContent = 'Loading...';
                combinationInput.value = '';
                sizeQuantitiesCard.style.display = 'none';
                
                fetch(`/get-combination/${styleId}/${colorId}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            combinationDisplay.textContent = data.combination.buyer_name;
                            combinationInput.value = data.combination.id;
                            loadSizeInputs(data.combination.size_ids);
                        } else {
                            combinationDisplay.textContent = 'No combination found';
                            combinationInput.value = '';
                            sizeQuantitiesCard.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching combination:', error);
                        combinationDisplay.textContent = 'Error loading combination';
                    });
            }

            // Function to load size inputs
            function loadSizeInputs(selectedSizeIds) {
                // Clear any existing size inputs
                sizeQuantityInputsContainer.innerHTML = '';
                
                if (selectedSizeIds && selectedSizeIds.length > 0) {
                    // Show the size quantities card
                    sizeQuantitiesCard.style.display = 'block';
                    
                    // Create input for each size
                    selectedSizeIds.forEach(sizeId => {
                        fetchSizeName(sizeId).then(sizeName => {
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
                                input.setAttribute('placeholder', `Enter quantity`);
                                input.setAttribute('min', '0');
                                input.setAttribute('value', '0');
                                
                                // Set old value if available
                                const oldQuantities = @json(old('quantities'));
                                if (oldQuantities && oldQuantities[sizeId] !== undefined) {
                                    input.value = oldQuantities[sizeId];
                                }

                                input.addEventListener('input', calculateTotalQuantity);

                                formGroupDiv.appendChild(label);
                                formGroupDiv.appendChild(input);
                                colDiv.appendChild(formGroupDiv);
                                sizeQuantityInputsContainer.appendChild(colDiv);
                                
                                // Recalculate total after adding new input
                                calculateTotalQuantity();
                            }
                        });
                    });
                } else {
                    sizeQuantitiesCard.style.display = 'none';
                    totalQuantitySpan.textContent = '0';
                }
            }

            // Helper function to get size name (could be optimized)
            async function fetchSizeName(sizeId) {
                try {
                    // In a real app, you might have a sizes map from the server
                    const response = await fetch(`/get-size-name/${sizeId}`);
                    const data = await response.json();
                    return data.name;
                } catch (error) {
                    console.error('Error fetching size name:', error);
                    return `Size ${sizeId}`;
                }
            }

            // Calculate total quantity
            function calculateTotalQuantity() {
                let total = 0;
                document.querySelectorAll('.quantity-input').forEach(input => {
                    const value = parseInt(input.value) || 0;
                    total += value;
                });
                totalQuantitySpan.textContent = total;
            }

            // Event listeners
            styleSelect.addEventListener('change', function() {
                fetchColors(this.value);
                // Reset color and combination
                colorSelect.value = '';
                combinationDisplay.textContent = 'Select Style and Color';
                combinationInput.value = '';
                sizeQuantitiesCard.style.display = 'none';
            });

            colorSelect.addEventListener('change', function() {
                if (styleSelect.value && this.value) {
                    fetchCombination(styleSelect.value, this.value);
                } else {
                    combinationDisplay.textContent = 'Select Style and Color';
                    combinationInput.value = '';
                    sizeQuantitiesCard.style.display = 'none';
                }
            });

            // Initialize form state
            const oldStyleId = "{{ old('style_id') }}";
            if (oldStyleId) {
                // Trigger initial color load
                fetchColors(oldStyleId);
            }
            
            // Add event listeners to existing quantity inputs (for old input)
            document.querySelectorAll('.quantity-input').forEach(input => {
                input.addEventListener('input', calculateTotalQuantity);
            });
            
            // Calculate initial total if there are old inputs
            calculateTotalQuantity();
        });
    </script>
</x-backend.layouts.master>