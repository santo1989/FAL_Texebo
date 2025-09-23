<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Add Sewing Input Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Add Sewing Input Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('line_input_data.index') }}">Sewing Input</a></li>
            <li class="breadcrumb-item active">Add New</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-backend.layouts.elements.errors />
    <form action="{{ route('line_input_data.store') }}" method="post">
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
                    <th rowspan="2">PO Number</th>
                    <th rowspan="2">Style</th>
                    <th rowspan="2">Color</th>
                    @foreach ($sizes as $size)
                        <th rowspan="2">
                            {{ $size->name }}
                        </th>
                    @endforeach
                    <th rowspan="2">Total Input Qty</th>
                </tr>
            </thead>
            <tbody id="line-input-data-body">
                <!-- Dynamic rows will be injected here by JavaScript -->
            </tbody>
        </table>

        <a href="{{ route('line_input_data.index') }}" class="btn btn-secondary mt-3">Back</a>
        <button type="submit" class="btn btn-primary mt-3">Save Sewing Input Data</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const poNumberSelect = document.getElementById('po_number');
            const lineInputDataBody = document.getElementById('line-input-data-body');
            let savedInputs = {};
            let rowIndex = 0; // Track row index globally

            function updateLineInputDataRows(poNumbers) {
                if (poNumbers.length === 0) {
                    lineInputDataBody.innerHTML = '';
                    savedInputs = {};
                    return;
                }

                const url = new URL('{{ route('line_input_data.find') }}');
                poNumbers.forEach(po => url.searchParams.append('po_numbers[]', po));

                fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        lineInputDataBody.innerHTML = '';
                        rowIndex = 0; // Reset row index for new data
                        savedInputs = {}; // Clear saved inputs

                        if (!data || Object.keys(data).length === 0) {
                            lineInputDataBody.innerHTML =
                                '<tr><td colspan="100%">No data found for selected PO numbers.</td></tr>';
                            return;
                        }

                        // Process each PO number
                        for (const poNumber in data) {
                            if (!Array.isArray(data[poNumber])) continue;

                            // Process each combination in this PO
                            data[poNumber].forEach(combination => {
                                if (!combination.combination_id || !combination.style || !combination
                                    .color || !combination.available_quantities || !combination.size_ids
                                    ) {
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

                                const availableSizeIds = combination.size_ids.map(id => parseInt(id));

                                @foreach ($sizes as $size)
                                    {
                                        const sizeId = {{ $size->id }};
                                        const availableQty = combination.available_quantities[sizeId] ||
                                            0;
                                        const orderQty = combination.order_quantities ? (combination
                                            .order_quantities[sizeId] || 0) : 0;
                                        const isSizeAvailable = availableSizeIds.includes(sizeId);
                                        const savedInput = (savedInputs[key] && savedInputs[key]
                                            .input && savedInputs[key].input[sizeId]) || 0;

                                        const cell = document.createElement('td');
                                        if (isSizeAvailable && availableQty > 0) {
                                            cell.innerHTML = `
                                    <div class="input-group input-group-sm">
                                        <label class="d-block w-100">Max Avail: ${availableQty}</label>
                                        <input type="number" 
                                               name="rows[${rowIndex}][input_quantities][${sizeId}]" 
                                               class="form-control input-qty-input" 
                                               min="0" 
                                               max="${availableQty}" 
                                               value="${savedInput}" 
                                               placeholder="Input">
                                    </div>
                                `;
                                        } else {
                                            cell.innerHTML =
                                                `<span class="text-muted text-center">N/A</span>`;
                                        }
                                        row.appendChild(cell);
                                    }
                                @endforeach

                                row.innerHTML += `
                        <td><span class="total-input-qty-span">0</span></td>
                    `;

                                lineInputDataBody.appendChild(row);

                                // Initialize saved inputs for this row
                                if (!savedInputs[key]) {
                                    savedInputs[key] = {
                                        input: {}
                                    };
                                }

                                rowIndex++;
                            });
                        }

                        // Update totals for all rows
                        lineInputDataBody.querySelectorAll('.input-qty-input').forEach(input => {
                            input.dispatchEvent(new Event('input'));
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        lineInputDataBody.innerHTML =
                            `<tr><td colspan="100%">Error loading data. Error: ${error.message}</td></tr>`;
                    });
            }

            poNumberSelect.addEventListener('change', function() {
                const selectedPoNumbers = Array.from(this.selectedOptions).map(option => option.value);
                rowIndex = 0; // Reset row index when PO selection changes
                updateLineInputDataRows(selectedPoNumbers);
            });

            // Event delegation for dynamic inputs
            lineInputDataBody.addEventListener('input', function(e) {
                const target = e.target;
                if (target.classList.contains('input-qty-input')) {
                    const row = target.closest('tr');
                    const key = row.dataset.key;

                    if (!savedInputs[key]) {
                        savedInputs[key] = {
                            input: {}
                        };
                    }

                    const name = target.name;
                    const sizeIdMatch = name.match(/\[input_quantities\]\[(\d+)\]/);
                    if (sizeIdMatch) {
                        const sizeId = sizeIdMatch[1];
                        let value = parseInt(target.value) || 0;

                        const maxQty = parseInt(target.getAttribute('max'));
                        if (!isNaN(maxQty) && !isNaN(value) && value > maxQty) {
                            value = maxQty;
                            target.value = maxQty;
                        }

                        // Save the updated value
                        savedInputs[key].input[sizeId] = value;

                        // Recalculate totals
                        let totalInput = 0;
                        row.querySelectorAll('.input-qty-input').forEach(input => {
                            totalInput += parseInt(input.value) || 0;
                        });

                        row.querySelector('.total-input-qty-span').textContent = totalInput;
                    }
                }
            });

            // Load initial data
            const initialPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
            if (initialPoNumbers.length > 0) {
                updateLineInputDataRows(initialPoNumbers);
            }
        });
    </script>
</x-backend.layouts.master>
