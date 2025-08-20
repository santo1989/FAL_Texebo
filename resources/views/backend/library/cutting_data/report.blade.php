<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Cutting Report
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Cutting Report </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Cutting Report</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title text-center">Total Cutting Report</h3>
                           
                            <div class="card-tools pt-1">
                                <form action="{{ route('cutting_data_report') }}" method="GET" class="form-inline">
                                    <div class="row justify-content-between">
                                        <div class="col-2 "> <a href="{{ route('home') }}" class="btn btn-lg btn-outline-danger">
                                <i class="fas fa-arrow-left"></i> Close
                            </a>
                                        </div>

                                        <div class="col-2 ">
                                            <label for="start_date" class="mr-2">From:</label>
                                            <input type="date" name="start_date" id="start_date"
                                                class="form-control mr-2" value="{{ request('start_date') }}"> - 
                                            <label for="end_date" class="mr-2">To:</label>
                                            <input type="date" name="end_date" id="end_date"
                                                class="form-control mr-2" value="{{ request('end_date') }}">
                                        </div>
                                        <div class="col-2 ">
                                            <label for="style_id" class="mr-2">Style:</label>
                                            <select name="style_id" id="style_id" class="form-control mr-2">
                                                <option value="">All Styles</option>
                                                @foreach ($styles as $style)
                                                    <option value="{{ $style->id }}"
                                                        {{ request('style_id') == $style->id ? 'selected' : '' }}>
                                                        {{ $style->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-2 ">
                                            <label for="color_id" class="mr-2">Color:</label>
                                            <select name="color_id" id="color_id" class="form-control mr-2">
                                                <option value="">All Colors</option>
                                                @foreach ($colors as $color)
                                                    <option value="{{ $color->id }}"
                                                        {{ request('color_id') == $color->id ? 'selected' : '' }}>
                                                        {{ $color->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-2 ">
                                            <label for="po_number">PO Number</label>
                                            <select name="po_number" id="po_number" class="form-control">
                                                <option value="">Select PO Number</option>
                                                @foreach ($distinctPoNumbers as $poNumber)
                                                    <option value="{{ $poNumber }}"
                                                        {{ request('po_number') == $poNumber ? 'selected' : '' }}>
                                                        {{ $poNumber }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-2 ">
                                            <button type="submit" class="btn btn-primary">Filter</button>
                                            <a href="{{ route('cutting_data_report') }}"
                                                class="btn btn-secondary ml-2">Reset</a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="card-body" style="overflow-x: auto;">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th rowspan="2">PO Number</th>
                                        <th rowspan="2">Style</th>
                                        <th rowspan="2">Color</th>
                                        @foreach ($allSizes as $size)
                                            <th colspan="3" class="text-center">
                                                {{ strtoupper($size->name) }}
                                            </th>
                                        @endforeach
                                        <th colspan="3">Total Quantities</th>
                                    </tr>
                                    <tr>
                                        @foreach ($allSizes as $size)
                                            <th>Cut Qty</th>
                                            <th>Waste Qty</th>
                                            <th class="bg-info">Order Qty</th>
                                        @endforeach
                                        <th>Cut</th>
                                        <th>Waste</th>
                                        <th class="bg-info">Order</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($reportData as $data)
                                        <tr>
                                            <td>{{ $data['po_number'] }}</td>
                                            <td>{{ $data['style'] }}</td>
                                            <td>{{ $data['color'] }}</td>
                                            @foreach ($allSizes as $size)
                                                <td>{{ $data['cut_sizes'][$size->id] ?? 0 }}</td>
                                                <td>{{ $data['waste_sizes'][$size->id] ?? 0 }}</td>
                                                <td class="bg-info">{{ $data['order_sizes'][$size->id] ?? 0 }}</td>
                                            @endforeach
                                            <td>{{ $data['total_cut'] }}</td>
                                            <td>{{ $data['total_waste'] }}</td>
                                            <td class="bg-info">{{ $data['total_order'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ 3 + count($allSizes) * 3 + 3 }}" class="text-center">No
                                                report
                                                data found for the selected criteria.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>
