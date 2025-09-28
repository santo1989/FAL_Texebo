<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Add Finish Packing Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Add Finish Packing Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('finish_packing_data.index') }}">Finish Packing</a></li>
            <li class="breadcrumb-item active">Add New</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-backend.layouts.elements.errors />
    <form action="{{ route('finish_packing_data.store') }}" method="post">
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
                            <small>Packing Qty / Waste Qty</small>
                        </th>
                    @endforeach
                    <th>Total Packing Qty</th>
                    <th>Total Waste Qty</th>
                </tr>
            </thead>
            <tbody id="finish-packing-data-body">
                <!-- Dynamic rows will be injected here by JavaScript -->
            </tbody>
        </table>

        <a href="{{ route('finish_packing_data.index') }}" class="btn btn-secondary mt-3">Back</a>
        <button type="submit" class="btn btn-primary mt-3">Save Finish Packing Data</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const poNumberSelect = document.getElementById('po_number');
            const finishPackingDataBody = document.getElementById('finish-packing-data-body');
            let savedPackings = {};
            let processedCombinations = new Set();

            const initialPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
            if (initialPoNumbers.length > 0) {
                updateFinishPackingDataRows(initialPoNumbers);
            }

            poNumberSelect.addEventListener('change', function() {
                const selectedPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
                processedCombinations.clear();
                if (selectedPoNumbers.length > 0) {
                    updateFinishPackingDataRows(selectedPoNumbers);
                } else {
                    finishPackingDataBody.innerHTML = '';
                    savedPackings = {};
                }
            });

            function updateFinishPackingDataRows(poNumbers) {
                const url = '{{ route('finish_packing_data.find') }}?po_numbers[]=' + poNumbers.join('&po_numbers[]=');

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
                        finishPackingDataBody.innerHTML = '';
                        let rowIndex = 0;
                        processedCombinations.clear();

                        if (!data || Object.keys(data).length === 0) {
                            finishPackingDataBody.innerHTML =
                                '<tr><td colspan="100%">No data found for selected PO numbers.</td></tr>';
                            return;
                        }

                        let hasData = false;

                        for (const poNumber in data) {
                            if (!Array.isArray(data[poNumber])) continue;

                            if (data[poNumber].length === 0) {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td colspan="100%" class="text-center text-muted">
                                        PO: ${poNumber} - No available quantities for packing
                                    </td>
                                `;
                                finishPackingDataBody.appendChild(row);
                                continue;
                            }

                            data[poNumber].forEach(combination => {
                                if (!combination.combination_id || !combination.style || !combination.color ||
                                    !combination.available_quantities || !combination.size_ids) {
                                    console.error('Invalid combination data:', combination);
                                    return;
                                }

                                const combinationKey =
                                    `${combination.combination_id}-${combination.style}-${combination.color}-${poNumber}`;

                                if (processedCombinations.has(combinationKey)) {
                                    return;
                                }

                                processedCombinations.add(combinationKey);
                                hasData = true;

                                const row = document.createElement('tr');
                                const key = `${poNumber}-${combination.combination_id}`;
                                row.dataset.key = key;
                                row.dataset.rowIndex = rowIndex;
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
                                        const sizeName = "{{ $size->name }}";
                                        const availableQty = combination.available_quantities[sizeId] || 0;
                                        const savedPacking = (savedPackings[key] && savedPackings[key].packing && savedPackings[key].packing[sizeId]) || 0;
                                        const savedWaste = (savedPackings[key] && savedPackings[key].waste && savedPackings[key].waste[sizeId]) || 0;
                                        const isSizeAvailable = availableSizeIds.includes(sizeId) && availableQty > 0;

                                        const cell = document.createElement('td');
                                        if (isSizeAvailable) {
                                            cell.innerHTML = ` 
                                                <label>Max = ${availableQty} Pcs </label> <br>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" 
                                                           name="rows[${rowIndex}][packing_quantities][${sizeId}]" 
                                                           class="form-control packing-qty-input"
                                                           min="0" 
                                                           max="${availableQty}"
                                                           value="${savedPacking}"
                                                           placeholder="Av: ${availableQty}">
                                                    <input type="number" 
                                                           name="rows[${rowIndex}][packing_waste_quantities][${sizeId}]" 
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
                                    <td><span class="total-packing-qty-span">0</span></td>
                                    <td><span class="total-waste-qty-span">0</span></td>
                                `;

                                finishPackingDataBody.appendChild(row);
                                rowIndex++;
                            });
                        }

                        if (!hasData && finishPackingDataBody.children.length === 0) {
                            finishPackingDataBody.innerHTML =
                                '<tr><td colspan="100%">No available quantities found for the selected POs.</td></tr>';
                        }

                        finishPackingDataBody.querySelectorAll('.packing-qty-input, .waste-qty-input').forEach(input => {
                            input.dispatchEvent(new Event('input'));
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        finishPackingDataBody.innerHTML = '<tr><td colspan="100%">Error loading data. Error: ' + error.message + '</td></tr>';
                    });
            }

            finishPackingDataBody.addEventListener('input', function(e) {
                const target = e.target;
                const row = target.closest('tr');
                const key = row.dataset.key;
                const rowIndex = row.dataset.rowIndex;

                if (!savedPackings[key]) {
                    savedPackings[key] = {
                        packing: {},
                        waste: {}
                    };
                }

                let isPacking = target.classList.contains('packing-qty-input');
                let isWaste = target.classList.contains('waste-qty-input');

                if (isPacking || isWaste) {
                    const name = target.name;
                    const sizeId = name.match(/\[(packing_quantities|packing_waste_quantities)\]\[(\d+)\]/)[2];
                    const max = parseInt(target.getAttribute('max')) || 0;
                    let value = parseInt(target.value) || 0;

                    // Get corresponding inputs for this size
                    const packingInput = row.querySelector(`input[name="rows[${rowIndex}][packing_quantities][${sizeId}]"]`);
                    const wasteInput = row.querySelector(`input[name="rows[${rowIndex}][packing_waste_quantities][${sizeId}]"]`);
                    const packingQty = parseInt(packingInput.value) || 0;
                    const wasteQty = parseInt(wasteInput.value) || 0;

                    // Validate sum
                    if (packingQty + wasteQty > max) {
                        if (isPacking) {
                            // Adjust packing quantity
                            value = max - wasteQty;
                            packingInput.value = value >= 0 ? value : 0;
                        } else if (isWaste) {
                            // Adjust waste quantity
                            value = max - packingQty;
                            wasteInput.value = value >= 0 ? value : 0;
                        }

                        // Show error message
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'text-danger';
                        errorDiv.textContent = `Total for size ${sizeId} exceeds available quantity (${max})`;
                        const cell = target.closest('td');
                        const existingError = cell.querySelector('.text-danger');
                        if (existingError) existingError.remove();
                        cell.appendChild(errorDiv);
                    } else {
                        // Clear error message
                        const cell = target.closest('td');
                        const existingError = cell.querySelector('.text-danger');
                        if (existingError) existingError.remove();
                    }

                    // Update saved values
                    if (isPacking) {
                        savedPackings[key].packing[sizeId] = value;
                    } else if (isWaste) {
                        savedPackings[key].waste[sizeId] = value;
                    }

                    // Update totals
                    let totalPacking = 0;
                    let totalWaste = 0;
                    row.querySelectorAll('.packing-qty-input').forEach(input => {
                        totalPacking += parseInt(input.value) || 0;
                    });
                    row.querySelectorAll('.waste-qty-input').forEach(input => {
                        totalWaste += parseInt(input.value) || 0;
                    });

                    row.querySelector('.total-packing-qty-span').textContent = totalPacking;
                    row.querySelector('.total-waste-qty-span').textContent = totalWaste;
                }
            });
        });
    </script>

    <style>
        .text-danger {
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }
        .input-group-sm input {
            max-width: 100px;
        }
    </style>
</x-backend.layouts.master>