<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Output Finishing Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Edit Output Finishing Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('output_finishing_data.index') }}">Output Finishing</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Edit Output Finishing Data</h3>
                        </div>
                        <form action="{{ route('output_finishing_data.update', $outputFinishingDatum->id) }}" method="POST" id="editOutputFinishingForm">
                            @csrf
                            @method('PATCH')
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="date">Date</label>
                                    <input type="date" name="date" class="form-control" id="date" value="{{ old('date', $outputFinishingDatum->date) }}" required>
                                    @error('date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="product_combination">Product Combination (Style - Color)</label>
                                    <input type="text" class="form-control" id="product_combination"
                                        value="{{ $outputFinishingDatum->productCombination->style->name }} - {{ $outputFinishingDatum->productCombination->color->name }}" readonly>
                                    <input type="hidden" name="product_combination_id" value="{{ $outputFinishingDatum->product_combination_id }}">
                                </div>

                                <div class="form-group">
                                    <label>PO Number</label>
                                    <input type="text" class="form-control" value="{{ $outputFinishingDatum->po_number }}" readonly>
                                </div>

                                <div class="form-group">
                                    <label>Output Quantities by Size</label>
                                    <div class="row">
                                        @foreach($sizeData as $size)
                                            <div class="col-md-3 mb-3">
                                                <label for="output_quantity_{{ $size['id'] }}">
                                                    {{ $size['name'] }} (Max Available: {{ $size['max_allowed'] }}, Order Qty: {{ $size['order_quantity'] }})
                                                </label>
                                                <input type="number"
                                                       name="output_quantities[{{ $size['id'] }}]"
                                                       id="output_quantity_{{ $size['id'] }}"
                                                       class="form-control"
                                                       value="{{ old('output_quantities.' . $size['id'], $size['output_quantity']) }}"
                                                       min="0"
                                                       max="{{ $size['max_allowed'] }}"
                                                       data-max="{{ $size['max_allowed'] }}"
                                                       data-size="{{ strtolower($size['name']) }}">
                                                <small class="form-text text-muted">Current: {{ $size['output_quantity'] }}, Max: {{ $size['max_allowed'] }}</small>
                                                <div class="text-danger" id="error_output_quantity_{{ $size['id'] }}"></div>
                                                
                                                <label for="waste_quantity_{{ $size['id'] }}" class="mt-2">Waste Quantity</label>
                                                <input type="number"
                                                       name="output_waste_quantities[{{ $size['id'] }}]"
                                                       id="waste_quantity_{{ $size['id'] }}"
                                                       class="form-control"
                                                       value="{{ old('output_waste_quantities.' . $size['id'], $size['waste_quantity']) }}"
                                                       min="0"
                                                       max="{{ $size['max_allowed'] }}">
                                                <small class="form-text text-muted">Current Waste: {{ $size['waste_quantity'] }}</small>
                                                <div class="text-danger" id="error_waste_quantity_{{ $size['id'] }}"></div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Update</button>
                                <a href="{{ route('output_finishing_data.index') }}" class="btn btn-danger">Cancel</a>
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
                const updateValidation = () => {
                    if (input.name.includes('output_quantities')) {
                        const max = parseInt(input.getAttribute('data-max'));
                        const value = parseInt(input.value) || 0;
                        const errorDiv = document.getElementById(`error_output_quantity_${input.id.split('_')[2]}`);
                        
                        if (value > max) {
                            input.classList.add('is-invalid');
                            errorDiv.textContent = `Quantity for ${input.getAttribute('data-size').toUpperCase()} exceeds available limit (${max})`;
                        } else {
                            input.classList.remove('is-invalid');
                            errorDiv.textContent = '';
                        }
                    }
                };
                
                input.addEventListener('input', updateValidation);
                updateValidation(); // Initial validation
            });
        });
    </script>
</x-backend.layouts.master>

{{-- <x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Output Finishing Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Edit Output Finishing Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('output_finishing_data.index') }}">Output Finishing</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Edit Output Finishing Data</h3>
                        </div>
                        <form action="{{ route('output_finishing_data.update', $outputFinishingDatum->id) }}" method="POST" id="editOutputFinishingForm">
                            @csrf
                            @method('PATCH')
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="date">Date</label>
                                    <input type="date" name="date" class="form-control" id="date" value="{{ old('date', $outputFinishingDatum->date) }}" required>
                                    @error('date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="product_combination">Product Combination (Style - Color)</label>
                                    <input type="text" class="form-control" id="product_combination"
                                        value="{{ $outputFinishingDatum->productCombination->style->name }} - {{ $outputFinishingDatum->productCombination->color->name }}" readonly>
                                    <input type="hidden" name="product_combination_id" value="{{ $outputFinishingDatum->product_combination_id }}">
                                </div>

                                <div class="form-group">
                                    <label>Output Quantities by Size</label>
                                    <div class="row">
                                        @foreach($sizeData as $size)
                                            <div class="col-md-3 mb-3">
                                                <label for="quantity_{{ $size['id'] }}">
                                                    {{ $size['name'] }} (Max Available: {{ $size['max_allowed'] }})
                                                </label>
                                                <input type="number"
                                                       name="quantities[{{ $size['id'] }}]"
                                                       id="quantity_{{ $size['id'] }}"
                                                       class="form-control"
                                                       value="{{ old('quantities.' . $size['id'], $size['current_quantity']) }}"
                                                       min="0"
                                                       max="{{ $size['max_allowed'] }}"
                                                       data-max="{{ $size['max_allowed'] }}"
                                                       data-size="{{ strtolower($size['name']) }}">
                                                <small class="form-text text-muted">Current: {{ $size['current_quantity'] }}, Max: {{ $size['max_allowed'] }}</small>
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
                                <a href="{{ route('output_finishing_data.index') }}" class="btn btn-danger">Cancel</a>
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
                    const max = parseInt(input.getAttribute('data-max'));
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
                updateProgressBar(); // Initial update
            });
        });
    </script>
</x-backend.layouts.master> --}}