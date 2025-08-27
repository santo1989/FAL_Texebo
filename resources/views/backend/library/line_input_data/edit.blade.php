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
                                    <label>PO Number</label>
                                    <input type="text" class="form-control" value="{{ $lineInputDatum->po_number }}" readonly>
                                </div>

                                <div class="form-group">
                                    <label>Input Quantities by Size</label>
                                    <div class="row">
                                        @foreach ($allSizes as $size)
                                            @php
                                                $inputQty = $lineInputDatum->input_quantities[$size->id] ?? 0;
                                                $wasteQty = $lineInputDatum->input_waste_quantities[$size->id] ?? 0;
                                            @endphp
                                            <div class="col-md-3 mb-3">
                                                <label for="input_quantities_{{ $size->id }}">{{ $size->name }} Input</label>
                                                <input type="number" name="input_quantities[{{ $size->id }}]" id="input_quantities_{{ $size->id }}"
                                                    class="form-control" value="{{ old('input_quantities.' . $size->id, $inputQty) }}" min="0">
                                                @error('input_quantities.' . $size->id)
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="input_waste_quantities_{{ $size->id }}">{{ $size->name }} Waste</label>
                                                <input type="number" name="input_waste_quantities[{{ $size->id }}]" id="input_waste_quantities_{{ $size->id }}"
                                                    class="form-control" value="{{ old('input_waste_quantities.' . $size->id, $wasteQty) }}" min="0">
                                                @error('input_waste_quantities.' . $size->id)
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