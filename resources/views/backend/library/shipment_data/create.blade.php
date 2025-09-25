<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Add Shipment Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Add Shipment Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('shipment_data.index') }}">Shipment</a></li>
            <li class="breadcrumb-item active">Add New</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-backend.layouts.elements.errors />
    <form action="{{ route('shipment_data.store') }}" method="post">
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
                            <small>Shipment Qty</small>
                        </th>
                    @endforeach
                    <th>Total Shipment Qty</th>
                    </tr>
            </thead>
            <tbody id="shipment-data-body">
                </tbody>
        </table>

        <a href="{{ route('shipment_data.index') }}" class="btn btn-secondary mt-3">Back</a>
        <button type="submit" class="btn btn-primary mt-3">Save Shipment Data</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const poNumberSelect = document.getElementById('po_number');
            const shipmentDataBody = document.getElementById('shipment-data-body');
            // 'savedShipments' will now only store shipment quantities
            let savedShipments = {}; 
            let processedCombinations = new Set();

            const initialPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
            if (initialPoNumbers.length > 0) {
                updateShipmentDataRows(initialPoNumbers);
            }

            poNumberSelect.addEventListener('change', function() {
                const selectedPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option
                    .value);
                processedCombinations.clear();
                if (selectedPoNumbers.length > 0) {
                    updateShipmentDataRows(selectedPoNumbers);
                } else {
                    shipmentDataBody.innerHTML = '';
                    savedShipments = {};
                }
            });

           function updateShipmentDataRows(poNumbers) {
                const url = '{{ route('shipment_data.find') }}?po_numbers[]=' + poNumbers.join('&po_numbers[]=');

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
                        shipmentDataBody.innerHTML = '';
                        let rowIndex = 0;
                        processedCombinations.clear();

                        if (!data || Object.keys(data).length === 0) {
                            shipmentDataBody.innerHTML = '<tr><td colspan="100%">No data found for selected PO numbers.</td></tr>';
                            return;
                        }

                        let hasData = false;

                        for (const poNumber in data) {
                            if (!Array.isArray(data[poNumber])) continue;

                            // Check if this PO has any combinations
                            if (data[poNumber].length === 0) {
                                // PO exists but has no available combinations
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td colspan="100%" class="text-center text-muted">
                                        PO: ${poNumber} - No available quantities for shipment
                                    </td>
                                `;
                                shipmentDataBody.appendChild(row);
                                continue;
                            }

                            data[poNumber].forEach(combination => {
                                if (!combination.combination_id || !combination.style || !combination.color ||
                                    !combination.available_quantities || !combination.size_ids) {
                                    console.error('Invalid combination data:', combination);
                                    return;
                                }

                                const combinationKey = `${combination.combination_id}-${combination.style}-${combination.color}-${poNumber}`;

                                if (processedCombinations.has(combinationKey)) {
                                    return;
                                }

                                processedCombinations.add(combinationKey);
                                hasData = true;

                                const row = document.createElement('tr');
                                const key = `${poNumber}-${combination.combination_id}`;
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
                                        const sizeName = "{{ $size->name }}";
                                        const availableQty = combination.available_quantities[sizeId] || 0;
                                        // Update: Only checking for saved shipment qty
                                        const savedShipment = (savedShipments[key] && savedShipments[key][sizeId]) || 0; 
                                        const isSizeAvailable = availableSizeIds.includes(sizeId) && availableQty > 0;

                                        const cell = document.createElement('td');
                                        if (isSizeAvailable) {
                                            // Update: Removed the waste qty input field
                                            cell.innerHTML = ` 
                                                <label>Max = ${availableQty} Pcs </label> <br>
                                                <input type="number" 
                                                    name="rows[${rowIndex}][shipment_quantities][${sizeId}]" 
                                                    class="form-control shipment-qty-input"
                                                    min="0" 
                                                    max="${availableQty}"
                                                    value="${savedShipment}"
                                                    placeholder="Shipment Qty">
                                            `;
                                        } else {
                                            cell.innerHTML = `<span class="text-muted text-center">N/A</span>`;
                                        }
                                        row.appendChild(cell);
                                    }
                                @endforeach

                                // Update: Removed total waste qty span
                                row.innerHTML += `
                                    <td><span class="total-shipment-qty-span">0</span></td>
                                `;

                                shipmentDataBody.appendChild(row);
                                rowIndex++;
                            });
                        }

                        if (!hasData && shipmentDataBody.children.length === 0) {
                            shipmentDataBody.innerHTML = '<tr><td colspan="100%">No available quantities found for the selected POs.</td></tr>';
                        }

                        // Update: Only dispatching event for shipment qty inputs
                        shipmentDataBody.querySelectorAll('.shipment-qty-input').forEach(
                            input => {
                                input.dispatchEvent(new Event('input'));
                            });
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        shipmentDataBody.innerHTML = '<tr><td colspan="100%">Error loading data. Error: ' + error.message + '</td></tr>';
                    });
            }

            shipmentDataBody.addEventListener('input', function(e) {
                const target = e.target;
                const row = target.closest('tr');
                const key = row.dataset.key;

                if (!savedShipments[key]) {
                    // Update: Initialize as an empty object for shipment quantities
                    savedShipments[key] = {};
                }

                // Update: Check only for shipment-qty-input
                let isShipment = target.classList.contains('shipment-qty-input');

                if (isShipment) {
                    const name = target.name;
                    // Update: Adjusted regex to look for shipment_quantities only
                    const match = name.match(/\[shipment_quantities\]\[(\d+)\]/); 
                    if (!match) return;
                    
                    const sizeId = match[1];
                    let value = parseInt(target.value) || 0;

                    const max = parseInt(target.getAttribute('max')) || 0;
                    if (value > max) {
                        value = max;
                        target.value = max;
                    }

                    // Update: Save the value directly to the key
                    savedShipments[key][sizeId] = value; 

                    let totalShipment = 0;

                    row.querySelectorAll('.shipment-qty-input').forEach(input => {
                        totalShipment += parseInt(input.value) || 0;
                    });
                    
                    // Update: Removed total waste calculation and display
                    row.querySelector('.total-shipment-qty-span').textContent = totalShipment;
                }
            });
        });
    </script>
</x-backend.layouts.master>
