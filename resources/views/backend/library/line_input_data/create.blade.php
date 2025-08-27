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
                    <th>PO Number</th>
                    <th>Style</th>
                    <th>Color</th>
                    @foreach ($sizes as $size)
                        <th>
                            {{ $size->name }}
                            <br>
                            <small>Input Qty / Waste Qty</small>
                        </th>
                    @endforeach
                    <th>Total Input Qty</th>
                    <th>Total Waste Qty</th>
                </tr>
            </thead>
            <tbody id="line-input-data-body">
                <!-- Dynamic rows will be injected here by JavaScript -->
            </tbody>
        </table>

        <a href="{{ route('line_input_data.index') }}" class="btn btn-secondary mt-3">Back</a>
        <button type="submit" class="btn btn-primary mt-3">Save Line Input Data</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const poNumberSelect = document.getElementById('po_number');
            const lineInputDataBody = document.getElementById('line-input-data-body');
            let savedInputs = {};
            let processedCombinations = new Set();

            const initialPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
            if (initialPoNumbers.length > 0) {
                updateLineInputDataRows(initialPoNumbers);
            }

            poNumberSelect.addEventListener('change', function() {
                const selectedPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
                processedCombinations.clear();
                if (selectedPoNumbers.length > 0) {
                    updateLineInputDataRows(selectedPoNumbers);
                } else {
                    lineInputDataBody.innerHTML = '';
                    savedInputs = {};
                }
            });

            function updateLineInputDataRows(poNumbers) {
                const url = '{{ route('line_input_data.find') }}?po_numbers[]=' + poNumbers.join('&po_numbers[]=');

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
                    lineInputDataBody.innerHTML = '';
                    let rowIndex = 0;
                    processedCombinations.clear();

                    if (!data || Object.keys(data).length === 0) {
                        lineInputDataBody.innerHTML = '<tr><td colspan="100%">No data found for selected PO numbers.</td></tr>';
                        return;
                    }

                    for (const poNumber in data) {
                        if (!Array.isArray(data[poNumber])) continue;
                        
                        data[poNumber].forEach(combination => {
                            if (!combination.combination_id || !combination.style || !combination.color || !combination.available_quantities || !combination.size_ids) {
                                return;
                            }
                            
                            const combinationKey = `${combination.combination_id}-${combination.style}-${combination.color}`;
                            
                            if (processedCombinations.has(combinationKey)) {
                                return;
                            }
                            
                            processedCombinations.add(combinationKey);
                            
                            const row = document.createElement('tr');
                            const key = `${poNumber}-${combination.combination_id}`;
                            row.dataset.key = key;
                            row.innerHTML = `
                                <td class="text-center">
                                    <input type="hidden" name="rows[${rowIndex}][product_combination_id]" value="${combination.combination_id}">
                                    ${poNumber}
                                </td>
                                <td class="text-center">${combination.style}</td>
                                <td class="text-center">${combination.color}</td>
                            `;

                            const availableSizeIds = combination.size_ids.map(id => String(id));

                            @foreach ($sizes as $size)
                                {
                                    const sizeId = "{{ $size->id }}";
                                    const sizeName = "{{ $size->name }}";
                                    const availableQty = combination.available_quantities[sizeName.toLowerCase()] || 0;
                                    const savedInput = (savedInputs[key] && savedInputs[key].input && savedInputs[key].input[sizeId]) || 0;
                                    const savedWaste = (savedInputs[key] && savedInputs[key].waste && savedInputs[key].waste[sizeId]) || 0;
                                    const isSizeAvailable = availableSizeIds.includes(sizeId);

                                    const cell = document.createElement('td');
                                    if (isSizeAvailable && availableQty > 0) {
                                        cell.innerHTML = ` <label>Max = ${availableQty} Pcs </label> <br>
                                            <div class="input-group input-group-sm">
                                                <input type="number" 
                                                       name="rows[${rowIndex}][input_quantities][${sizeId}]" 
                                                       class="form-control input-qty-input"
                                                       min="0" 
                                                       max="${availableQty}"
                                                       value="${savedInput}"
                                                       placeholder="Av: ${availableQty}">
                                                <input type="number" 
                                                       name="rows[${rowIndex}][input_waste_quantities][${sizeId}]" 
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
                                <td><span class="total-input-qty-span">0</span></td>
                                <td><span class="total-waste-qty-span">0</span></td>
                            `;

                            lineInputDataBody.appendChild(row);
                            rowIndex++;
                        });
                    }

                    lineInputDataBody.querySelectorAll('.input-qty-input, .waste-qty-input').forEach(input => {
                        input.dispatchEvent(new Event('input'));
                    });
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    lineInputDataBody.innerHTML = '<tr><td colspan="100%">Error loading data. Please try again.</td></tr>';
                });
            }

            lineInputDataBody.addEventListener('input', function(e) {
                const target = e.target;
                const row = target.closest('tr');
                const key = row.dataset.key;

                if (!savedInputs[key]) {
                    savedInputs[key] = { input: {}, waste: {} };
                }

                let isInput = target.classList.contains('input-qty-input');
                let isWaste = target.classList.contains('waste-qty-input');

                if (isInput || isWaste) {
                    const name = target.name;
                    const sizeId = name.match(/\[(input_quantities|input_waste_quantities)\]\[(\d+)\]/)[2];
                    let value = parseInt(target.value) || 0;

                    if (isInput) {
                        const max = parseInt(target.getAttribute('max')) || 0;
                        if (value > max) {
                            value = max;
                            target.value = max;
                        }
                    }

                    if (isInput) {
                        savedInputs[key].input[sizeId] = value;
                    } else if (isWaste) {
                        savedInputs[key].waste[sizeId] = value;
                    }

                    let totalInput = 0;
                    let totalWaste = 0;

                    row.querySelectorAll('.input-qty-input').forEach(input => {
                        totalInput += parseInt(input.value) || 0;
                    });

                    row.querySelectorAll('.waste-qty-input').forEach(input => {
                        totalWaste += parseInt(input.value) || 0;
                    });

                    row.querySelector('.total-input-qty-span').textContent = totalInput;
                    row.querySelector('.total-waste-qty-span').textContent = totalWaste;
                }
            });
        });
    </script>
</x-backend.layouts.master>