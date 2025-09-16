<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Print/Embroidery Receive Details
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Print/Embroidery Receive Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('print_receive_data.index') }}">Print/Embroidery Receive
                    Data</a></li>
            <li class="breadcrumb-item active">Details</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Print/Embroidery Receive Details</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <strong>Date:</strong> {{ $printReceiveDatum->date }}
                </div>
                <div class="col-md-4">
                    <strong>PO Number:</strong> {{ $printReceiveDatum->po_number }}
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-4">
                    <strong>Buyer:</strong> {{ $printReceiveDatum->productCombination->buyer->name ?? 'N/A' }}
                </div>
                <div class="col-md-4">
                    <strong>Style:</strong> {{ $printReceiveDatum->productCombination->style->name ?? 'N/A' }}
                </div>
                <div class="col-md-4">
                    <strong>Color:</strong> {{ $printReceiveDatum->productCombination->color->name ?? 'N/A' }}
                </div>
            </div>

            <table class="table table-bordered mt-4 text-center">
                <thead>
                    <tr>
                        <th>Size</th>
                        <th>Receive Quantity</th>
                        <th>Waste Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($allSizes as $size)
                        <tr>
                            <td>{{ $size->name }}</td>
                            <td>{{ $printReceiveDatum->receive_quantities[$size->id] ?? 0 }}</td>
                            <td>{{ $printReceiveDatum->receive_waste_quantities[$size->id] ?? 0 }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td><strong>Total</strong></td>
                        <td><strong>{{ $printReceiveDatum->total_receive_quantity }}</strong></td>
                        <td><strong>{{ $printReceiveDatum->total_receive_waste_quantity }}</strong></td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-3">
                @canany(['Admin', 'Print Receive', 'Supervisor'])
                    <a href="{{ route('print_receive_data.edit', $printReceiveDatum->id) }}"
                        class="btn btn-primary">Edit</a>
                @endcanany
                <a href="{{ route('print_receive_data.index') }}" class="btn btn-secondary">Back to List</a>
                @canany(['Admin', 'Print Receive', 'Supervisor'])
                    <form action="{{ route('print_receive_data.destroy', $printReceiveDatum->id) }}" method="POST"
                        class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger"
                            onclick="return confirm('Are you sure you want to delete this record?');">Delete</button>
                    </form>
                @endcanany
            </div>
        </div>
    </div>
</x-backend.layouts.master>
