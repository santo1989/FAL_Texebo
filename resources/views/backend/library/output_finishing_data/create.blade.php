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
                                               placeholder="Av: ${availableQty}"
                                               data-available="${availableQty}">
                                        <input type="number" 
                                               name="rows[${rowIndex}][output_waste_quantities][${sizeId}]" 
                                               class="form-control waste-qty-input"
                                               min="0" 
                                               value="${savedWaste}"
                                               max="${availableQty}"
                                               placeholder="W: 0"
                                               data-available="${availableQty}">
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

            const isOutput = target.classList.contains('output-qty-input');
            let value = parseInt(target.value) || 0;

            if (value < 0) {
                value = 0;
                target.value = 0;
            }

            const available = parseInt(target.dataset.available) || 0;

            if (value > available) {
                value = available;
                target.value = available;
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Input',
                    text: `${isOutput ? 'Output' : 'Waste'} quantity cannot exceed available quantity (${available}).`,
                });
            }

            const name = target.name;
            const match = name.match(/\[(output_quantities|output_waste_quantities)\]\[(\d+)\]/);
            if (match) {
                const sizeId = match[2];

                const outputInput = row.querySelector(`input[name$="[output_quantities][${sizeId}]"]`);
                const wasteInput = row.querySelector(`input[name$="[output_waste_quantities][${sizeId}]"]`);

                let outputVal = parseInt(outputInput.value) || 0;
                let wasteVal = parseInt(wasteInput.value) || 0;

                if (outputVal + wasteVal > available) {
                    const otherVal = isOutput ? wasteVal : outputVal;
                    const maxAllowed = available - otherVal;
                    target.value = maxAllowed;
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Input',
                        text: `Output + Waste cannot exceed available quantity (${available}). Adjusted to ${maxAllowed}.`,
                    });
                    value = maxAllowed;
                }

                if (isOutput) {
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