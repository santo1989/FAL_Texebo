<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Print/Embroidery Balance Quantity Report
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Print/Embroidery Balance Quantity Report </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('print_receive_data.index') }}">Print/Emb Receive</a></li>
            <li class="breadcrumb-item active">Balance Quantity Report</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Print/Embroidery Balance Quantities (Sent - Received - Waste)</h3>
                            <a href="{{ route('print_receive_data.index') }}" class="btn btn-primary float-right">Back
                                to List</a>
                            <form class="d-flex float-right"
                                action="{{ route('print_receive_data.report.balance_quantity') }}" method="GET">
                                <input class="form-control me-2" type="date" name="start_date"
                                    value="{{ request('start_date') }}">
                                <input class="form-control me-2" type="date" name="end_date"
                                    value="{{ request('end_date') }}">
                                <button class="btn btn-outline-success" type="submit">Filter</button>
                            </form>
                        </div>
                        <div class="card-body" style="overflow-x: auto;">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Sl#</th>
                                        <th>Style</th>
                                        <th>Color</th>
                                        @foreach ($allSizes as $size)
                                            <th>{{ strtoupper($sizeIdToName[$size->id] ?? 'N/A') }} (Bal)</th>
                                            <th>{{ strtoupper($sizeIdToName[$size->id] ?? 'N/A') }} (Waste)</th>
                                        @endforeach
                                        <th>Total Sent</th>
                                        <th>Total Received</th>
                                        <th>Total Waste</th>
                                        <th>Total Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($reportData as $data)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $data['style'] }}</td>
                                            <td>{{ $data['color'] }}</td>
                                            @foreach ($allSizes as $size)
                                                <td>{{ $data['sizes'][$size->id]['balance'] ?? 0 }}</td>
                                                <td>{{ $data['sizes'][$size->id]['waste'] ?? 0 }}</td>
                                            @endforeach
                                            <td>{{ $data['total_sent'] }}</td>
                                            <td>{{ $data['total_received'] }}</td>
                                            <td>{{ $data['total_waste'] }}</td>
                                            <td>{{ $data['total_balance'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ 7 + (count($allSizes) * 2) }}" class="text-center">No balance
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