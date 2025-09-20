{{-- <x-backend.layouts.master>
    <x-slot name="pageTitle">
        Add Sublimation Print/Receive Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Sublimation Print/Receive Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('sublimation_print_receive_data.index') }}">Sublimation Print/Receive Data</a></li>
            <li class="breadcrumb-item active">Add</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-backend.layouts.elements.errors />
    <form action="{{ route('sublimation_print_receive_data.store') }}" method="post">
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
            <div class="col-md-3">
                <div class="form-group">
                    <label for="old_order">Old Order</label>
                    <select name="old_order" id="old_order" class="form-control">
                        <option value="no" {{ old('old_order') == 'no' ? 'selected' : '' }}>No</option>
                        <option value="yes" {{ old('old_order') == 'yes' ? 'selected' : '' }}>Yes</option>
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
                    @foreach ($allSizes as $size)
                        <th>
                            {{ $size->name }}
                            <br>
                            <small>Receive Qty / Waste Qty</small>
                        </th>
                    @endforeach
                    <th>Total Receive Qty</th>
                    <th>Total Waste Qty</th>
                </tr>
            </thead>
            <tbody id="sublimation-print-receive-data-body">
                <!-- Dynamic rows will be injected here by JavaScript -->
            </tbody>
        </table>

        <a href="{{ route('sublimation_print_receive_data.index') }}" class="btn btn-secondary mt-3">Back</a>
        <button type="submit" class="btn btn-primary mt-3">Save Sublimation Print/Receive Data</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const poNumberSelect = document.getElementById('po_number');
            const sublimationPrintReceiveDataBody = document.getElementById('sublimation-print-receive-data-body');
            let savedInputs = {}; // Persist input values

            // Load initial data if any PO numbers are selected
            const initialPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
            if (initialPoNumbers.length > 0) {
                updateSublimationPrintReceiveDataRows(initialPoNumbers);
            }

            // Listen for changes in the PO number selection
            poNumberSelect.addEventListener('change', function() {
                const selectedPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
                if (selectedPoNumbers.length > 0) {
                    updateSublimationPrintReceiveDataRows(selectedPoNumbers);
                } else {
                    sublimationPrintReceiveDataBody.innerHTML = '';
                    savedInputs = {}; // Clear saved inputs when no POs selected
                }
            });

            function updateSublimationPrintReceiveDataRows(poNumbers) {
                const url = '{{ route('sublimation_print_receive_data.find') }}?po_numbers[]=' + poNumbers.join('&po_numbers[]=');
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
                        sublimationPrintReceiveDataBody.innerHTML = '';
                        let rowIndex = 0;

                        if (!data || Object.keys(data).length === 0) {
                            console.log('No data returned from server');
                            sublimationPrintReceiveDataBody.innerHTML = '<tr><td colspan="100%">No data found for selected PO numbers.</td></tr>';
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
                                        <input type="hidden" name="rows[${rowIndex}][product_combination_id]" value="${combination.combination_id}">
                                        ${poNumber}
                                    </td>
                                    <td class="text-center">${combination.style}</td>
                                    <td class="text-center">${combination.color}</td>
                                `;

                                // Available sizes for this PO
                                const availableSizeIds = combination.size_ids.map(id => String(id));

                                @foreach ($allSizes as $size)
                                    {
                                        const sizeId = "{{ $size->id }}";
                                        const sizeName = "{{ $size->name }}";
                                        const availableQty = combination.available_quantities[sizeName] || 0;
                                        const savedReceive = (savedInputs[key] && savedInputs[key].receive && savedInputs[key].receive[sizeId]) || 0;
                                        const savedWaste = (savedInputs[key] && savedInputs[key].waste && savedInputs[key].waste[sizeId]) || 0;
                                        const isSizeAvailable = availableSizeIds.includes(sizeId);

                                        const cell = document.createElement('td');
                                        if (isSizeAvailable && availableQty > 0) {
                                            cell.innerHTML = ` <label>Max = ${availableQty} Pcs </label> <br>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" 
                                                           name="rows[${rowIndex}][sublimation_print_receive_quantities][${sizeId}]" 
                                                           class="form-control receive-qty-input"
                                                           min="0" 
                                                           max="${availableQty}"
                                                           value="${savedReceive}"
                                                           placeholder="Av: ${availableQty}">
                                                    <input type="number" 
                                                           name="rows[${rowIndex}][sublimation_print_receive_waste_quantities][${sizeId}]" 
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
                                    <td><span class="total-receive-qty-span">0</span></td>
                                    <td><span class="total-waste-qty-span">0</span></td>
                                `;

                                sublimationPrintReceiveDataBody.appendChild(row);
                                rowIndex++;
                            });
                        }

                        // Trigger input event to calculate initial totals
                        sublimationPrintReceiveDataBody.querySelectorAll('.receive-qty-input, .waste-qty-input').forEach(input => {
                            input.dispatchEvent(new Event('input'));
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        sublimationPrintReceiveDataBody.innerHTML = '<tr><td colspan="100%">Error loading data. Please try again.</td></tr>';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load data. Please try again.',
                        });
                    });
            }

            // Calculate totals and persist input values
            sublimationPrintReceiveDataBody.addEventListener('input', function(e) {
                const target = e.target;
                const row = target.closest('tr');
                const key = row.dataset.key;

                if (!savedInputs[key]) {
                    savedInputs[key] = { receive: {}, waste: {} };
                }

                let isReceive = target.classList.contains('receive-qty-input');
                let isWaste = target.classList.contains('waste-qty-input');

                if (isReceive || isWaste) {
                    const name = target.name;
                    const sizeId = name.match(/\[(sublimation_print_receive_quantities|sublimation_print_receive_waste_quantities)\]\[(\d+)\]/)[2];
                    let value = parseInt(target.value) || 0;

                    if (isReceive) {
                        const max = parseInt(target.getAttribute('max')) || 0;
                        if (value > max) {
                            value = max;
                            target.value = max;
                            Swal.fire({
                                icon: 'warning',
                                title: 'Invalid Input',
                                text: `Receive quantity cannot exceed available quantity (${max}).`,
                            });
                        }
                    }

                    // Save the updated value
                    if (isReceive) {
                        savedInputs[key].receive[sizeId] = value;
                    } else if (isWaste) {
                        savedInputs[key].waste[sizeId] = value;
                    }

                    // Recalculate totals
                    let totalReceive = 0;
                    let totalWaste = 0;

                    row.querySelectorAll('.receive-qty-input').forEach(input => {
                        totalReceive += parseInt(input.value) || 0;
                    });

                    row.querySelectorAll('.waste-qty-input').forEach(input => {
                        totalWaste += parseInt(input.value) || 0;
                    });

                    row.querySelector('.total-receive-qty-span').textContent = totalReceive;
                    row.querySelector('.total-waste-qty-span').textContent = totalWaste;
                }
            });
        });
    </script>
</x-backend.layouts.master> --}}

<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Add Sublimation Print/Receive Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Sublimation Print/Receive Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('sublimation_print_receive_data.index') }}">Sublimation Print/Receive Data</a></li>
            <li class="breadcrumb-item active">Add</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-backend.layouts.elements.errors />
    <form action="{{ route('sublimation_print_receive_data.store') }}" method="post">
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
            <div class="col-md-3">
                <div class="form-group">
                    <label for="old_order">Old Order</label>
                    {{-- <select name="old_order" id="old_order" class="form-control">
                        <option value="no" {{ old('old_order') == 'no' ? 'selected' : '' }}>No</option>
                        <option value="yes" {{ old('old_order') == 'yes' ? 'selected' : '' }}>Yes</option>
                    </select> --}}
                    <input type="text" name="old_order" id="old_order" class="form-control"
                        value="no" readonly>
                </div>
            </div>
        </div>

        <table class="table table-bordered mt-4 text-center">
            <thead>
                <tr>
                    <th rowspan="2">PO Number</th>
                    <th rowspan="2">Style</th>
                    <th rowspan="2">Color</th>
                    @foreach ($allSizes as $size)
                        <th colspan="2">{{ $size->name }}</th>
                    @endforeach
                    <th rowspan="2">Total Receive Qty</th>
                    <th rowspan="2">Total Waste Qty</th>
                </tr>
                <tr>
                    @foreach ($allSizes as $size)
                        <th>Receive Qty</th>
                        <th>Waste Qty</th>
                    @endforeach
                </tr>
            </thead>
            <tbody id="sublimation-print-receive-data-body">
                <!-- Dynamic rows will be injected here by JavaScript -->
            </tbody>
        </table>

        <a href="{{ route('sublimation_print_receive_data.index') }}" class="btn btn-secondary mt-3">Back</a>
        <button type="submit" class="btn btn-primary mt-3">Save Sublimation Print/Receive Data</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const poNumberSelect = document.getElementById('po_number');
            const sublimationPrintReceiveDataBody = document.getElementById('sublimation-print-receive-data-body');
            let savedInputs = {}; // Persist input values

            // Load initial data if any PO numbers are selected
            const initialPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
            if (initialPoNumbers.length > 0) {
                updateSublimationPrintReceiveDataRows(initialPoNumbers);
            }

            // Listen for changes in the PO number selection
            poNumberSelect.addEventListener('change', function() {
                const selectedPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
                if (selectedPoNumbers.length > 0) {
                    updateSublimationPrintReceiveDataRows(selectedPoNumbers);
                } else {
                    sublimationPrintReceiveDataBody.innerHTML = '';
                    savedInputs = {}; // Clear saved inputs when no POs selected
                }
            });

            function updateSublimationPrintReceiveDataRows(poNumbers) {
                const url = '{{ route('sublimation_print_receive_data.find') }}?po_numbers[]=' + poNumbers.join('&po_numbers[]=');
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
                        sublimationPrintReceiveDataBody.innerHTML = '';
                        let rowIndex = 0;

                        if (!data || Object.keys(data).length === 0) {
                            console.log('No data returned from server');
                            sublimationPrintReceiveDataBody.innerHTML = '<tr><td colspan="100%">No data found for selected PO numbers.</td></tr>';
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
                                        <input type="hidden" name="rows[${rowIndex}][product_combination_id]" value="${combination.combination_id}">
                                        <input type="hidden" name="rows[${rowIndex}][po_number]" value="${poNumber}">
                                        ${poNumber}
                                    </td>
                                    <td class="text-center">${combination.style}</td>
                                    <td class="text-center">${combination.color}</td>
                                `;

                                // Available sizes for this PO
                                const availableSizeIds = combination.size_ids.map(id => String(id));

                                @foreach ($allSizes as $size)
                                    {
                                        const sizeId = "{{ $size->id }}";
                                        const sizeName = "{{ $size->name }}";
                                        const availableQty = combination.available_quantities[sizeName] || 0;
                                        const savedReceive = (savedInputs[key] && savedInputs[key].receive && savedInputs[key].receive[sizeId]) || 0;
                                        const savedWaste = (savedInputs[key] && savedInputs[key].waste && savedInputs[key].waste[sizeId]) || 0;
                                        const isSizeAvailable = availableSizeIds.includes(sizeId);

                                        // Create two separate cells for each size (receive and waste)
                                        if (isSizeAvailable && availableQty > 0) {
                                            // Receive quantity cell
                                            const receiveCell = document.createElement('td');
                                            receiveCell.innerHTML = `
                                        <label>Max = ${availableQty} Pcs </label> <br>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" 
                                                           name="rows[${rowIndex}][sublimation_print_receive_quantities][${sizeId}]" 
                                                           class="form-control receive-qty-input"
                                                           min="0" 
                                                           max="${availableQty}"
                                                           value="${savedReceive}"
                                                           placeholder="Av: ${availableQty}">
                                                </div>
                                            `;
                                            row.appendChild(receiveCell);

                                            // Waste quantity cell
                                            const wasteCell = document.createElement('td');
                                            wasteCell.innerHTML = `
                                            <label></label> <br>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" 
                                                           name="rows[${rowIndex}][sublimation_print_receive_waste_quantities][${sizeId}]" 
                                                           class="form-control waste-qty-input"
                                                           min="0" 
                                                           value="${savedWaste}"
                                                           max="${availableQty}"
                                                           placeholder="W: 0">
                                                </div>
                                            `;
                                            row.appendChild(wasteCell);
                                        } else {
                                            // Two empty cells for unavailable sizes
                                            const emptyCell1 = document.createElement('td');
                                            emptyCell1.innerHTML = `<span class="text-muted">N/A</span>`;
                                            row.appendChild(emptyCell1);
                                            
                                            const emptyCell2 = document.createElement('td');
                                            emptyCell2.innerHTML = `<span class="text-muted">N/A</span>`;
                                            row.appendChild(emptyCell2);
                                        }
                                    }
                                @endforeach

                                row.innerHTML += `
                                    <td><span class="total-receive-qty-span">0</span></td>
                                    <td><span class="total-waste-qty-span">0</span></td>
                                `;

                                sublimationPrintReceiveDataBody.appendChild(row);
                                rowIndex++;
                            });
                        }

                        // Trigger input event to calculate initial totals
                        sublimationPrintReceiveDataBody.querySelectorAll('.receive-qty-input, .waste-qty-input').forEach(input => {
                            input.dispatchEvent(new Event('input'));
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        sublimationPrintReceiveDataBody.innerHTML = '<tr><td colspan="100%">Error loading data. Please try again.</td></tr>';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load data. Please try again.',
                        });
                    });
            }

            // Calculate totals and persist input values
            sublimationPrintReceiveDataBody.addEventListener('input', function(e) {
                const target = e.target;
                const row = target.closest('tr');
                const key = row.dataset.key;

                if (!savedInputs[key]) {
                    savedInputs[key] = { receive: {}, waste: {} };
                }

                let isReceive = target.classList.contains('receive-qty-input');
                let isWaste = target.classList.contains('waste-qty-input');

                if (isReceive || isWaste) {
                    const name = target.name;
                    const sizeId = name.match(/\[(sublimation_print_receive_quantities|sublimation_print_receive_waste_quantities)\]\[(\d+)\]/)[2];
                    let value = parseInt(target.value) || 0;

                    if (isReceive) {
                        const max = parseInt(target.getAttribute('max')) || 0;
                        if (value > max) {
                            value = max;
                            target.value = max;
                            Swal.fire({
                                icon: 'warning',
                                title: 'Invalid Input',
                                text: `Receive quantity cannot exceed available quantity (${max}).`,
                            });
                        }
                    }

                    // Save the updated value
                    if (isReceive) {
                        savedInputs[key].receive[sizeId] = value;
                    } else if (isWaste) {
                        savedInputs[key].waste[sizeId] = value;
                    }

                    // Recalculate totals
                    let totalReceive = 0;
                    let totalWaste = 0;

                    row.querySelectorAll('.receive-qty-input').forEach(input => {
                        totalReceive += parseInt(input.value) || 0;
                    });

                    row.querySelectorAll('.waste-qty-input').forEach(input => {
                        totalWaste += parseInt(input.value) || 0;
                    });

                    row.querySelector('.total-receive-qty-span').textContent = totalReceive;
                    row.querySelector('.total-waste-qty-span').textContent = totalWaste;
                }
            });
        });
    </script>
</x-backend.layouts.master>