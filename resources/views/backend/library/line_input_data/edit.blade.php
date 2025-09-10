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
                                <div class="form-group">
                                    <label for="date">Date</label>
                                    <input type="date" name="date" class="form-control" id="date"
                                        value="{{ old('date', $lineInputDatum->date) }}" required>
                                    @error('date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Product Combination</label>
                                    <input type="text" class="form-control"
                                        value="{{ $lineInputDatum->productCombination->style->name }} - {{ $lineInputDatum->productCombination->color->name }}"
                                        readonly>
                                </div>

                                <div class="form-group">
                                    <label>PO Number</label>
                                    <input type="text" class="form-control" value="{{ $lineInputDatum->po_number }}"
                                        readonly>
                                </div>

                                <div class="form-group">
                                    <label>Input Quantities by Size</label>
                                    <div class="row">
                                        @foreach ($sizeData as $size)
                                            <div class="col-md-4 mb-3">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h5 class="card-title">{{ $size['name'] }}</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="form-group">
                                                            <label for="input_quantities_{{ $size['id'] }}">Input
                                                                Quantity (Max: {{ $size['max_allowed'] }})</label>
                                                            <input type="number"
                                                                name="input_quantities[{{ $size['id'] }}]"
                                                                id="input_quantities_{{ $size['id'] }}"
                                                                class="form-control input-qty"
                                                                value="{{ old('input_quantities.' . $size['id'], $size['input_quantity']) }}"
                                                                min="0" max="{{ $size['max_allowed'] }}"
                                                                data-size-id="{{ $size['id'] }}"
                                                                data-max-allowed="{{ $size['max_allowed'] }}">
                                                            @error('input_quantities.' . $size['id'])
                                                                <div class="text-danger">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="form-group">
                                                            <label
                                                                for="input_waste_quantities_{{ $size['id'] }}">Waste
                                                                Quantity</label>
                                                            <input type="number"
                                                                name="input_waste_quantities[{{ $size['id'] }}]"
                                                                id="input_waste_quantities_{{ $size['id'] }}"
                                                                class="form-control waste-qty"
                                                                value="{{ old('input_waste_quantities.' . $size['id'], $size['waste_quantity']) }}"
                                                                min="0" data-size-id="{{ $size['id'] }}">
                                                            @error('input_waste_quantities.' . $size['id'])
                                                                <div class="text-danger">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="progress mt-2" style="height: 5px;">
                                                            <div class="progress-bar" role="progressbar"
                                                                style="width: 0%;"></div>
                                                        </div>
                                                        <small class="form-text text-muted">
                                                            Order Qty: {{ $size['order_quantity'] }} |
                                                            Available: {{ $size['max_available'] }} |
                                                            Current: {{ $size['input_quantity'] }} |
                                                            Max: {{ $size['max_allowed'] }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Update</button>
                                <a href="{{ route('line_input_data.index') }}" class="btn btn-danger">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners for progress bars
            document.querySelectorAll('.input-qty').forEach(input => {
                input.addEventListener('input', function() {
                    const maxAllowed = parseInt(this.getAttribute('data-max-allowed'));
                    const value = parseInt(this.value) || 0;
                    const percent = maxAllowed > 0 ? Math.min(100, (value / maxAllowed) * 100) : 0;
                    const progressBar = this.nextElementSibling.nextElementSibling.querySelector(
                        '.progress-bar');
                    progressBar.style.width = `${percent}%`;

                    if (value > maxAllowed) {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                    }
                });

                // Trigger the input event to set initial progress
                input.dispatchEvent(new Event('input'));
            });
        });
    </script>

    <style>
        .progress {
            background-color: #e9ecef;
        }

        .progress-bar {
            background-color: #28a745;
            transition: width 0.3s ease;
        }

        input[type="number"]:disabled {
            background-color: #f8f9fa;
        }

        .card {
            margin-bottom: 0;
        }
    </style>
</x-backend.layouts.master>
