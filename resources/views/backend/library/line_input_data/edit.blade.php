<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Line Input Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Edit Line Input Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('line_input_data.index') }}">Line Input</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Edit Line Input Data</h3>
                        </div>
                        <form action="{{ route('line_input_data.update', $lineInputDatum->id) }}" method="POST">
                            @csrf
                            @method('patch')
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="date">Date</label>
                                    <input type="date" name="date" class="form-control" id="date" value="{{ old('date', $lineInputDatum->date) }}" required>
                                    @error('date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Product Combination</label>
                                    <input type="text" class="form-control" value="{{ $lineInputDatum->productCombination->style->name }} - {{ $lineInputDatum->productCombination->color->name }}" readonly>
                                </div>

                                <div class="form-group">
                                    <label>Input Quantities by Size</label>
                                    <div class="row">
                                        @foreach ($sizeData as $size)
                                            <div class="col-md-3 mb-3">
                                                <label for="quantity_{{ $size['id'] }}">{{ $size['name'] }} (Max: {{ $size['max_allowed'] }})</label>
                                                <input type="number" name="quantities[{{ $size['id'] }}]" id="quantity_{{ $size['id'] }}"
                                                    class="form-control" value="{{ old('quantities.' . $size['id'], $size['current_quantity']) }}" min="0" max="{{ $size['max_allowed'] + $size['current_quantity'] }}">
                                                @error('quantities.' . $size['id'])
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
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
</x-backend.layouts.master>