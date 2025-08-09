<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Add Order Data
    </x-slot>

    {{-- <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Add Order Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('order_data.index') }}">Order</a></li>
            <li class="breadcrumb-item active">Add New</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot> --}}

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Add Order Data</h3>
                        </div>
                        <form action="{{ route('order_data.store') }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="form-group col-md-4 mb-3">
                                        <label for="date">Date</label>
                                        <input type="date" name="date" class="form-control" id="date" value="{{ old('date', date('Y-m-d')) }}" required>
                                        @error('date')
                                        <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-group col-md-4 mb-3">
                                        <label for="po_number">PO Number</label>
                                        <input type="text" name="po_number" class="form-control" id="po_number" value="{{ old('po_number') }}" required style="text-transform: uppercase;">
                                        @error('po_number')
                                        <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <!--ground Total Quantity-->
                                    <div class="form-group col-md-4 mb-3">
                                        <label for="total_quantity">Total Quantity</label>
                                        <input type="number" name="total_quantity" class="form-control" id="total_quantity" value="{{ old('total_quantity') }}" required readonly>
                                        @error('total_quantity')
                                        <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <table class="table table-bordered" id="dynamic-table">
                                    <thead>
                                        <tr>
                                            <th>Style</th>
                                            <th>Color</th>
                                            <th>Sizes & Quantity</th>
                                            <th>Total Quantity / Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="product-combination-table-body">
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-right">
                                                <button type="button" class="btn btn-success btn-sm" id="add-row">Add Style & Color</button>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Submit</button>
                                <a href="{{ route('order_data.index') }}" class="btn btn-danger">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        let rowCount = 0;
        
        // This is a placeholder for your styles data. In a real application, you'd pass this from the controller.
        const styles = @json($styles);

        // Function to save form data to localStorage
        function saveFormData() {
            const formData = {
                date: $('#date').val(),
                po_number: $('#po_number').val(),
                combinations: []
            };

            $('#product-combination-table-body tr').each(function() {
                const rowId = $(this).attr('id').split('-')[1];
                const combination = {
                    style_id: $(`#row-${rowId} .style-select`).val(),
                    color_id: $(`#row-${rowId} .color-select`).val(),
                    product_combination_id: $(`#row-${rowId} input[name="combinations[${rowId}][product_combination_id]"]`).val(),
                    quantities: {}
                };

                $(this).find('.size-input').each(function() {
                    const sizeId = $(this).attr('name').match(/\[(\d+)\]$/)[1];
                    combination.quantities[sizeId] = $(this).val();
                });

                formData.combinations.push(combination);
            });
            localStorage.setItem('orderFormData', JSON.stringify(formData));
        }

        // Function to load form data from localStorage
        function loadFormData() {
            const savedData = localStorage.getItem('orderFormData');
            if (savedData) {
                const formData = JSON.parse(savedData);
                $('#date').val(formData.date);
                $('#po_number').val(formData.po_number);
                $('#product-combination-table-body').empty();

                if (formData.combinations.length > 0) {
                    rowCount = 0; // Reset row count to avoid ID conflicts
                    formData.combinations.forEach(function(combination) {
                        const newRowId = ++rowCount;
                        addRow(newRowId, combination);
                    });
                } else {
                    addRow();
                }
                
                // Update the total quantity for the main form
                updateGroundTotalQuantity();
            } else {
                addRow();
            }
        }

        function addRow(id = null, data = null) {
            const rowId = id || ++rowCount;
            const newRow = `
                <tr id="row-${rowId}">
                    <td>
                        <select name="combinations[${rowId}][style_id]" class="form-control style-select" data-row-id="${rowId}" required>
                            <option value="">Select Style</option>
                            ${styles.map(style => `<option value="${style.id}">${style.name}</option>`).join('')}
                        </select>
                    </td>
                    <td>
                        <select name="combinations[${rowId}][color_id]" class="form-control color-select" data-row-id="${rowId}" required disabled>
                            <option value="">Select Color</option>
                        </select>
                    </td>
                    <td class="size-inputs-cell">
                    </td>
                    <td>
                        <input type="number" name="combinations[${rowId}][total_quantity]" class="form-control total-quantity-input" id="total-quantity-${rowId}" value="0" min="0" readonly>
                        <input type="hidden" name="combinations[${rowId}][product_combination_id]" class="product-combination-id-input">
                        <button type="button" class="btn btn-danger pt-2 btn-sm remove-row" data-row-id="${rowId}">Remove</button>
                    </td>
                </tr>
            `;
            $('#product-combination-table-body').append(newRow);

            if (data) {
                const $row = $(`#row-${rowId}`);
                $row.find('.style-select').val(data.style_id).trigger('change', [data]);
            }
            saveFormData();
        }

        // Function to calculate and update the total quantity for a single row
        function updateTotalQuantity(rowId) {
            let total = 0;
            $(`#row-${rowId} .size-input`).each(function() {
                const quantity = parseInt($(this).val()) || 0;
                total += quantity;
            });
            $(`#total-quantity-${rowId}`).val(total);
            updateGroundTotalQuantity(); // Also update the grand total
        }
        
        // Function to calculate and update the grand total quantity for the whole form
        function updateGroundTotalQuantity() {
            let grandTotal = 0;
            $('.total-quantity-input').each(function() {
                grandTotal += parseInt($(this).val()) || 0;
            });
            $('#total_quantity').val(grandTotal);
        }

        // Load data on page load
        loadFormData();

        // Handle adding new rows
        $('#add-row').on('click', function() {
            addRow();
        });

        // Handle removing rows
        $('#product-combination-table-body').on('click', '.remove-row', function() {
            const rowId = $(this).data('row-id');
            $(`#row-${rowId}`).remove();
            updateGroundTotalQuantity();
            saveFormData();
        });

        // Handle style selection change
        $('#product-combination-table-body').on('change', '.style-select', function(event, savedData) {
            const styleId = $(this).val();
            const rowId = $(this).data('row-id');
            const $row = $(this).closest('tr');
            const $colorSelect = $row.find('.color-select');
            const $sizeInputsCell = $row.find('.size-inputs-cell');

            $colorSelect.prop('disabled', true).empty().append('<option value="">Loading...</option>');
            $sizeInputsCell.empty();
            $row.find('.total-quantity-input').val(0);
            updateGroundTotalQuantity();

            if (styleId) {
                $.ajax({
                    url: `/get-colors-by-style/${styleId}`,
                    method: 'GET',
                    success: function(data) {
                        $colorSelect.empty().append('<option value="">Select Color</option>');
                        $.each(data, function(index, color) {
                            $colorSelect.append(`<option value="${color.id}">${color.name}</option>`);
                        });
                        $colorSelect.prop('disabled', false);
                        if (savedData && savedData.color_id) {
                            $colorSelect.val(savedData.color_id).trigger('change', [savedData]);
                        }
                    }
                });
            } else {
                $colorSelect.empty().append('<option value="">Select Color</option>');
            }
            saveFormData();
        });

        // Handle color selection change
        $('#product-combination-table-body').on('change', '.color-select', function(event, savedData) {
            const $row = $(this).closest('tr');
            const styleId = $row.find('.style-select').val();
            const colorId = $(this).val();
            const rowId = $row.find('.style-select').data('row-id');
            const $sizeInputsCell = $row.find('.size-inputs-cell');
            const $combinationIdInput = $row.find('.product-combination-id-input');

            $sizeInputsCell.empty();
            $row.find('.total-quantity-input').val(0);
            updateGroundTotalQuantity();

            if (styleId && colorId) {
                $.ajax({
                    url: `/get-combination-sizes/${styleId}/${colorId}`,
                    method: 'GET',
                    success: function(data) {
                        if (data.success) {
                            const sizes = data.sizes;
                            const combinationId = data.combination_id;
                            
                            $combinationIdInput.val(combinationId);

                            let sizeInputsHtml = '';
                            $.each(sizes, function(index, size) {
                                const quantity = savedData && savedData.quantities[size.id] ? savedData.quantities[size.id] : '0';
                                sizeInputsHtml += `
                                    <div class="row align-items-center mb-1">
                                        <div class="col-md-6">
                                            <label class="mb-0">${size.name}</label>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="number" name="combinations[${rowId}][quantities][${size.id}]" class="form-control size-input" value="${quantity}" min="0" placeholder="Quantity">
                                        </div>
                                    </div>
                                `;
                            });
                            $sizeInputsCell.html(sizeInputsHtml);
                            // Update total quantity after populating inputs
                            updateTotalQuantity(rowId);
                        } else {
                            $sizeInputsCell.html('<div class="alert alert-warning">No product combination found.</div>');
                        }
                    },
                    error: function(xhr) {
                        console.error('Error fetching combination sizes:', xhr.responseText);
                        $sizeInputsCell.html('<div class="alert alert-danger">Error fetching data.</div>');
                    }
                });
            }
            saveFormData();
        });

        // Handle quantity input change to update total quantity and save to local storage
        $('#product-combination-table-body').on('input', '.size-input', function() {
            const rowId = $(this).closest('tr').find('.style-select').data('row-id');
            updateTotalQuantity(rowId);
            saveFormData();
        });
        
        // Save form data on any change in main form inputs
        $('#date, #po_number').on('input', function() {
            saveFormData();
        });

        // Clear local storage on form submission
        $('form').on('submit', function() {
            localStorage.removeItem('orderFormData');
        });

        // No confirmation needed for cancel
        $('a.btn-danger').on('click', function(e) {
            localStorage.removeItem('orderFormData');
            // The browser will handle the navigation after this.
        });
    });
</script>
</x-backend.layouts.master>