<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Add Line Input Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Add Line Input Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('line_input_data.index') }}">Line Input</a></li>
            <li class="breadcrumb-item active">Add New</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Add Line Input Data</h3>
                        </div>
                        <form action="{{ route('line_input_data.store') }}" method="POST" id="lineInputForm">
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

                                <div id="sizeInputs">
                                    <!-- Size inputs will be loaded here dynamically -->
                                    <div class="text-center mt-4">
                                        <p class="text-muted">Select a product combination to see available quantities</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Submit</button>
                                <a href="{{ route('line_input_data.index') }}" class="btn btn-danger">Cancel</a>
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
            const sizeInputsContainer = document.getElementById('sizeInputs');
            
            productCombinationSelect.addEventListener('change', function() {
                const combinationId = this.value;
                
                if (!combinationId) {
                    sizeInputsContainer.innerHTML = `
                        <div class="text-center mt-4">
                            <p class="text-muted">Select a product combination to see available quantities</p>
                        </div>
                    `;
                    return;
                }
                
                // Show loading indicator
                sizeInputsContainer.innerHTML = `
                    <div class="text-center mt-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Loading available quantities...</p>
                    </div>
                `;
                
                // Fetch available quantities via AJAX
                fetch(`/line_input_data/available_quantities/${combinationId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.availableQuantities) {
                            let html = `
                                <div class="form-group">
                                    <label>Input Quantities by Size</label>
                                    <div class="row">
                            `;
                            
                            data.sizes.forEach(size => {
                                const sizeName = size.name.toLowerCase();
                                const availableQty = data.availableQuantities[sizeName] || 0;
                                
                                html += `
                                    <div class="col-md-3 mb-3">
                                        <label for="quantity_${size.id}">
                                            ${size.name} (Available: ${availableQty})
                                        </label>
                                        <input type="number" 
                                               name="quantities[${size.id}]" 
                                               id="quantity_${size.id}"
                                               class="form-control" 
                                               value="0" 
                                               min="0" 
                                               max="${availableQty}"
                                               data-size="${sizeName}">
                                        <small class="form-text text-muted">Max: ${availableQty}</small>
                                        <div class="progress mt-2" style="height: 5px;">
                                            <div class="progress-bar" role="progressbar" style="width: 0%;"></div>
                                        </div>
                                        <div class="text-danger" id="error_${size.id}"></div>
                                    </div>
                                `;
                            });
                            
                            html += `
                                    </div>
                                </div>
                            `;
                            
                            sizeInputsContainer.innerHTML = html;
                            
                            // Add event listeners for progress bars
                            document.querySelectorAll('input[type="number"]').forEach(input => {
                                input.addEventListener('input', function() {
                                    const max = parseInt(this.max);
                                    const value = parseInt(this.value) || 0;
                                    const percent = max > 0 ? Math.min(100, (value / max) * 100) : 0;
                                    const progressBar = this.nextElementSibling.nextElementSibling.querySelector('.progress-bar');
                                    progressBar.style.width = `${percent}%`;
                                    
                                    if (value > max) {
                                        this.classList.add('is-invalid');
                                        this.nextElementSibling.nextElementSibling.nextElementSibling.textContent = 
                                            `Cannot exceed ${max}`;
                                    } else {
                                        this.classList.remove('is-invalid');
                                        this.nextElementSibling.nextElementSibling.nextElementSibling.textContent = '';
                                    }
                                });
                            });
                        } else {
                            sizeInputsContainer.innerHTML = `
                                <div class="alert alert-danger">
                                    Error loading available quantities. Please try again.
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        sizeInputsContainer.innerHTML = `
                            <div class="alert alert-danger">
                                Error loading available quantities: ${error.message}
                            </div>
                        `;
                    });
            });
            
            // Trigger change if there's already a selected value (e.g., after validation error)
            if (productCombinationSelect.value) {
                productCombinationSelect.dispatchEvent(new Event('change'));
            }
        });
    </script>
    
    <style>
        .progress {
            background-color: #e9ecef;
        }
        .progress-bar {
            background-color: #28a745;
            transition: width 0.3s ease;
        }
        input[type="number"]:disabled {
            background-color: #f8f9fa;
        }
    </style>
</x-backend.layouts.master>