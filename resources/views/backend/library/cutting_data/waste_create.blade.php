<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Add Waste Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Waste Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('cutting_data.index') }}">Cutting Data</a></li>
            <li class="breadcrumb-item active">Add Waste</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-backend.layouts.elements.errors />
    <form action="{{ route('cutting_data.store_waste') }}" method="post">
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
                            <small>Waste Qty</small>
                        </th>
                    @endforeach
                    <th>Total Waste Qty</th>
                </tr>
            </thead>
            <tbody id="waste-data-body">
                </tbody>
        </table>

        <a href="{{ route('cutting_data.index') }}" class="btn btn-secondary mt-3">Back</a>
        <button type="submit" class="btn btn-primary mt-3">Save Waste Data</button>
    </form>

    <script>
       
        document.addEventListener('DOMContentLoaded', function() {
            const poNumberSelect = document.getElementById('po_number');
            const wasteDataBody = document.getElementById('waste-data-body');
            let savedInputs = {};

            const initialPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
            if (initialPoNumbers.length > 0) {
                updateWasteDataRows(initialPoNumbers);
            }

            poNumberSelect.addEventListener('change', function() {
                const selectedPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
                if (selectedPoNumbers.length > 0) {
                    updateWasteDataRows(selectedPoNumbers);
                } else {
                    wasteDataBody.innerHTML = '';
                    savedInputs = {};
                }
            });

            function updateWasteDataRows(poNumbers) {
                const url = '{{ route('cutting_data.find_waste') }}?po_numbers[]=' + poNumbers.join('&po_numbers[]=');
                console.log('Fetching URL:', url);

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
                        console.log('Raw response data:', JSON.stringify(data, null, 2));
                        wasteDataBody.innerHTML = '';
                        let rowIndex = 0;

                        if (!data || Object.keys(data).length === 0) {
                            wasteDataBody.innerHTML = '<tr><td colspan="100%">No data found for selected PO numbers.</td></tr>';
                            return;
                        }

                        for (const poNumber in data) {
                            if (!Array.isArray(data[poNumber])) continue;
                            data[poNumber].forEach(combination => {
                                if (!combination.combination_id || !combination.style || !combination.color || !combination.size_ids) return;

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
                                        const isSizeAvailable = availableSizeIds.includes(sizeId);
                                        const savedWaste = (savedInputs[key] && savedInputs[key].waste && savedInputs[key].waste[sizeId]) || 0;

                                        const cell = document.createElement('td');
                                        if (isSizeAvailable) {
                                            cell.innerHTML = `
                                                <div class="input-group input-group-sm">
                                                    <input type="number"
                                                        name="rows[${rowIndex}][waste_quantities][${sizeId}]"
                                                        class="form-control waste-qty-input"
                                                        min="0"
                                                        value="${savedWaste}"
                                                        placeholder="Enter waste">
                                                </div>
                                            `;
                                        } else {
                                            cell.innerHTML = `<span class="text-muted text-center">N/A</span>`;
                                        }
                                        row.appendChild(cell);
                                    }
                                @endforeach

                                row.innerHTML += `
                                    <td><span class="total-waste-qty-span">0</span></td>
                                `;

                                wasteDataBody.appendChild(row);
                                rowIndex++;
                            });
                        }

                        wasteDataBody.querySelectorAll('.waste-qty-input').forEach(input => {
                            input.dispatchEvent(new Event('input'));
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        wasteDataBody.innerHTML = '<tr><td colspan="100%">Error loading data. Please try again.</td></tr>';
                    });
            }

            wasteDataBody.addEventListener('input', function(e) {
                const target = e.target;
                const row = target.closest('tr');
                const key = row.dataset.key;

                if (target.classList.contains('waste-qty-input')) {
                    if (!savedInputs[key]) {
                        savedInputs[key] = { waste: {} };
                    }

                    const name = target.name;
                    const sizeId = name.match(/\[waste_quantities\]\[(\d+)\]/)[1];
                    let value = parseInt(target.value) || 0;
                    savedInputs[key].waste[sizeId] = value;

                    let totalWaste = 0;
                    row.querySelectorAll('.waste-qty-input').forEach(input => {
                        totalWaste += parseInt(input.value) || 0;
                    });

                    row.querySelector('.total-waste-qty-span').textContent = totalWaste;
                }
            });
        });
    </script>
</x-backend.layouts.master>