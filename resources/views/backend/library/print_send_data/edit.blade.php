<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Print/Embroidery Send Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Print/Embroidery Send Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('print_send_data.index') }}">Print/Embroidery Send Data</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <x-backend.layouts.elements.errors />
    <form action="{{ route('print_send_data.update', $printSendDatum->id) }}" method="post">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" name="date" id="date" class="form-control"
                        value="{{ old('date', $printSendDatum->date) }}" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>PO Number</label>
                    <input type="text" class="form-control" value="{{ $printSendDatum->po_number }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Old Order</label>
                    <input type="text" class="form-control" value="{{ $printSendDatum->old_order }}" readonly>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Buyer</label>
                    <input type="text" class="form-control" 
                        value="{{ $printSendDatum->productCombination->buyer->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Style</label>
                    <input type="text" class="form-control" 
                        value="{{ $printSendDatum->productCombination->style->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Color</label>
                    <input type="text" class="form-control" 
                        value="{{ $printSendDatum->productCombination->color->name ?? 'N/A' }}" readonly>
                </div>
            </div>
        </div>

        <table class="table table-bordered mt-4 text-center">
            <thead>
                <tr>
                    <th>Size</th>
                    <th>Send Quantity</th>
                    <th>Waste Quantity</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($allSizes as $size)
                    <tr>
                        <td>{{ $size->name }}</td>
                        <td>
                            <input type="number" name="send_quantities[{{ $size->id }}]" 
                                class="form-control" 
                                value="{{ $printSendDatum->send_quantities[$size->id] ?? 0 }}" 
                                min="0">
                        </td>
                        <td>
                            <input type="number" name="send_waste_quantities[{{ $size->id }}]" 
                                class="form-control" 
                                value="{{ $printSendDatum->send_waste_quantities[$size->id] ?? 0 }}" 
                                min="0">
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td><strong>Total</strong></td>
                    <td><strong>{{ $printSendDatum->total_send_quantity }}</strong></td>
                    <td><strong>{{ $printSendDatum->total_send_waste_quantity }}</strong></td>
                </tr>
            </tbody>
        </table>

        <a href="{{ route('print_send_data.index') }}" class="btn btn-secondary mt-3">Back</a>
        <button type="submit" class="btn btn-primary mt-3">Update Print/Embroidery Send Data</button>
    </form>
</x-backend.layouts.master>