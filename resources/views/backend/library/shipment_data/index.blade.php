<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Shipment Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Shipment Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Shipment</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            @if (session('message'))
                <div class="alert alert-success">
                    {{ session('message') }}
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <a href="{{ route('home') }}" class="btn btn-lg btn-outline-danger">
                        <i class="fas fa-arrow-left"></i> Close
                    </a>
                    <a href="{{ route('shipment_data.create') }}" class="btn btn-lg btn-outline-primary">
                        <i class="fas fa-plus"></i> Add Shipment Data
                    </a>
                    <a href="{{ route('shipment_data.report.total_shipment') }}"
                        class="btn btn-lg btn-outline-info">
                        <i class="fas fa-ship"></i> Total Shipment Report
                    </a>
                    <a href="{{ route('shipment_data.report.ready_goods') }}"
                        class="btn btn-lg btn-outline-info ml-2">
                        <i class="fas fa-warehouse"></i> Ready Goods Report
                    </a>
                    
                    <a href="{{ route('shipment_data.report.final_balance') }}"
                        class="btn btn-lg btn-outline-secondary ml-2">
                        <i class="fas fa-balance-scale"></i> Final Balance Report
                    </a>
                    
                    <form class="d-flex float-right" action="{{ route('shipment_data.index') }}" method="GET">
                        <input class="form-control me-2" type="search" name="search"
                            placeholder="Search by Style/Color" value="{{ request('search') }}">
                        <input class="form-control me-2" type="date" name="date" value="{{ request('date') }}">
                        <button class="btn btn-outline-success" type="submit">Search</button>
                        @if (request('search') || request('date'))
                            <a href="{{ route('shipment_data.index') }}" class="btn btn-secondary ml-2">Reset</a>
                        @endif
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>SL#</th>
                                    <th>Date</th>
                                    <th>Buyer</th>
                                    <th>Style</th>
                                    <th>Color</th>
                                    @foreach ($allSizes as $size)
                                        <th>{{ $size->name }}</th>
                                    @endforeach
                                    <th>Total Shipped</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($shipmentData as $key => $data)
                                    <tr>
                                        <td>{{ $shipmentData->firstItem() + $key }}</td>
                                        <td>{{ \Carbon\Carbon::parse($data->date)->format('d-M-Y') }}</td>
                                        <td>{{ $data->productCombination->buyer->name ?? 'N/A' }}</td>
                                        <td>{{ $data->productCombination->style->name ?? 'N/A' }}</td>
                                        <td>{{ $data->productCombination->color->name ?? 'N/A' }}</td>
                                        @foreach ($allSizes as $size)
                                            <td>{{ $data->shipment_quantities[$size->name] ?? 0 }}</td>
                                        @endforeach
                                        <td>{{ $data->total_shipment_quantity }}</td>
                                        <td>
                                            <a href="{{ route('shipment_data.show', $data->id) }}"
                                                class="btn btn-sm btn-info">Show</a>
                                            <a href="{{ route('shipment_data.edit', $data->id) }}"
                                                class="btn btn-sm btn-warning">Edit</a>
                                            <form action="{{ route('shipment_data.destroy', $data->id) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 6 + count($allSizes) }}" class="text-center">No shipment data found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center">
                        {{ $shipmentData->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>