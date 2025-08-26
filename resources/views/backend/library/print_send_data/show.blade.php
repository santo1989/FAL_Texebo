<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Print/Embroidery Send Details
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Print/Embroidery Send Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('print_send_data.index') }}">Print/Embroidery Send Data</a></li>
            <li class="breadcrumb-item active">Details</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Print/Embroidery Send Details</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <strong>Date:</strong> {{ $printSendDatum->date }}
                </div>
                <div class="col-md-4">
                    <strong>PO Number:</strong> {{ $printSendDatum->po_number }}
                </div>
                <div class="col-md-4">
                    <strong>Old Order:</strong> {{ $printSendDatum->old_order }}
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-4">
                    <strong>Buyer:</strong> {{ $printSendDatum->productCombination->buyer->name ?? 'N/A' }}
                </div>
                <div class="col-md-4">
                    <strong>Style:</strong> {{ $printSendDatum->productCombination->style->name ?? 'N/A' }}
                </div>
                <div class="col-md-4">
                    <strong>Color:</strong> {{ $printSendDatum->productCombination->color->name ?? 'N/A' }}
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
                            <td>{{ $printSendDatum->send_quantities[$size->id] ?? 0 }}</td>
                            <td>{{ $printSendDatum->send_waste_quantities[$size->id] ?? 0 }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td><strong>Total</strong></td>
                        <td><strong>{{ $printSendDatum->total_send_quantity }}</strong></td>
                        <td><strong>{{ $printSendDatum->total_send_waste_quantity }}</strong></td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-3">
                <a href="{{ route('print_send_data.edit', $printSendDatum->id) }}" class="btn btn-primary">Edit</a>
                <a href="{{ route('print_send_data.index') }}" class="btn btn-secondary">Back to List</a>
                <form action="{{ route('print_send_data.destroy', $printSendDatum->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this record?');">Delete</button>
                </form>
            </div>
        </div>
    </div>
</x-backend.layouts.master>