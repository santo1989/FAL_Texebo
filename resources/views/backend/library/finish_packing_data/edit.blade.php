<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Finish Packing Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Edit Finish Packing Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('finish_packing_data.index') }}">Finish Packing</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Edit Finish Packing Data</h3>
                        </div>
                        <form action="{{ route('finish_packing_data.update', $finishPackingDatum->id) }}" method="POST" id="editFinishPackingForm">
                            @csrf
                            @method('PATCH')
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="date">Date</label>
                                    <input type="date" name="date" class="form-control" id="date" value="{{ old('date', $finishPackingDatum->date) }}" required>
                                    @error('date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="product_combination">Product Combination (Style - Color)</label>
                                    <input type="text" class="form-control" id="product_combination"
                                        value="{{ $finishPackingDatum->productCombination->style->name }} - {{ $finishPackingDatum->productCombination->color->name }}" readonly>
                                    <input type="hidden" name="product_combination_id" value="{{ $finishPackingDatum->product_combination_id }}">
                                </div>

                                <div class="form-group">
                                    <label>Packing Quantities by Size</label>
                                    <div class="row">
                                        @foreach($sizeData as $size)
                                            <div class="col-md-3 mb-3">
                                                <label for="quantity_{{ $size['id'] }}">
                                                    {{ $size['name'] }} (Max Available: {{ $size['max_allowed'] + $size['current_quantity'] }})
                                                </label>
                                                <input type="number"
                                                       name="quantities[{{ $size['id'] }}]"
                                                       id="quantity_{{ $size['id'] }}"
                                                       class="form-control @error('quantities.' . $size['id']) is-invalid @enderror"
                                                       value="{{ old('quantities.' . $size['id'], $size['current_quantity']) }}"
                                                       min="0"
                                                       data-available="{{ $size['max_allowed'] + $size['current_quantity'] }}"
                                                       data-size="{{ strtolower($size['name']) }}">
                                                <small class="form-text text-muted">Current: {{ $size['current_quantity'] }}, Max: {{ $size['max_allowed'] + $size['current_quantity'] }}</small>
                                                <div class="progress mt-2" style="height: 5px;">
                                                    <div class="progress-bar" role="progressbar" style="width: 0%;"></div>
                                                </div>
                                                @error('quantities.' . $size['id'])
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                                <div class="text-danger" id="error_quantity_{{ $size['id'] }}"></div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Update</button>
                                <a href="{{ route('finish_packing_data.index') }}" class="btn btn-danger">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('input[type="number"]').forEach(input => {
                const updateProgressBar = () => {
                    const max = parseInt(input.getAttribute('data-available'));
                    const value = parseInt(input.value) || 0;
                    const percent = max > 0 ? Math.min(100, (value / max) * 100) : 0;
                    const progressBar = input.nextElementSibling.nextElementSibling.querySelector('.progress-bar');
                    progressBar.style.width = `${percent}%`;

                    const errorDiv = document.getElementById(`error_quantity_${input.id.split('_')[1]}`);
                    if (value > max) {
                        input.classList.add('is-invalid');
                        errorDiv.textContent = `Quantity for ${input.getAttribute('data-size').toUpperCase()} exceeds available limit (${max})`;
                    } else {
                        input.classList.remove('is-invalid');
                        errorDiv.textContent = '';
                    }
                };
                input.addEventListener('input', updateProgressBar);
                // Initial update for existing values
                updateProgressBar();
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
    </style>
</x-backend.layouts.master>