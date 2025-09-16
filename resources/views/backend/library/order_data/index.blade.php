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
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('home') }}" class="btn btn-lg btn-outline-danger">
                            <i class="fas fa-arrow-left"></i> Close
                        </a>
                        @canany(['Admin', 'Supervisor','OrderDataEntry'])
                        <a href="{{ route('order_data.create') }}" class="btn btn-lg btn-outline-primary">
                            <i class="fas fa-plus"></i> Add Order Data
                        </a>
                        @endcanany
                        <a href="{{ route('order_data.report.total_order') }}" class="btn btn-lg btn-outline-info">
                            <i class="fas fa-chart-bar"></i> Total Order Report
                        </a>
                    </div>

                    <form class="d-flex flex-wrap align-items-end gap-2" action="{{ route('order_data.index') }}"
                        method="GET">
                        <div class="row g-2">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="style_id">Style</label>
                                    <select name="style_id[]" id="style_id" class="form-control" multiple>
                                        <option value="">Select Style</option>
                                        @foreach ($allStyles as $style)
                                            <option value="{{ $style->id }}"
                                                {{ in_array($style->id, (array) request('style_id')) ? 'selected' : '' }}>
                                                {{ $style->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="color_id">Color</label>
                                    <select name="color_id[]" id="color_id" class="form-control" multiple>
                                        <option value="">Select Color</option>
                                        @foreach ($allColors as $color)
                                            <option value="{{ $color->id }}"
                                                {{ in_array($color->id, (array) request('color_id')) ? 'selected' : '' }}>
                                                {{ $color->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="po_number">PO Number</label>
                                    <select name="po_number[]" id="po_number" class="form-control" multiple>
                                        <option value="">Select PO Number</option>
                                        @foreach ($distinctPoNumbers as $poNumber)
                                            <option value="{{ $poNumber }}"
                                                {{ in_array($poNumber, (array) request('po_number')) ? 'selected' : '' }}>
                                                {{ $poNumber }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control"
                                        value="{{ request('start_date') }}">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="end_date">End Date</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control"
                                        value="{{ request('end_date') }}">
                                </div>
                            </div>

                            <div class="col-md-2 d-flex align-items-end gap-2">
                                <input class="form-control me-2" type="search" name="search"
                                    placeholder="Search by PO/Style/Color" value="{{ request('search') }}">
                                <button class="btn btn-outline-success" type="submit">Search</button>
                                @if (request('search') ||
                                        request('date') ||
                                        request('style_id') ||
                                        request('color_id') ||
                                        request('po_number') ||
                                        request('start_date') ||
                                        request('end_date'))
                                    <a href="{{ route('order_data.index') }}"
                                        class="btn btn-outline-secondary">Reset</a>
                                @endif
                            </div>
                        </div>
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
                                             @canany(['Admin', 'Supervisor'])
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
                                            @else
                                                {{ ucfirst($data->po_status) }}
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('order_data.show', $data->id) }}"
                                                class="btn btn-sm btn-info">Show</a>
                                                @canany(['Admin', 'Supervisor'])
                                            <a href="{{ route('order_data.edit', $data->id) }}"
                                                class="btn btn-sm btn-warning">Edit</a>
                                            <form action="{{ route('order_data.destroy', $data->id) }}" method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                            @endcanany
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
    <script>
        $(document).ready(function () {
            $('#style_id, #color_id, #po_number').select2({
                placeholder: 'Select an option',
                allowClear: true,
                width: '100%'
            });
        });
    </script>

</x-backend.layouts.master>
