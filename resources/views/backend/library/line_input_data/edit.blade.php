<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Sewing Input Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Edit Sewing Input Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('line_input_data.index') }}">Sewing Input</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <x-backend.layouts.elements.errors />
    @if (session('message'))
        <div class="alert alert-success">
            <span class="close" data-dismiss="alert">&times;</span>
            <strong>{{ session('message') }}</strong>
        </div>
    @endif

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Edit Sewing Input Data</h3>
                        </div>
                        <form action="{{ route('line_input_data.update', $lineInputDatum->id) }}" method="POST"
                            id="lineInputForm">
                            @csrf
                            @method('patch')
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="date">Date</label>
                                            <input type="date" name="date" id="date" class="form-control"
                                                value="{{ old('date', $lineInputDatum->date) }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="po_number">PO Number</label>
                                            <input type="text" class="form-control"
                                                value="{{ $lineInputDatum->po_number }}" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h5>Product Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="buyer">Buyer</label>
                                                    <input type="text" class="form-control"
                                                        value="{{ $lineInputDatum->productCombination->buyer->name ?? 'N/A' }}"
                                                        readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="style">Style</label>
                                                    <input type="text" class="form-control"
                                                        value="{{ $lineInputDatum->productCombination->style->name ?? 'N/A' }}"
                                                        readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="color">Color</label>
                                                    <input type="text" class="form-control"
                                                        value="{{ $lineInputDatum->productCombination->color->name ?? 'N/A' }}"
                                                        readonly>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <table class="table table-bordered mt-4 text-center">
                                    <thead>
                                        <tr>
                                            <th>Size</th>
                                            <th>Order Quantity</th>
                                            <th>Cutting Quantity</th> {{-- Added Cutting Quantity --}}
                                            <th>Current Input Quantity (This Entry)</th>
                                            <th>Max Available (Excluding Other Entries)</th>
                                            <th>New Input Quantity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sizeData as $size)
                                            <tr>
                                                <td>{{ $size['name'] }}</td>
                                                <td>{{ $size['order_quantity'] }}</td>
                                                <td>{{ $size['cutting_quantity'] }}</td> {{-- Display Cutting Quantity --}}
                                                <td>{{ $size['current_input_quantity'] }}</td>
                                                <td>{{ $size['max_available'] }}</td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number"
                                                               name="input_quantities[{{ $size['id'] }}]"
                                                               class="form-control input-qty-input"
                                                               min="0"
                                                               max="{{ $size['max_available'] }}"
                                                               value="{{ old('input_quantities.'.$size['id'], $size['current_input_quantity']) }}"
                                                               placeholder="Enter quantity">
                                                    </div>
                                                    @error('input_quantities.'.$size['id'])
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="5" class="text-right"><strong>Total Input Quantity:</strong></td> {{-- Adjusted colspan --}}
                                            <td><span id="total-input-qty">{{ $lineInputDatum->total_input_quantity }}</span></td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <a href="{{ route('line_input_data.index') }}" class="btn btn-secondary">Back to List</a>
                                        <button type="submit" class="btn btn-primary">Update Sewing Input Data</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputQtyInputs = document.querySelectorAll('.input-qty-input');
            const totalInputQtySpan = document.getElementById('total-input-qty');

            function updateTotalInputQuantity() {
                let total = 0;
                inputQtyInputs.forEach(input => {
                    const value = parseInt(input.value) || 0;
                    total += value;
                });
                totalInputQtySpan.textContent = total;
            }

            // Add event listeners to all quantity inputs
            inputQtyInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const max = parseInt(this.getAttribute('max'));
                    let value = parseInt(this.value);

                    // Ensure value is not negative
                    if (isNaN(value) || value < 0) {
                        this.value = 0;
                        value = 0;
                    }

                    // Cap at max attribute
                    if (value > max) {
                        this.value = max;
                    }

                    updateTotalInputQuantity();
                });
            });

            // Initialize total on page load
            updateTotalInputQuantity();
        });
    </script>
</x-backend.layouts.master>
