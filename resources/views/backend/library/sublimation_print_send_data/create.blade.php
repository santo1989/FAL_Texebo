{{-- <x-backend.layouts.master>
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
</x-backend.layouts.master> --}}
{{-- <x-backend.layouts.master>
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
                    <input type="date" name="date" id="date" class="form-control"
                        value="{{ old('date', date('Y-m-d')) }}" required>
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
                        @if (old('color_id'))
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
                        @if (old('product_combination_id'))
                            @php
                                $oldCombination = \App\Models\ProductCombination::with('buyer')->find(
                                    old('product_combination_id'),
                                );
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

        <div class="alert alert-info">
            <strong>Available Quantity: </strong> <span id="available-quantity">0</span>
        </div>

        <div class="card mt-4" id="size-quantities-card"
            style="display: {{ old('product_combination_id') ? 'block' : 'none' }};">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Send Quantities by Size</h5>
                <div>
                    <strong>Total Quantity: </strong> <span id="total-quantity">0</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="size-quantity-inputs">
                    @if (old('product_combination_id') && old('quantities'))
                        @php
                            $oldCombination = \App\Models\ProductCombination::find(old('product_combination_id'));
                            $oldSizeIds = $oldCombination ? $oldCombination->size_ids : [];
                        @endphp
                        @foreach ($oldSizeIds as $sizeId)
                            @php
                                $size = \App\Models\Size::find($sizeId);
                            @endphp
                            @if ($size)
                                <div class="col-md-2 mb-3">
                                    <div class="form-group">
                                        <label for="quantity_{{ $sizeId }}">{{ $size->name }}</label>
                                        <input type="number" name="quantities[{{ $sizeId }}]"
                                            id="quantity_{{ $sizeId }}" class="form-control quantity-input"
                                            placeholder="Enter quantity" min="0"
                                            value="{{ old('quantities.' . $sizeId) }}">
                                        
                                        <small class="form-text text-muted" id="available_note_{{ $sizeId }}">
                                            Available: 0
                                        </small>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Save Print/Emb Send Data</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
    const styleSelect = document.getElementById('style_id');
    const colorSelect = document.getElementById('color_id');
    const combinationDisplay = document.getElementById('combination-display');
    const combinationInput = document.getElementById('product_combination_id');
    const availableQuantitySpan = document.getElementById('available-quantity');
    const sizeQuantitiesCard = document.getElementById('size-quantities-card');
    const sizeQuantityInputsContainer = document.getElementById('size-quantity-inputs');
    const totalQuantitySpan = document.getElementById('total-quantity');
    const allSizes = @json($sizes->pluck('name', 'id')); // { 'id': 'name' }

    // Global variable to store per-size available quantities
    let availablePerSize = {};
    let oldQuantities = @json(old('quantities', []));
    let sizeNameToIdMap = @json($sizes->pluck('id', 'name')); // Map size names to IDs

    // Function to fetch colors for a style
    function fetchColors(styleId) {
        if (!styleId) {
            colorSelect.innerHTML = '<option value="">Select Style first</option>';
            colorSelect.disabled = true;
            combinationDisplay.textContent = 'Select Style and Color';
            combinationInput.value = '';
            availableQuantitySpan.textContent = '0';
            availablePerSize = {};
            sizeQuantitiesCard.style.display = 'none';
            return;
        }

        fetch(`/print_send_data/get-colors/${styleId}`)
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
                    fetchCombination(styleSelect.value, oldColorId);
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
        availableQuantitySpan.textContent = '0';
        availablePerSize = {};
        sizeQuantitiesCard.style.display = 'none';

        fetch(`/print_send_data/get-combination/${styleId}/${colorId}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    combinationDisplay.textContent = data.combination.buyer_name;
                    combinationInput.value = data.combination.id;
                    fetchAvailableQuantity(data.combination.id).then(() => {
                        loadSizeInputs(data.combination.size_ids);
                    });
                } else {
                    combinationDisplay.textContent = 'No combination found';
                    availableQuantitySpan.textContent = '0';
                    sizeQuantitiesCard.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error fetching combination:', error);
                combinationDisplay.textContent = 'Error loading combination';
            });
    }

    // Function to fetch available quantity
    async function fetchAvailableQuantity(pcId) {
        try {
            const response = await fetch(`/api/print_send_data/available/${pcId}`);
            const data = await response.json();
            availableQuantitySpan.textContent = data.available;
            
            // Convert size IDs to strings for consistent access
            availablePerSize = {};
            for (const [sizeId, quantity] of Object.entries(data.available_per_size || {})) {
                availablePerSize[sizeId.toString()] = quantity;
            }
        } catch (error) {
            console.error('Error fetching available quantity:', error);
            availableQuantitySpan.textContent = '0';
            availablePerSize = {};
        }
    }

    // Function to load size inputs
    function loadSizeInputs(selectedSizeIds) {
        sizeQuantityInputsContainer.innerHTML = '';
        if (selectedSizeIds && selectedSizeIds.length > 0) {
            sizeQuantitiesCard.style.display = 'block';

            selectedSizeIds.forEach(sizeId => {
                // Convert size ID to string for consistent access
                const sizeIdStr = sizeId.toString();
                const sizeName = allSizes[sizeId] || `Size ${sizeId}`;
                
                // Get available quantity using string key
                const availableForSize = availablePerSize[sizeIdStr] || 0;

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
                input.setAttribute('placeholder', 'Enter quantity');
                input.setAttribute('min', '0');
                input.setAttribute('max', availableForSize); // Set max attribute
                
                // Use string key to access old quantities
                input.value = oldQuantities[sizeIdStr] || 0;

                input.addEventListener('input', function() {
                    calculateTotalQuantity();
                    validateSizeInput(this, availableForSize);
                    updateProgressBar(this, availableForSize);
                });

                const availableNote = document.createElement('small');
                availableNote.classList.add('form-text', 'text-muted');
                availableNote.setAttribute('id', `available_note_${sizeId}`);
                availableNote.innerHTML = `Available: ${availableForSize}`;

                const progressContainer = document.createElement('div');
                progressContainer.classList.add('progress', 'mt-2');
                progressContainer.style.height = '5px';
                
                const progressBar = document.createElement('div');
                progressBar.classList.add('progress-bar');
                progressBar.setAttribute('role', 'progressbar');
                progressBar.style.width = '0%';
                
                progressContainer.appendChild(progressBar);

                formGroupDiv.appendChild(label);
                formGroupDiv.appendChild(input);
                formGroupDiv.appendChild(availableNote);
                formGroupDiv.appendChild(progressContainer);
                colDiv.appendChild(formGroupDiv);
                sizeQuantityInputsContainer.appendChild(colDiv);

                // Initialize progress bar and validation
                updateProgressBar(input, availableForSize);
                validateSizeInput(input, availableForSize);
            });

            calculateTotalQuantity();
        } else {
            sizeQuantitiesCard.style.display = 'none';
            totalQuantitySpan.textContent = '0';
        }
    }

    // Update progress bar based on input value
    function updateProgressBar(input, maxValue) {
        const value = parseInt(input.value) || 0;
        const percent = maxValue > 0 ? Math.min(100, (value / maxValue) * 100) : 0;
        const progressBar = input.nextElementSibling.nextElementSibling.querySelector('.progress-bar');
        progressBar.style.width = `${percent}%`;
        
        // Set color based on percentage
        if (percent > 90) {
            progressBar.style.backgroundColor = '#dc3545'; // Red
        } else if (percent > 70) {
            progressBar.style.backgroundColor = '#ffc107'; // Yellow
        } else {
            progressBar.style.backgroundColor = '#28a745'; // Green
        }
    }

    // Validate individual size input
    function validateSizeInput(input, maxValue) {
        const value = parseInt(input.value) || 0;
        const sizeId = input.id.split('_')[1];
        const note = document.getElementById(`available_note_${sizeId}`);

        if (value > maxValue) {
            input.classList.add('is-invalid');
            note.innerHTML = `<span class="text-danger">Exceeds available (${maxValue})</span>`;
        } else {
            input.classList.remove('is-invalid');
            note.innerHTML = `Available: ${maxValue}`;
        }
    }

    // Calculate total quantity
    function calculateTotalQuantity() {
        let total = 0;
        document.querySelectorAll('.quantity-input').forEach(input => {
            total += parseInt(input.value) || 0;
        });
        totalQuantitySpan.textContent = total;

        // Check against overall available quantity
        const available = parseInt(availableQuantitySpan.textContent) || 0;
        if (total > available) {
            totalQuantitySpan.parentElement.classList.add('text-danger');
        } else {
            totalQuantitySpan.parentElement.classList.remove('text-danger');
        }
    }

    // Event listeners
    styleSelect.addEventListener('change', function() {
        fetchColors(this.value);
        colorSelect.value = '';
        combinationDisplay.textContent = 'Select Style and Color';
        combinationInput.value = '';
        availableQuantitySpan.textContent = '0';
        sizeQuantitiesCard.style.display = 'none';
    });

    colorSelect.addEventListener('change', function() {
        if (styleSelect.value && this.value) {
            fetchCombination(styleSelect.value, this.value);
        } else {
            combinationDisplay.textContent = 'Select Style and Color';
            combinationInput.value = '';
            availableQuantitySpan.textContent = '0';
            sizeQuantitiesCard.style.display = 'none';
        }
    });

    // Initialize form
    const oldStyleId = "{{ old('style_id') }}";
    if (oldStyleId) {
        fetchColors(oldStyleId);
    }

    // Initialize progress bars for old values
    setTimeout(() => {
        document.querySelectorAll('.quantity-input').forEach(input => {
            const sizeId = input.id.split('_')[1];
            // Use string key to access available quantities
            const maxValue = availablePerSize[sizeId] || 0;
            updateProgressBar(input, maxValue);
        });
    }, 500);
});
    </script>
</x-backend.layouts.master> --}}

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
                    <input type="date" name="date" id="date" class="form-control"
                        value="{{ old('date', date('Y-m-d')) }}" required>
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
                        @if (old('color_id'))
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
                        @if (old('product_combination_id'))
                            @php
                                $oldCombination = \App\Models\ProductCombination::with('buyer')->find(old('product_combination_id'));
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

        <div id="sizeInputs">
            <div class="text-center mt-4">
                <p class="text-muted">Select a style and color to see available quantities</p>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Save Print/Emb Send Data</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const styleSelect = document.getElementById('style_id');
            const colorSelect = document.getElementById('color_id');
            const combinationDisplay = document.getElementById('combination-display');
            const combinationInput = document.getElementById('product_combination_id');
            const sizeInputsContainer = document.getElementById('sizeInputs');

            function fetchColors(styleId) {
                if (!styleId) {
                    colorSelect.innerHTML = '<option value="">Select Style first</option>';
                    colorSelect.disabled = true;
                    combinationDisplay.textContent = 'Select Style and Color';
                    combinationInput.value = '';
                    sizeInputsContainer.innerHTML = '<div class="text-center mt-4"><p class="text-muted">Select a style and color to see available quantities</p></div>';
                    return;
                }

                fetch(`/print_send_data/get-colors/${styleId}`)
                    .then(response => response.json())
                    .then(colors => {
                        colorSelect.innerHTML = '<option value="">Select Color</option>';
                        colors.forEach(color => {
                            const option = document.createElement('option');
                            option.value = color.id;
                            option.textContent = color.name;
                            colorSelect.appendChild(option);
                        });
                        colorSelect.disabled = false;
                        if ("{{ old('color_id') }}") {
                            colorSelect.value = "{{ old('color_id') }}";
                            fetchCombination(styleSelect.value, colorSelect.value);
                        }
                    });
            }

            function fetchCombination(styleId, colorId) {
                fetch(`/print_send_data/get-combination/${styleId}/${colorId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            combinationDisplay.textContent = data.combination.buyer_name;
                            combinationInput.value = data.combination.id;
                            loadSizeInputs(data.combination.id);
                        } else {
                            combinationDisplay.textContent = 'No combination found';
                            sizeInputsContainer.innerHTML = '<div class="text-center mt-4"><p class="text-muted">No sizes available</p></div>';
                        }
                    });
            }

            function loadSizeInputs(combinationId) {
                sizeInputsContainer.innerHTML = '<div class="text-center mt-4"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div><p class="mt-2">Loading available quantities...</p></div>';

                fetch(`/print_send_data/available_quantities/${combinationId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.availableQuantities) {
                            console.log(data.availableQuantities);
                            let html = '<div class="form-group"><label>Send Quantities by Size</label><div class="row">';
                            data.sizes.forEach(size => {
                                const sizeName = size.name.toLowerCase();
                                const availableQty = data.availableQuantities[sizeName] || 0;
                                html += `
                                    <div class="col-md-3 mb-3">
                                        <label for="quantity_${size.id}">${size.name} (Available: ${availableQty})</label>
                                        <input type="number" name="quantities[${size.id}]" id="quantity_${size.id}"
                                            class="form-control" value="0" min="0" max="${availableQty}"
                                            data-size="${sizeName}">
                                        <small class="form-text text-muted">Max: ${availableQty}</small>
                                        <div class="progress mt-2" style="height: 5px;">
                                            <div class="progress-bar" role="progressbar" style="width: 0%;"></div>
                                        </div>
                                        <div class="text-danger" id="error_${size.id}"></div>
                                    </div>
                                `;
                            });
                            html += '</div></div>';
                            sizeInputsContainer.innerHTML = html;

                            document.querySelectorAll('input[type="number"]').forEach(input => {
                                input.addEventListener('input', function() {
                                    const max = parseInt(this.max);
                                    const value = parseInt(this.value) || 0;
                                    const percent = max > 0 ? Math.min(100, (value / max) * 100) : 0;
                                    const progressBar = this.nextElementSibling.nextElementSibling.querySelector('.progress-bar');
                                    progressBar.style.width = `${percent}%`;
                                    if (value > max) {
                                        this.classList.add('is-invalid');
                                        this.nextElementSibling.nextElementSibling.nextElementSibling.textContent = `Cannot exceed ${max}`;
                                    } else {
                                        this.classList.remove('is-invalid');
                                        this.nextElementSibling.nextElementSibling.nextElementSibling.textContent = '';
                                    }
                                });
                            });
                        } else {
                            sizeInputsContainer.innerHTML = '<div class="alert alert-danger">Error loading available quantities.</div>';
                        }
                    })
                    .catch(error => {
                        sizeInputsContainer.innerHTML = '<div class="alert alert-danger">Error loading available quantities: ${error.message}</div>';
                    });
            }

            styleSelect.addEventListener('change', function() {
                fetchColors(this.value);
                colorSelect.value = '';
            });

            colorSelect.addEventListener('change', function() {
                if (styleSelect.value && this.value) {
                    fetchCombination(styleSelect.value, this.value);
                }
            });

            if ("{{ old('style_id') }}") {
                fetchColors("{{ old('style_id') }}");
            }
        });
    </script>

    <style>
        .progress { background-color: #e9ecef; }
        .progress-bar { background-color: #28a745; transition: width 0.3s ease; }
    </style>
</x-backend.layouts.master>
