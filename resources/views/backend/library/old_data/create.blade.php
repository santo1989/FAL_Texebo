<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Add Old Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Old Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('old_data_index') }}">Old Data</a></li>
            <li class="breadcrumb-item active">Add</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-backend.layouts.elements.errors />
    <form action="{{ route('old_data_store') }}" method="post">
        @csrf
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" name="date" id="date" class="form-control"
                        value="{{ old('date', date('Y-m-d')) }}" required>
                </div>
            </div>
            <div class="col-md-3">
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
            <div class="col-md-3">
                <div class="form-group">
                    <label for="Stage">Stage</label>
                    <select name="Stage" id="Stage" class="form-control">
                        <option value="" disabled selected>Select Stage</option>
                        <option value="CuttingData" {{ old('Stage') == 'CuttingData' ? 'selected' : '' }}>Cutting Data</option>
                        <option value="SublimationPrintSend" {{ old('Stage') == 'SublimationPrintSend' ? 'selected' : '' }}>Sublimation Print Send</option>
                        <option value="SublimationPrintReceive" {{ old('Stage') == 'SublimationPrintReceive' ? 'selected' : '' }}>Sublimation Print Receive</option>
                        <option value="PrintSendData" {{ old('Stage') == 'PrintSendData' ? 'selected' : '' }}>Print Send Data</option>
                        <option value="PrintReceiveData" {{ old('Stage') == 'PrintReceiveData' ? 'selected' : '' }}>Print Receive Data</option>
                        <option value="LineInputData" {{ old('Stage') == 'LineInputData' ? 'selected' : '' }}>Line Input Data</option>
                        <option value="OutputFinishingData" {{ old('Stage') == 'OutputFinishingData' ? 'selected' : '' }}>Output Finishing Data</option>
                        <option value="FinishPackingData" {{ old('Stage') == 'FinishPackingData' ? 'selected' : '' }}>Finish Packing Data</option>
                        <option value="ShipmentData" {{ old('Stage') == 'ShipmentData' ? 'selected' : '' }}>Shipment Data</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="old_order">Old Order</label>
                    <select name="old_order" id="old_order" class="form-control">
                        {{-- <option value="no" {{ old('old_order') == 'no' ? 'selected' : '' }}>No</option> --}}
                        <option value="yes" {{ old('old_order') == 'yes' ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>
            </div>
        </div>

        <table class="table table-bordered mt-4 text-center text-wrap" style="width: 100%; table-layout: responsive;">
            <thead>
                <tr>
                    <th rowspan="2">PO Number</th>
                    <th rowspan="2">Style</th>
                    <th rowspan="2">Color</th>
                    @foreach ($allSizes as $size)
                        <th colspan="2">
                            {{ $size->name }}
                        </th>
                    @endforeach
                    <th rowspan="2">Total Qty</th>
                    <th rowspan="2">Waste Qty</th>
                </tr>
                <tr>
                    @foreach ($allSizes as $size)
                        <td>Qty</td>
                        <td>Waste Qty</td>
                    @endforeach
                </tr>
            </thead>
            <tbody id="Old_data_qty-data-body">

            </tbody>
        </table>

        <a href="{{ route('old_data_index') }}" class="btn btn-secondary mt-3">Back</a>
        <button type="submit" class="btn btn-primary mt-3">Save Old Data</button>
    </form>

     <script>
        document.addEventListener('DOMContentLoaded', function() {
            const poNumberSelect = document.getElementById('po_number');
            const cuttingDataBody = document.getElementById('Old_data_qty-data-body');
            let savedInputs = {}; // Persist input values

            // Load initial data if any PO numbers are selected
            const initialPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
            if (initialPoNumbers.length > 0) {
                updateCuttingDataRows(initialPoNumbers);
            }

            // Listen for changes in the PO number selection
            poNumberSelect.addEventListener('change', function() {
                const selectedPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
                if (selectedPoNumbers.length > 0) {
                    updateCuttingDataRows(selectedPoNumbers);
                } else {
                    cuttingDataBody.innerHTML = '';
                    savedInputs = {}; // Clear saved inputs when no POs selected
                }
            });

            function updateCuttingDataRows(poNumbers) {
                const url = '{{ route('cutting_data.find') }}?po_numbers[]=' + poNumbers.join('&po_numbers[]=');
                console.log('Fetching URL:', url);

                fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Raw response data:', JSON.stringify(data, null, 2));
                        cuttingDataBody.innerHTML = '';
                        let rowIndex = 0;

                        if (!data || Object.keys(data).length === 0) {
                            console.log('No data returned from server');
                            cuttingDataBody.innerHTML = '<tr><td colspan="100%">No data found for selected PO numbers.</td></tr>';
                            return;
                        }

                        for (const poNumber in data) {
                            if (!Array.isArray(data[poNumber])) {
                                console.error(`Invalid data format for PO ${poNumber}`);
                                continue;
                            }
                            data[poNumber].forEach(combination => {
                                if (!combination.combination_id || !combination.style || !combination.color || !combination.available_quantities || !combination.size_ids) {
                                    console.error(`Incomplete combination data for PO ${poNumber}:`, combination);
                                    return;
                                }
                                const row = document.createElement('tr');
                                const key = `${poNumber}-${combination.combination_id}`;
                                row.dataset.key = key;
                                row.innerHTML = `
                                    <td class="text-center">
                                        <input type="hidden" name="rows[${rowIndex}][po_number]" value="${poNumber}">
                                        <input type="hidden" name="rows[${rowIndex}][product_combination_id]" value="${combination.combination_id}">
                                        ${poNumber}
                                    </td>
                                    <td class="text-center">${combination.style}</td>
                                    <td class="text-center">${combination.color}</td>
                                `;

                                const availableSizeIds = combination.size_ids.map(id => String(id));

                                @foreach ($allSizes as $size)
                                    {
                                        const sizeId = "{{ $size->id }}";
                                        const sizeName = "{{ $size->name }}";
                                        const availableQty = combination.available_quantities[sizeName] || 0;
                                        const savedQty = (savedInputs[key] && savedInputs[key].qty && savedInputs[key].qty[sizeId]) || 0;
                                        const savedWaste = (savedInputs[key] && savedInputs[key].waste && savedInputs[key].waste[sizeId]) || 0;
                                        const isSizeAvailable = availableSizeIds.includes(sizeId);

                                        // Quantity cell
                                        const qtyCell = document.createElement('td');
                                        if (isSizeAvailable && availableQty > 0) {
                                            qtyCell.innerHTML = `
                                                
                                                <div class="input-group input-group-sm">
                                                    <input type="number"
                                                        name="rows[${rowIndex}][Old_data_qty][${sizeId}]"
                                                        class="form-control qty-input"
                                                        min="0"
                                                        max="${availableQty}"
                                                        value="${savedQty}"
                                                        placeholder="Av: ${availableQty}">
                                                </div><br><small>Max = ${availableQty} Pcs </small> 
                                            `;
                                        } else {
                                            qtyCell.innerHTML = `<span class="text-muted text-center">N/A</span>`;
                                        }
                                        row.appendChild(qtyCell);

                                        // Waste cell
                                        const wasteCell = document.createElement('td');
                                        if (isSizeAvailable && availableQty > 0) {
                                            wasteCell.innerHTML = `
                                                <div class="input-group input-group-sm">
                                                    <input type="number"
                                                        name="rows[${rowIndex}][Old_data_waste][${sizeId}]"
                                                        class="form-control waste-input"
                                                        min="0"
                                                        max="${availableQty}"
                                                        value="${savedWaste}"
                                                        placeholder="Waste">
                                                </div>
                                            `;
                                        } else {
                                            wasteCell.innerHTML = `<span class="text-muted text-center">N/A</span>`;
                                        }
                                        row.appendChild(wasteCell);
                                    }
                                @endforeach

                                // Add total quantity and total waste cells
                                row.innerHTML += `
                                    <td class="total-qty-cell"><span class="total-qty-span">0</span></td>
                                    <td class="total-waste-cell"><span class="total-waste-span">0</span></td>
                                `;

                                cuttingDataBody.appendChild(row);
                                rowIndex++;
                            });
                        }

                        // Trigger input event to calculate initial totals
                        cuttingDataBody.querySelectorAll('.qty-input, .waste-input').forEach(input => {
                            input.dispatchEvent(new Event('input'));
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        cuttingDataBody.innerHTML = '<tr><td colspan="100%">Error loading data. Please try again.</td></tr>';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load data. Please try again.',
                        });
                    });
            }

            // Event delegation for input events
            cuttingDataBody.addEventListener('input', function(e) {
                const target = e.target;
                const row = target.closest('tr');
                const key = row.dataset.key;

                if (!savedInputs[key]) {
                    savedInputs[key] = { qty: {}, waste: {} };
                }

                if (target.classList.contains('qty-input')) {
                    const name = target.name;
                    const sizeId = name.match(/\[Old_data_qty\]\[(\d+)\]/)[1];
                    let value = parseInt(target.value) || 0;
                    const max = parseInt(target.getAttribute('max')) || 0;

                    if (value > max) {
                        value = max;
                        target.value = max;
                        Swal.fire({
                            icon: 'warning',
                            title: 'Invalid Input',
                            text: `Quantity cannot exceed available quantity (${max}).`,
                        });
                    }

                    savedInputs[key].qty[sizeId] = value;
                    updateRowTotals(row);
                } 
                else if (target.classList.contains('waste-input')) {
                    const name = target.name;
                    const sizeId = name.match(/\[Old_data_waste\]\[(\d+)\]/)[1];
                    let value = parseInt(target.value) || 0;
                    const max = parseInt(target.getAttribute('max')) || 0;

                    if (value > max) {
                        value = max;
                        target.value = max;
                        Swal.fire({
                            icon: 'warning',
                            title: 'Invalid Input',
                            text: `Waste quantity cannot exceed available quantity (${max}).`,
                        });
                    }

                    savedInputs[key].waste[sizeId] = value;
                    updateRowTotals(row);
                }
            });

            // Function to update row totals
            function updateRowTotals(row) {
                let totalQty = 0;
                let totalWaste = 0;
                
                // Calculate total quantity
                row.querySelectorAll('.qty-input').forEach(input => {
                    totalQty += parseInt(input.value) || 0;
                });
                
                // Calculate total waste
                row.querySelectorAll('.waste-input').forEach(input => {
                    totalWaste += parseInt(input.value) || 0;
                });
                
                // Update display
                row.querySelector('.total-qty-span').textContent = totalQty;
                row.querySelector('.total-waste-span').textContent = totalWaste;
            }
        });
    </script>
</x-backend.layouts.master>