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
                    <select name="po_number[]" id="po_number" class="form-control select2" multiple="multiple"
                        style="width: 100%;">
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

        <table class="table table-bordered mt-4">
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
                {{-- Dynamic rows will be injected here by JavaScript --}}
            </tbody>
        </table>

        <a href="{{ route('cutting_data.index') }}" class="btn btn-secondary mt-3">Back</a>
        <button type="submit" class="btn btn-primary mt-3">Save Cutting Data</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2
            $('#po_number').select2({
                placeholder: 'Select PO Number(s)',
                allowClear: true
            });

            const poNumberSelect = document.getElementById('po_number');
            const cuttingDataBody = document.getElementById('cutting-data-body');

            poNumberSelect.addEventListener('change', function() {
                const selectedPoNumbers = Array.from(poNumberSelect.selectedOptions).map(option => option.value);
                if (selectedPoNumbers.length > 0) {
                    updateCuttingDataRows(selectedPoNumbers);
                } else {
                    cuttingDataBody.innerHTML = '';
                }
            });

            function updateCuttingDataRows(poNumbers) {
    const url = '{{ route('cutting_data.find') }}?po_numbers[]=' + poNumbers.join('&po_numbers[]=');

    fetch(url)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            cuttingDataBody.innerHTML = '';
            let rowIndex = 0;

            for (const poNumber in data) {
                data[poNumber].forEach(combination => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>
                            <input type="hidden" name="rows[${rowIndex}][po_number]" value="${poNumber}">
                            <input type="hidden" name="rows[${rowIndex}][product_combination_id]" value="${combination.combination_id}">
                            ${poNumber}
                        </td>
                        <td>${combination.style}</td>
                        <td>${combination.color}</td>
                    `;

                    // Dynamically create a cell for each size
                    @foreach ($allSizes as $size)
                        {
                            const sizeId = "{{ $size->id }}";
                            const sizeNameLower = "{{ strtolower($size->name) }}";
                            // The availableQty will be a property of the combination.available_quantities object
                            const availableQty = combination.available_quantities[sizeNameLower] || 0;
                            
                            const cell = document.createElement('td');
                            cell.innerHTML = `
                                <div class="input-group">
                                    <input type="number" 
                                        name="rows[${rowIndex}][cut_quantities][${sizeId}]" 
                                        class="form-control form-control-sm cut-qty-input"
                                        min="0" 
                                        max="${availableQty}"
                                        placeholder="Av: ${availableQty}">
                                    <input type="number" 
                                        name="rows[${rowIndex}][waste_quantities][${sizeId}]" 
                                        class="form-control form-control-sm waste-qty-input"
                                        min="0" 
                                        placeholder="W: 0">
                                </div>
                            `;
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
        })
        .catch(error => {
            console.error('Error fetching data:', error);
            cuttingDataBody.innerHTML = '<tr><td colspan="100%">Error loading data. Please try again.</td></tr>';
        });
}
            // Listen for input changes to recalculate totals
            cuttingDataBody.addEventListener('input', function(e) {
                const target = e.target;
                if (target.classList.contains('cut-qty-input') || target.classList.contains('waste-qty-input')) {
                    const row = target.closest('tr');
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