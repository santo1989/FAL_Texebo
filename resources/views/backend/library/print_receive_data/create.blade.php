<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Add Print/Embroidery Receive Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Print/Embroidery Receive Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('print_receive_data.index') }}">Print/Embroidery Receive
                    Data</a></li>
            <li class="breadcrumb-item active">Add</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-backend.layouts.elements.errors />
    <form action="{{ route('print_receive_data.store') }}" method="post">
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
            <tbody id="print-receive-data-body">
                <!-- Dynamic rows will be injected here by JavaScript -->
            </tbody>
        </table>

        <a href="{{ route('print_receive_data.index') }}" class="btn btn-secondary mt-3">Back</a>
        <button type="submit" class="btn btn-primary mt-3">Save Print/Embroidery Receive Data</button>
    </form>

    <script>
        // document.addEventListener('DOMContentLoaded', function() {
        //     const poNumberSelect = document.getElementById('po_number');
        //     const printReceiveDataBody = document.getElementById('print-receive-data-body');
        //     let savedInputs = {};
        //     let processedCombinations = new Set();

        //     const initialPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
        //     if (initialPoNumbers.length > 0) {
        //         updatePrintReceiveDataRows(initialPoNumbers);
        //     }

        //     poNumberSelect.addEventListener('change', function() {
        //         const selectedPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
        //         processedCombinations.clear();
        //         if (selectedPoNumbers.length > 0) {
        //             updatePrintReceiveDataRows(selectedPoNumbers);
        //         } else {
        //             printReceiveDataBody.innerHTML = '';
        //             savedInputs = {};
        //         }
        //     });

        //     function updatePrintReceiveDataRows(poNumbers) {
        //         const url = '{{ route('print_receive_data.find') }}?po_numbers[]=' + poNumbers.join('&po_numbers[]=');

        //         fetch(url, {
        //             headers: {
        //                 'Accept': 'application/json',
        //                 'X-Requested-With': 'XMLHttpRequest',
        //                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        //             }
        //         })
        //         .then(response => {
        //             if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
        //             return response.json();
        //         })
        //         .then(data => {
        //             printReceiveDataBody.innerHTML = '';
        //             let rowIndex = 0;
        //             processedCombinations.clear();

        //             if (!data || Object.keys(data).length === 0) {
        //                 printReceiveDataBody.innerHTML = '<tr><td colspan="100%">No data found for selected PO numbers.</td></tr>';
        //                 return;
        //             }

        //             for (const poNumber in data) {
        //                 if (!Array.isArray(data[poNumber])) continue;

        //                 data[poNumber].forEach(combination => {
        //                     if (!combination.combination_id || !combination.style || !combination.color || !combination.available_quantities || !combination.size_ids) {
        //                         return;
        //                     }

        //                     const combinationKey = `${combination.combination_id}-${combination.style}-${combination.color}`;

        //                     if (processedCombinations.has(combinationKey)) {
        //                         return;
        //                     }

        //                     processedCombinations.add(combinationKey);

        //                     const row = document.createElement('tr');
        //                     const key = `${poNumber}-${combination.combination_id}`;
        //                     row.dataset.key = key;
        //                     row.innerHTML = `
    //                         <td class="text-center">
    //                             <input type="hidden" name="rows[${rowIndex}][product_combination_id]" value="${combination.combination_id}">
    //                             ${poNumber}
    //                         </td>
    //                         <td class="text-center">${combination.style}</td>
    //                         <td class="text-center">${combination.color}</td>
    //                     `;

        //                     const availableSizeIds = combination.size_ids.map(id => String(id));

        //                     @foreach ($allSizes as $size)
        //                         {
        //                             const sizeId = "{{ $size->id }}";
        //                             const sizeName = "{{ $size->name }}";
        //                             const availableQty = combination.available_quantities[sizeId] || 0;
        //                             const savedReceive = (savedInputs[key] && savedInputs[key].receive && savedInputs[key].receive[sizeId]) || 0;
        //                             const savedWaste = (savedInputs[key] && savedInputs[key].waste && savedInputs[key].waste[sizeId]) || 0;
        //                             const isSizeAvailable = availableSizeIds.includes(sizeId);

        //                             const cell = document.createElement('td');
        //                             if (isSizeAvailable && availableQty > 0) {
        //                                 cell.innerHTML = ` <label>Max = ${availableQty} Pcs </label> <br>
    //                                     <div class="input-group input-group-sm">
    //                                         <input type="number" 
    //                                                name="rows[${rowIndex}][receive_quantities][${sizeId}]" 
    //                                                class="form-control receive-qty-input"
    //                                                min="0" 
    //                                                max="${availableQty}"
    //                                                value="${savedReceive}"
    //                                                placeholder="Av: ${availableQty}">
    //                                         <input type="number" 
    //                                                name="rows[${rowIndex}][receive_waste_quantities][${sizeId}]" 
    //                                                class="form-control waste-qty-input"
    //                                                min="0" 
    //                                                value="${savedWaste}"
    //                                                max="${availableQty}"
    //                                                placeholder="W: 0">
    //                                     </div>
    //                                 `;
        //                             } else {
        //                                 cell.innerHTML = `<span class="text-muted text-center">N/A</span>`;
        //                             }
        //                             row.appendChild(cell);
        //                         }
        //                     @endforeach

        //                     row.innerHTML += `
    //                         <td><span class="total-receive-qty-span">0</span></td>
    //                         <td><span class="total-waste-qty-span">0</span></td>
    //                     `;

        //                     printReceiveDataBody.appendChild(row);
        //                     rowIndex++;
        //                 });
        //             }

        //             printReceiveDataBody.querySelectorAll('.receive-qty-input, .waste-qty-input').forEach(input => {
        //                 input.dispatchEvent(new Event('input'));
        //             });
        //         })
        //         .catch(error => {
        //             console.error('Error fetching data:', error);
        //             printReceiveDataBody.innerHTML = '<tr><td colspan="100%">Error loading data. Please try again.</td></tr>';
        //         });
        //     }

        //     printReceiveDataBody.addEventListener('input', function(e) {
        //         const target = e.target;
        //         const row = target.closest('tr');
        //         const key = row.dataset.key;

        //         if (!savedInputs[key]) {
        //             savedInputs[key] = { receive: {}, waste: {} };
        //         }

        //         let isReceive = target.classList.contains('receive-qty-input');
        //         let isWaste = target.classList.contains('waste-qty-input');

        //         if (isReceive || isWaste) {
        //             const name = target.name;
        //             const sizeId = name.match(/\[(receive_quantities|receive_waste_quantities)\]\[(\d+)\]/)[2];
        //             let value = parseInt(target.value) || 0;

        //             if (isReceive) {
        //                 const max = parseInt(target.getAttribute('max')) || 0;
        //                 if (value > max) {
        //                     value = max;
        //                     target.value = max;
        //                 }
        //             }

        //             if (isReceive) {
        //                 savedInputs[key].receive[sizeId] = value;
        //             } else if (isWaste) {
        //                 savedInputs[key].waste[sizeId] = value;
        //             }

        //             let totalReceive = 0;
        //             let totalWaste = 0;

        //             row.querySelectorAll('.receive-qty-input').forEach(input => {
        //                 totalReceive += parseInt(input.value) || 0;
        //             });

        //             row.querySelectorAll('.waste-qty-input').forEach(input => {
        //                 totalWaste += parseInt(input.value) || 0;
        //             });

        //             row.querySelector('.total-receive-qty-span').textContent = totalReceive;
        //             row.querySelector('.total-waste-qty-span').textContent = totalWaste;
        //         }
        //     });
        // });

        document.addEventListener('DOMContentLoaded', function() {
            const poNumberSelect = document.getElementById('po_number');
            const printReceiveDataBody = document.getElementById('print-receive-data-body');
            let savedInputs = {};
            let rowIndex = 0; // Move rowIndex outside to maintain consistent indexing

            const initialPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
            if (initialPoNumbers.length > 0) {
                updatePrintReceiveDataRows(initialPoNumbers);
            }

            poNumberSelect.addEventListener('change', function() {
                const selectedPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option
                    .value);
                rowIndex = 0; // Reset row index when PO selection changes
                if (selectedPoNumbers.length > 0) {
                    updatePrintReceiveDataRows(selectedPoNumbers);
                } else {
                    printReceiveDataBody.innerHTML = '';
                    savedInputs = {};
                }
            });

            function updatePrintReceiveDataRows(poNumbers) {
                const url = '{{ route('print_receive_data.find') }}?po_numbers[]=' + poNumbers.join(
                    '&po_numbers[]=');

                fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok: ' + response
                        .statusText);
                        return response.json();
                    })
                    .then(data => {
                        printReceiveDataBody.innerHTML = '';
                        rowIndex = 0; // Reset row index for new data
                        savedInputs = {}; // Clear saved inputs

                        if (!data || Object.keys(data).length === 0) {
                            printReceiveDataBody.innerHTML =
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

                                const availableSizeIds = combination.size_ids.map(id => String(id));

                                @foreach ($allSizes as $size)
                                    {
                                        const sizeId = "{{ $size->id }}";
                                        const availableQty = combination.available_quantities[sizeId] ||
                                            0;
                                        const savedReceive = (savedInputs[key] && savedInputs[key]
                                            .receive && savedInputs[key].receive[sizeId]) || 0;
                                        const savedWaste = (savedInputs[key] && savedInputs[key]
                                            .waste && savedInputs[key].waste[sizeId]) || 0;
                                        const isSizeAvailable = availableSizeIds.includes(sizeId);

                                        const cell = document.createElement('td');
                                        if (isSizeAvailable && availableQty > 0) {
                                            cell.innerHTML = ` <label>Max = ${availableQty} Pcs </label> <br>
                                    <div class="input-group input-group-sm">
                                        <input type="number" 
                                               name="rows[${rowIndex}][receive_quantities][${sizeId}]" 
                                               class="form-control receive-qty-input"
                                               min="0" 
                                               max="${availableQty}"
                                               value="${savedReceive}"
                                               placeholder="Av: ${availableQty}">
                                        <input type="number" 
                                               name="rows[${rowIndex}][receive_waste_quantities][${sizeId}]" 
                                               class="form-control waste-qty-input"
                                               min="0" 
                                               value="${savedWaste}"
                                               max="${availableQty}"
                                               placeholder="W: 0">
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
                        <td><span class="total-receive-qty-span">0</span></td>
                        <td><span class="total-waste-qty-span">0</span></td>
                    `;

                                printReceiveDataBody.appendChild(row);

                                // Initialize saved inputs for this row
                                if (!savedInputs[key]) {
                                    savedInputs[key] = {
                                        receive: {},
                                        waste: {}
                                    };
                                }

                                rowIndex++;
                            });
                        }

                        // Update totals for all rows
                        printReceiveDataBody.querySelectorAll('.receive-qty-input, .waste-qty-input').forEach(
                            input => {
                                input.dispatchEvent(new Event('input'));
                            });
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        printReceiveDataBody.innerHTML =
                            '<tr><td colspan="100%">Error loading data. Please try again.</td></tr>';
                    });
            }

            // Event delegation for dynamic inputs
            printReceiveDataBody.addEventListener('input', function(e) {
                const target = e.target;
                if (target.classList.contains('receive-qty-input') || target.classList.contains(
                        'waste-qty-input')) {
                    const row = target.closest('tr');
                    const key = row.dataset.key;

                    if (!savedInputs[key]) {
                        savedInputs[key] = {
                            receive: {},
                            waste: {}
                        };
                    }

                    const name = target.name;
                    const sizeIdMatch = name.match(
                        /\[(receive_quantities|receive_waste_quantities)\]\[(\d+)\]/);
                    if (sizeIdMatch) {
                        const type = sizeIdMatch[1];
                        const sizeId = sizeIdMatch[2];
                        const value = parseInt(target.value) || 0;

                        if (type === 'receive_quantities') {
                            savedInputs[key].receive[sizeId] = value;
                        } else {
                            savedInputs[key].waste[sizeId] = value;
                        }

                        updateRowTotals(row);
                    }
                }
            });

            function updateRowTotals(row) {
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
    </script>
</x-backend.layouts.master>
