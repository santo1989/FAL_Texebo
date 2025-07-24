<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Add Print/Embroidery Receive Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Add Print/Embroidery Receive Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('print_receive_data.index') }}">Print/Emb Receive</a></li>
            <li class="breadcrumb-item active">Add New</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Add Print/Embroidery Receive Data</h3>
                        </div>
                        <form action="{{ route('print_receive_data.store') }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="date">Date</label>
                                    <input type="date" name="date" class="form-control" id="date" value="{{ old('date', date('Y-m-d')) }}" required>
                                    @error('date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="product_combination_id">Product Combination (Style - Color)</label>
                                    <select name="product_combination_id" id="product_combination_id" class="form-control" required>
                                        <option value="">Select Product Combination</option>
                                        @foreach ($productCombinations as $pc)
                                            <option value="{{ $pc->id }}" {{ old('product_combination_id') == $pc->id ? 'selected' : '' }}>
                                                {{ $pc->style->name }} - {{ $pc->color->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('product_combination_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Receive Quantities by Size</label>
                                    <div class="row">
                                        @foreach ($sizes as $size)
                                            <div class="col-md-3 mb-3">
                                                <label for="quantity_{{ $size->id }}">{{ $size->name }}</label>
                                                <input type="number" name="quantities[{{ $size->id }}]" id="quantity_{{ $size->id }}"
                                                    class="form-control" value="{{ old('quantities.' . $size->id, 0) }}" min="0">
                                                @error('quantities.' . $size->id)
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('total')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Submit</button>
                                <a href="{{ route('print_receive_data.index') }}" class="btn btn-danger">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>