<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Add Sewing Output Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Add Sewing Output Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('output_finishing_data.index') }}">Output Finishing</a></li>
            <li class="breadcrumb-item active">Add New</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-backend.layouts.elements.errors />
    <form action="{{ route('output_finishing_data.store') }}" method="post">
        @csrf
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" name="date" id="date" class="form-control"
                        value="{{ old('date', date('Y-m-d')) }}" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="po_number">PO Number</label>
                    <select name="po_number[]" id="po_number" class="form-control" multiple>
                        @foreach ($distinctPoNumbers as $poNumber)
                            <option value="{{ $poNumber }}"
                                {{ in_array($poNumber, old('po_number', [])) ? 'selected' : '' }}>
                                {{ $poNumber }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <table class="table table-bordered mt-4 text-center">
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>Style</th>
                    <th>Color</th>
                    @foreach ($sizes as $size)
                        <th>
                            {{ $size->name }}
                            <br>
                            <small>Output Qty / Waste Qty</small>
                        </th>
                    @endforeach
                    <th>Total Output Qty</th>
                    <th>Total Waste Qty</th>
                </tr>
            </thead>
            <tbody id="output-finishing-data-body">
                <!-- Dynamic rows will be injected here by JavaScript -->
            </tbody>
        </table>

        <a href="{{ route('output_finishing_data.index') }}" class="btn btn-secondary mt-3">Back</a>
        <button type="submit" class="btn btn-primary mt-3">Save Sewing Output Data</button>
    </form>

    <script>
        
        document.addEventListener('DOMContentLoaded', function() {
    const poNumberSelect = document.getElementById('po_number');
    const outputFinishingDataBody = document.getElementById('output-finishing-data-body');
    let savedOutputs = {};
    let rowIndex = 0; // Track row index globally

    const initialPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
    if (initialPoNumbers.length > 0) {
        updateOutputFinishingDataRows(initialPoNumbers);
    }

    poNumberSelect.addEventListener('change', function() {
        const selectedPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
        rowIndex = 0; // Reset row index when PO selection changes
        if (selectedPoNumbers.length > 0) {
            updateOutputFinishingDataRows(selectedPoNumbers);
        } else {
            outputFinishingDataBody.innerHTML = '';
            savedOutputs = {};
        }
    });

    function updateOutputFinishingDataRows(poNumbers) {
        const url = '{{ route('output_finishing_data.find') }}?po_numbers[]=' + poNumbers.join('&po_numbers[]=');

        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
            return response.json();
        })
        .then(data => {
            outputFinishingDataBody.innerHTML = '';
            rowIndex = 0; // Reset row index for new data
            savedOutputs = {}; // Clear saved outputs

            if (!data || Object.keys(data).length === 0) {
                outputFinishingDataBody.innerHTML = '<tr><td colspan="100%">No data found for selected PO numbers.</td></tr>';
                return;
            }

            // Process each PO number
            for (const poNumber in data) {
                if (!Array.isArray(data[poNumber])) continue;
                
                // Process each combination in this PO
                data[poNumber].forEach(combination => {
                    if (!combination.combination_id || !combination.style || !combination.color || !combination.available_quantities || !combination.size_ids) {
                        console.error('Invalid combination data:', combination);
                        return;
                    }

                    const key = `${poNumber}-${combination.combination_id}`;
                    const row = document.createElement('tr');
                    row.dataset.key = key;
                    row.innerHTML = `
                        <td class="text-center">
                            <input type="hidden" name="rows[${rowIndex}][product_combination_id]" value="${combination.combination_id}">
                            <input type="hidden" name="rows[${rowIndex}][po_number]" value="${poNumber}">
                            ${poNumber}
                        </td>
                        <td class="text-center">${combination.style}</td>
                        <td class="text-center">${combination.color}</td>
                    `;

                    const availableSizeIds = combination.size_ids.map(id => String(id));

                    @foreach ($sizes as $size)
                        {
                            const sizeId = "{{ $size->id }}";
                            const availableQty = combination.available_quantities[sizeId] || 0;
                            const savedOutput = (savedOutputs[key] && savedOutputs[key].output && savedOutputs[key].output[sizeId]) || 0;
                            const savedWaste = (savedOutputs[key] && savedOutputs[key].waste && savedOutputs[key].waste[sizeId]) || 0;
                            const isSizeAvailable = availableSizeIds.includes(sizeId);

                            const cell = document.createElement('td');
                            if (isSizeAvailable && availableQty > 0) {
                                cell.innerHTML = ` <label>Max = ${availableQty} Pcs </label> <br>
                                    <div class="input-group input-group-sm">
                                        <input type="number" 
                                               name="rows[${rowIndex}][output_quantities][${sizeId}]" 
                                               class="form-control output-qty-input"
                                               min="0" 
                                               max="${availableQty}"
                                               value="${savedOutput}"
                                               placeholder="Av: ${availableQty}">
                                        <input type="number" 
                                               name="rows[${rowIndex}][output_waste_quantities][${sizeId}]" 
                                               class="form-control waste-qty-input"
                                               min="0" 
                                               value="${savedWaste}"
                                               max="${availableQty}"
                                               placeholder="W: 0">
                                    </div>
                                `;
                            } else {
                                cell.innerHTML = `<span class="text-muted text-center">N/A</span>`;
                            }
                            row.appendChild(cell);
                        }
                    @endforeach

                    row.innerHTML += `
                        <td><span class="total-output-qty-span">0</span></td>
                        <td><span class="total-waste-qty-span">0</span></td>
                    `;

                    outputFinishingDataBody.appendChild(row);
                    
                    // Initialize saved outputs for this row
                    if (!savedOutputs[key]) {
                        savedOutputs[key] = { output: {}, waste: {} };
                    }
                    
                    rowIndex++;
                });
            }

            // Update totals for all rows
            outputFinishingDataBody.querySelectorAll('.output-qty-input, .waste-qty-input').forEach(input => {
                input.dispatchEvent(new Event('input'));
            });
        })
        .catch(error => {
            console.error('Error fetching data:', error);
            outputFinishingDataBody.innerHTML = '<tr><td colspan="100%">Error loading data. Error: ' + error.message + '</td></tr>';
        });
    }

    // Event delegation for dynamic inputs
    outputFinishingDataBody.addEventListener('input', function(e) {
        const target = e.target;
        if (target.classList.contains('output-qty-input') || target.classList.contains('waste-qty-input')) {
            const row = target.closest('tr');
            const key = row.dataset.key;

            if (!savedOutputs[key]) {
                savedOutputs[key] = { output: {}, waste: {} };
            }

            const name = target.name;
            const sizeIdMatch = name.match(/\[(output_quantities|output_waste_quantities)\]\[(\d+)\]/);
            if (sizeIdMatch) {
                const type = sizeIdMatch[1];
                const sizeId = sizeIdMatch[2];
                let value = parseInt(target.value) || 0;

                if (type === 'output_quantities') {
                    const max = parseInt(target.getAttribute('max')) || 0;
                    if (value > max) {
                        value = max;
                        target.value = max;
                    }
                    savedOutputs[key].output[sizeId] = value;
                } else {
                    savedOutputs[key].waste[sizeId] = value;
                }

                updateRowTotals(row);
            }
        }
    });

    function updateRowTotals(row) {
        let totalOutput = 0;
        let totalWaste = 0;

        row.querySelectorAll('.output-qty-input').forEach(input => {
            totalOutput += parseInt(input.value) || 0;
        });

        row.querySelectorAll('.waste-qty-input').forEach(input => {
            totalWaste += parseInt(input.value) || 0;
        });

        row.querySelector('.total-output-qty-span').textContent = totalOutput;
        row.querySelector('.total-waste-qty-span').textContent = totalWaste;
    }
});

    </script>
