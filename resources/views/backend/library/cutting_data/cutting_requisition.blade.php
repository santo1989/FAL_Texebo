{{-- <x-backend.layouts.master>
    <x-slot name="pageTitle">
        Cutting Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Cutting Requisition Report </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('cutting_data.index') }}">Cutting Data</a></li>
            <li class="breadcrumb-item active">Add</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-backend.layouts.elements.errors />
        <div class="row">
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
                    @foreach ($allSizes as $size)
                        <th>
                            {{ $size->name }}
                            <br>
                            <small>Cut Qty</small>
                        </th>
                    @endforeach
                    <th>Total Cut Qty</th>
                </tr>
            </thead>
            <tbody id="cutting-data-body">
                </tbody>
        </table>

        <a href="{{ route('cutting_data.index') }}" class="btn btn-secondary mt-3">Back</a>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                const url = '{{ route('cutting_requisition_find') }}?po_numbers[]=' + poNumbers.join('&po_numbers[]=');
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
                                        const savedCut = (savedInputs[key] && savedInputs[key].cut && savedInputs[key].cut[sizeId]) || 0;
                                        const isSizeAvailable = availableSizeIds.includes(sizeId);

                                        const cell = document.createElement('td');
                                        if (isSizeAvailable && availableQty > 0) {
                                            cell.innerHTML = `
                                                <label>Max = ${availableQty} Pcs </label> <br>
                                                <div class="input-group input-group-sm">
                                                    <input type="number"
                                                        name="rows[${rowIndex}][cut_quantities][${sizeId}]"
                                                        class="form-control cut-qty-input"
                                                        min="0"
                                                        max="${availableQty}"
                                                        value="${savedCut}"
                                                        placeholder="Av: ${availableQty}">
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
                                `;

                                cuttingDataBody.appendChild(row);
                                rowIndex++;
                            });
                        }

                        cuttingDataBody.querySelectorAll('.cut-qty-input').forEach(input => {
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

            cuttingDataBody.addEventListener('input', function(e) {
                const target = e.target;
                const row = target.closest('tr');
                const key = row.dataset.key;

                if (!savedInputs[key]) {
                    savedInputs[key] = { cut: {} };
                }

                if (target.classList.contains('cut-qty-input')) {
                    const name = target.name;
                    const sizeId = name.match(/\[cut_quantities\]\[(\d+)\]/)[1];
                    let value = parseInt(target.value) || 0;
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

                    savedInputs[key].cut[sizeId] = value;

                    let totalCut = 0;
                    row.querySelectorAll('.cut-qty-input').forEach(input => {
                        totalCut += parseInt(input.value) || 0;
                    });

                    row.querySelector('.total-cut-qty-span').textContent = totalCut;
                }
            });
        });
    </script>
</x-backend.layouts.master> --}}

