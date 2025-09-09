<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Print/Embroidery Receive Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Edit Print/Embroidery Receive Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('print_receive_data.index') }}">Print/Emb Receive</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Edit Print/Embroidery Receive Data</h3>
                        </div>
                        <form action="{{ route('print_receive_data.update', $printReceiveDatum->id) }}" method="POST">
                            @csrf
                            @method('patch')
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="date">Date</label>
                                    <input type="date" name="date" class="form-control" id="date" value="{{ old('date', $printReceiveDatum->date) }}" required>
                                    @error('date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Product Combination</label>
                                    <input type="text" class="form-control" value="{{ $printReceiveDatum->productCombination->style->name }} - {{ $printReceiveDatum->productCombination->color->name }}" readonly>
                                </div>

                                <div class="form-group">
                                    <label>PO Number</label>
                                    <input type="text" class="form-control" value="{{ $printReceiveDatum->po_number }}" readonly>
                                </div>

                                <div class="form-group">
                                    <label>Receive Quantities by Size</label>
                                    <div class="row">
                                        @foreach ($allSizes as $size)
                                            @php
    // Convert to array if it's an object
    $receiveQty = is_object($printReceiveDatum->receive_quantities) ? 
        ($printReceiveDatum->receive_quantities->{$size->id} ?? 0) : 
        ($printReceiveDatum->receive_quantities[$size->id] ?? 0);
        
    $wasteQty = is_object($printReceiveDatum->receive_waste_quantities) ? 
        ($printReceiveDatum->receive_waste_quantities->{$size->id} ?? 0) : 
        ($printReceiveDatum->receive_waste_quantities[$size->id] ?? 0);
@endphp
                                            <div class="col-md-3 mb-3">
                                                <label for="receive_quantities_{{ $size->id }}">{{ $size->name }} Receive| Max = {{ $availableQuantities[$size->id] ?? 0 }}</label>
                                                <input type="number" name="receive_quantities[{{ $size->id }}]" id="receive_quantities_{{ $size->id }}"
                                                    class="form-control" value="{{ old('receive_quantities.' . $size->id, $receiveQty) }}" min="0" max="{{ $availableQuantities[$size->id] ?? 0 }}">
                                                @error('receive_quantities.' . $size->id)
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="receive_waste_quantities_{{ $size->id }}">{{ $size->name }} Waste</label>
                                                <input type="number" name="receive_waste_quantities[{{ $size->id }}]" id="receive_waste_quantities_{{ $size->id }}"
                                                    class="form-control" value="{{ old('receive_waste_quantities.' . $size->id, $wasteQty) }}" min="0">
                                                @error('receive_waste_quantities.' . $size->id)
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Update</button>
                                <a href="{{ route('print_receive_data.index') }}" class="btn btn-danger">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script>
        // prevent higher than max value input and if high then auto set to max value
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('input', function() {
                const max = parseInt(this.max);
                const min = parseInt(this.min) || 0;
                let value = parseInt(this.value);

                if (!isNaN(max) && value > max) {
                    this.value = max;
                } else if (value < min) {
                    this.value = min;
                }
            });
        });
    </script>
</x-backend.layouts.master>