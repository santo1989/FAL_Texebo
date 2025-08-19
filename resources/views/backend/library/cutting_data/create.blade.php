 <x-backend.layouts.master>
    <x-slot name="pageTitle">
        Add Cutting Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Cutting Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('cutting_data.index') }}">Cutting Data</a></li>
            <li class="breadcrumb-item active">Add</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-backend.layouts.elements.errors />
    <form action="{{ route('cutting_data.store') }}" method="post">
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
                            <small>Cut Qty / Waste Qty</small>
                        </th>
                    @endforeach
                    <th>Total Cut Qty</th>
                    <th>Total Waste Qty</th>
                </tr>
            </thead>
            <tbody id="cutting-data-body">
                <!-- Dynamic rows will be injected here by JavaScript -->
            </tbody>
        </table>

        <a href="{{ route('cutting_data.index') }}" class="btn btn-secondary mt-3">Back</a>
        <button type="submit" class="btn btn-primary mt-3">Save Cutting Data</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2
            // $('#po_number').select2({
            //     placeholder: 'Select PO Number(s)',
            //     allowClear: true,
            //     width: '100%'
            // });

            const poNumberSelect = document.getElementById('po_number');
            const cuttingDataBody = document.getElementById('cutting-data-body');
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

                                // Available sizes for this PO
                                const availableSizeIds = combination.size_ids.map(id => String(id));

                                @foreach ($allSizes as $size)
                                    {
                                        const sizeId = "{{ $size->id }}";
                                        const sizeName = "{{ $size->name }}";
                                        const availableQty = combination.available_quantities[sizeName] || 0;
                                        const savedCut = (savedInputs[key] && savedInputs[key].cut && savedInputs[key].cut[sizeId]) || 0;
                                        const savedWaste = (savedInputs[key] && savedInputs[key].waste && savedInputs[key].waste[sizeId]) || 0;
                                        const isSizeAvailable = availableSizeIds.includes(sizeId);

                                        const cell = document.createElement('td');
                                        if (isSizeAvailable && availableQty > 0) {
                                            cell.innerHTML = ` <label>Max = ${availableQty} Pcs </label> <br>
                                                <div class="input-group input-group-sm">
                                                   
                                                    <input type="number" 
                                                           name="rows[${rowIndex}][cut_quantities][${sizeId}]" 
                                                           class="form-control cut-qty-input"
                                                           min="0" 
                                                           max="${availableQty}"
                                                           value="${savedCut}"
                                                           placeholder="Av: ${availableQty}">
                                                    <input type="number" 
                                                           name="rows[${rowIndex}][waste_quantities][${sizeId}]" 
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
                                    <td><span class="total-cut-qty-span">0</span></td>
                                    <td><span class="total-waste-qty-span">0</span></td>
                                `;

                                cuttingDataBody.appendChild(row);
                                rowIndex++;
                            });
                        }

                        // Trigger input event to calculate initial totals
                        cuttingDataBody.querySelectorAll('.cut-qty-input, .waste-qty-input').forEach(input => {
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

            // Calculate totals and persist input values
            cuttingDataBody.addEventListener('input', function(e) {
                const target = e.target;
                const row = target.closest('tr');
                const key = row.dataset.key;

                if (!savedInputs[key]) {
                    savedInputs[key] = { cut: {}, waste: {} };
                }

                let isCut = target.classList.contains('cut-qty-input');
                let isWaste = target.classList.contains('waste-qty-input');

                if (isCut || isWaste) {
                    const name = target.name;
                    const sizeId = name.match(/\[(cut|waste)_quantities\]\[(\d+)\]/)[2];
                    let value = parseInt(target.value) || 0;

                    if (isCut) {
                        const max = parseInt(target.getAttribute('max')) || 0;
                        if (value > max) {
                            value = max;
                            target.value = max;
                            Swal.fire({
                                icon: 'warning',
                                title: 'Invalid Input',
                                text: `Cut quantity cannot exceed available quantity (${max}).`,
                            });
                        }
                    }

                    // Save the updated value
                    if (isCut) {
                        savedInputs[key].cut[sizeId] = value;
                    } else if (isWaste) {
                        savedInputs[key].waste[sizeId] = value;
                    }

                    // Recalculate totals
                    let totalCut = 0;
                    let totalWaste = 0;

                    row.querySelectorAll('.cut-qty-input').forEach(input => {
                        totalCut += parseInt(input.value) || 0;
                    });

                    row.querySelectorAll('.waste-qty-input').forEach(input => {
                        totalWaste += parseInt(input.value) || 0;
                    });

                    row.querySelector('.total-cut-qty-span').textContent = totalCut;
                    row.querySelector('.total-waste-qty-span').textContent = totalWaste;
                }
            });
        });
    </script>
</x-backend.layouts.master> 