<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Cutting Requisition Report
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Cutting Requisition Report </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('cutting_data.index') }}">Cutting Data</a></li>
            <li class="breadcrumb-item active">Requisition Report</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Assuming x-backend.layouts.elements.errors exists for displaying validation errors --}}
    <x-backend.layouts.elements.errors />

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="po_number">Select PO Number(s)</label>
                {{-- Reverted to standard multiple select --}}
                <select name="po_number[]" id="po_number" class="form-control" multiple size="5"> {{-- size="5" makes it a scrollable list --}}
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

    <div class="card mt-4">
        <div class="card-header">
            <h3 class="card-title">Cutting Requisition Details</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive"> {{-- Make table responsive for smaller screens --}}
                <table class="table table-bordered table-hover text-center">
                    <thead>
                        <tr>
                            <th>PO Number</th>
                            <th>Style</th>
                            <th>Color</th>
                            @foreach ($allSizes as $size)
                                <th>{{ $size->name }} <br><small>Req. Qty</small></th>
                            @endforeach
                            <th>Total Requisition Qty</th>
                            <th>PO Total Max Value</th> {{-- New column for PO Total Max Value --}}
                        </tr>
                    </thead>
                    <tbody id="cutting-data-body">
                        <tr>
                            <td colspan="{{ count($allSizes) + 5 }}">Please select PO Number(s) to view the report.</td>
                        </tr>
                    </tbody>
                    <tfoot id="report-footer" style="display: none;">
                        {{-- Grand Total row will be dynamically inserted here --}}
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <a href="{{ route('cutting_data.index') }}" class="btn btn-secondary mt-3">Back</a>

   
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> {{-- SweetAlert2 is kept for notifications --}}

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const poNumberSelect = document.getElementById('po_number');
            const cuttingDataBody = document.getElementById('cutting-data-body');
            const reportFooter = document.getElementById('report-footer');

            // Function to update the report table
            function updateCuttingDataRows(poNumbers) {
                // Construct URL with array parameters correctly for GET request
                // e.g., ?po_numbers[]=PO123&po_numbers[]=PO456
                const url = '{{ route('cutting_requisition_find') }}?' + poNumbers.map(po => `po_numbers[]=${encodeURIComponent(po)}`).join('&');
                // console.log('Fetching URL:', url); // Debugging

                fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => {
                        // console.log('Response status:', response.status); // Debugging
                        if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
                        return response.json();
                    })
                    .then(fullResponse => {
                        // console.log('Raw response data:', JSON.stringify(fullResponse, null, 2)); // Debugging

                        cuttingDataBody.innerHTML = ''; // Clear existing report rows
                        reportFooter.innerHTML = '';    // Clear existing footer
                        reportFooter.style.display = 'none'; // Hide footer initially

                        const data = fullResponse.data;
                        const poTotalsMaxAllowed = fullResponse.po_totals_max_allowed;

                        if (!data || Object.keys(data).length === 0) {
                            cuttingDataBody.innerHTML = `<tr><td colspan="{{ count($allSizes) + 5 }}">No data found for selected PO numbers.</td></tr>`;
                            return;
                        }

                        let grandTotalMaxAllowed = 0; // Accumulator for total max allowed across ALL selected POs

                        for (const poNumber in data) {
                            if (!Array.isArray(data[poNumber])) {
                                console.error(`Invalid data format for PO ${poNumber}`);
                                continue;
                            }

                            let firstCombinationOfPo = true; // Flag to display PO number and its total only once per PO group
                            let poHasAnyRequisition = false; // Flag to check if this PO has any actual requisition quantities

                            data[poNumber].forEach(combination => {
                                if (!combination.combination_id || !combination.style || !combination.color || !combination.requisition_quantities || !combination.size_ids) {
                                    console.error(`Incomplete combination data for PO ${poNumber}:`, combination);
                                    return;
                                }

                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td class="text-center align-middle">${firstCombinationOfPo ? poNumber : ''}</td>
                                    <td class="text-center align-middle">${combination.style}</td>
                                    <td class="text-center align-middle">${combination.color}</td>
                                `;

                                const relevantSizeIds = combination.size_ids.map(id => String(id)); // Convert to string for consistent comparison

                                @foreach ($allSizes as $size)
                                    {
                                        const sizeName = "{{ $size->name }}";
                                        const sizeId = "{{ $size->id }}"; // Get actual size ID
                                        const requisitionQty = combination.requisition_quantities[sizeName] || 0;
                                        // Check if this size is explicitly associated with the current product combination
                                        const isSizeRelevant = relevantSizeIds.includes(sizeId);

                                        const cell = document.createElement('td');
                                        if (isSizeRelevant) {
                                            cell.textContent = requisitionQty > 0 ? requisitionQty : '-';
                                            if (requisitionQty > 0) {
                                                poHasAnyRequisition = true;
                                            }
                                        } else {
                                            cell.textContent = '-'; // Not applicable for this specific style/color combination
                                        }
                                        row.appendChild(cell);
                                    }
                                @endforeach

                                row.innerHTML += `
                                    <td class="text-center align-middle">${combination.combination_total_requisition_qty || '-'}</td>
                                    <td class="text-center align-middle">${firstCombinationOfPo && poTotalsMaxAllowed[poNumber] !== undefined ? poTotalsMaxAllowed[poNumber] : ''}</td>
                                `;
                                cuttingDataBody.appendChild(row);
                                firstCombinationOfPo = false; // Next combination within this PO won't show PO number/total
                            });

                            // Add this PO's total max allowed to the grand total if it had any relevant requisition data
                            if (poHasAnyRequisition && poTotalsMaxAllowed[poNumber] !== undefined) {
                                grandTotalMaxAllowed += poTotalsMaxAllowed[poNumber];
                            }
                        }

                        // Display a grand total footer if multiple POs or a single PO that actually generated data
                        if (Object.keys(data).length > 0) {
                            reportFooter.style.display = 'table-footer-group'; // Show the footer
                            const footerRow = document.createElement('tr');
                            footerRow.classList.add('table-primary', 'font-weight-bold'); // Highlight total row
                            footerRow.innerHTML = `
                                <td colspan="3" class="text-right">Grand Total Max Allowed (All Selected POs):</td>
                                @foreach ($allSizes as $size)
                                    <td></td> {{-- Empty cells for size columns --}}
                                @endforeach
                                <td></td> {{-- Empty cell for total requisition qty --}}
                                <td class="text-center">${grandTotalMaxAllowed}</td>
                            `;
                            reportFooter.appendChild(footerRow);
                        }

                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        cuttingDataBody.innerHTML = `<tr><td colspan="{{ count($allSizes) + 5 }}">Error loading data. Please try again.</td></tr>`;
                        reportFooter.innerHTML = ''; // Clear any existing footer content
                        reportFooter.style.display = 'none'; // Hide the footer
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load cutting requisition data. Please try again.',
                        });
                    });
            }

            // Load initial data if any PO numbers are pre-selected on page load
            const initialPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
            if (initialPoNumbers && initialPoNumbers.length > 0) {
                updateCuttingDataRows(initialPoNumbers);
            } else {
                cuttingDataBody.innerHTML = `<tr><td colspan="{{ count($allSizes) + 5 }}">Please select PO Number(s) to view the report.</td></tr>`;
            }

            // Listen for changes in the PO number selection (standard HTML select)
            poNumberSelect.addEventListener('change', function() {
                const selectedPoNumbers = Array.from(this.selectedOptions).map(option => option.value);
                if (selectedPoNumbers && selectedPoNumbers.length > 0) {
                    updateCuttingDataRows(selectedPoNumbers);
                } else {
                    cuttingDataBody.innerHTML = `<tr><td colspan="{{ count($allSizes) + 5 }}">Please select PO Number(s) to view the report.</td></tr>`;
                    reportFooter.innerHTML = '';
                    reportFooter.style.display = 'none';
                }
            });
        });
    </script>
</x-backend.layouts.master>