<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Order Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Edit Order Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('order_data.index') }}">Order</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Edit Order Data</h3>
                        </div>
                        <form action="{{ route('order_data.update', $orderDatum->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="date">Date</label>
                                    <input type="date" name="date" class="form-control" id="date"
                                        value="{{ old('date', $orderDatum->date) }}" required>
                                    @error('date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="po_number">PO Number</label>
                                    <input type="text" name="po_number" class="form-control" id="po_number"
                                        value="{{ old('po_number', $orderDatum->po_number) }}" required>
                                    @error('po_number')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="style_name">Style</label>
                                    <input type="text" class="form-control" id="style_name"
                                        value="{{ $orderDatum->style->name ?? 'N/A' }}" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="color_name">Color</label>
                                    <input type="text" class="form-control" id="color_name"
                                        value="{{ $orderDatum->color->name ?? 'N/A' }}" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="po_status">PO Status</label>
                                    <select name="po_status" id="po_status" class="form-control" required>
                                        @foreach ($poStatuses as $status)
                                            <option value="{{ $status }}"
                                                @if ($orderDatum->po_status === $status) selected @endif>
                                                {{ ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                    @error('po_status')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label>Order Quantities by Size</label>
                                    <div class="row">
                                        @php
                                            $quantities = $orderDatum->order_quantities;
                                        @endphp
                                        @foreach ($sizes as $size)
                                            <div class="col-md-3 mb-3">
                                                <label for="quantity_{{ $size->id }}">{{ $size->name }}</label>
                                                <input type="number" name="quantities[{{ $size->id }}]"
                                                    id="quantity_{{ $size->id }}" class="form-control"
                                                    value="{{ old('quantities.' . $size->id, $quantities[$size->id] ?? 0) }}"
                                                    min="0">
                                                @error('quantities.' . $size->id)
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Update</button>
                                <a href="{{ route('order_data.index') }}" class="btn btn-danger">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>