</x-backend.layouts.master>
{{-- <x-backend.layouts.master>
    <x-slot name="pageTitle">
        Add Sewing Output Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Add Sewing Output Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('output_finishing_data.index') }}">Output Finishing</a></li>
            <li class="breadcrumb-item active">Add New</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Add Sewing Output Data</h3>
                        </div>
                        <form action="{{ route('output_finishing_data.store') }}" method="POST" id="outputFinishingForm">
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

                                <div id="sizeOutputInputs">
                                    <div class="text-center mt-4">
                                        <p class="text-muted">Select a product combination to see available quantities for output</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Submit</button>
                                <a href="{{ route('output_finishing_data.index') }}" class="btn btn-danger">Cancel</a>
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
            const sizeOutputInputsContainer = document.getElementById('sizeOutputInputs');

            productCombinationSelect.addEventListener('change', function() {
                const combinationId = this.value;

                if (!combinationId) {
                    sizeOutputInputsContainer.innerHTML = `
                        <div class="text-center mt-4">
                            <p class="text-muted">Select a product combination to see available quantities for output</p>
                        </div>
                    `;
                    return;
                }

                // Show loading indicator
                sizeOutputInputsContainer.innerHTML = `
                    <div class="text-center mt-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Loading available quantities...</p>
                    </div>
                `;

                // Fetch available quantities via AJAX
                fetch(`/output_finishing_data/max_quantities/${combinationId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Received data:', data); // Debugging line
                        
                        if (data.maxQuantities && data.sizes) {
                            let html = `
                                <div class="form-group">
                                    <label>Output Quantities by Size</label>
                                    <div class="row">
                            `;

                            data.sizes.forEach(size => {
                                const sizeName = size.name.toLowerCase();
                                const maxQty = data.maxQuantities[sizeName] || 0;
                                
                                html += `
                                    <div class="col-md-3 mb-3">
                                        <label for="quantity_${size.id}">
                                            ${size.name} (Max Available: ${maxQty})
                                        </label>
                                        <input type="number"
                                               name="quantities[${size.id}]"
                                               id="quantity_${size.id}"
                                               class="form-control"
                                               value="0"
                                               min="0"
                                               max="${maxQty}"
                                               data-size="${sizeName}"
                                               data-max="${maxQty}">
                                        <small class="form-text text-muted">Max: ${maxQty}</small>
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

                            sizeOutputInputsContainer.innerHTML = html;

                            // Add event listeners for progress bars
                            document.querySelectorAll('input[type="number"]').forEach(input => {
                                const updateProgressBar = () => {
                                    const max = parseInt(input.getAttribute('data-max'));
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
                            sizeOutputInputsContainer.innerHTML = `
                                <div class="alert alert-danger">
                                    Error: Invalid data structure received from server
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching available quantities:', error);
                        sizeOutputInputsContainer.innerHTML = `
                            <div class="alert alert-danger">
                                Error loading available quantities: ${error.message}
                            </div>
                        `;
                    });
            });
        });
    </script>
</x-backend.layouts.master> --}}
