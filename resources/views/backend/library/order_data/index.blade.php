<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Order Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Order Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Order</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <a href="{{ route('home') }}" class="btn btn-lg btn-outline-danger">
                        <i class="fas fa-arrow-left"></i> Close
                    </a>
                    <a href="{{ route('order_data.create') }}" class="btn btn-lg btn-outline-primary">
                        <i class="fas fa-plus"></i> Add Order Data
                    </a>
                    <a href="{{ route('order_data.report.total_order') }}" class="btn btn-lg btn-outline-info">
                        <i class="fas fa-chart-bar"></i> Total Order Report
                    </a>
                    <form class="d-flex float-right" action="{{ route('order_data.index') }}" method="GET">
                        <input class="form-control me-2" type="search" name="search"
                            placeholder="Search by PO/Style/Color" value="{{ request('search') }}">
                        <input class="form-control me-2" type="date" name="date" value="{{ request('date') }}">
                        <button class="btn btn-outline-success" type="submit">Search</button>
                        @if (request('search') || request('date'))
                            <a href="{{ route('order_data.index') }}" class="btn btn-secondary ml-2">Reset</a>
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
                                    <th>PO Number</th>
                                    <th>Style</th>
                                    <th>Color</th>
                                    @foreach ($allSizes as $size)
                                        <th>{{ $size->name }}</th>
                                    @endforeach
                                    <th>Total Order</th>
                                    <th>PO Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orderData as $key => $data)
                                    <tr>
                                        <td>{{ $orderData->firstItem() + $key }}</td>
                                        <td>{{ \Carbon\Carbon::parse($data->date)->format('d-M-Y') }}</td>
                                        <td>{{ $data->po_number }}</td>
                                        <td>{{ $data->style->name ?? 'N/A' }}</td>
                                        <td>{{ $data->color->name ?? 'N/A' }}</td>

                                        @foreach ($allSizes as $size)
                                            <td>
                                                {{ $data->order_quantities[$size->id] ?? 0 }}
                                            </td>
                                        @endforeach

                                        <td>{{ $data->total_order_quantity }}</td>
                                        <td>
                                            <!--from select to change Status-->
                                            <form action="{{ route('order_data.update_status', $data->id) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <select name="po_status" class="form-select form-select-sm"
                                                    onchange="this.form.submit()">
                                                    <option value="running"
                                                        {{ $data->po_status == 'running' ? 'selected' : '' }}>Running
                                                    </option>
                                                    <option value="completed"
                                                        {{ $data->po_status == 'completed' ? 'selected' : '' }}>
                                                        Completed</option>
                                                    <option value="cancelled"
                                                        {{ $data->po_status == 'cancelled' ? 'selected' : '' }}>
                                                        Cancelled</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <a href="{{ route('order_data.show', $data->id) }}"
                                                class="btn btn-sm btn-info">Show</a>
                                            <a href="{{ route('order_data.edit', $data->id) }}"
                                                class="btn btn-sm btn-warning">Edit</a>
                                            <form action="{{ route('order_data.destroy', $data->id) }}" method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 7 + count($allSizes) }}" class="text-center">No order data
                                            found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center">
                        {{ $orderData->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>
