<x-backend.layouts.master>
    <x-slot name="pageTitle">
        WIP (Work In Progress) Report
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> WIP (Work In Progress) Report </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('print_receive_data.index') }}">Print/Emb Send</a></li>
            <li class="breadcrumb-item active">WIP Report</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Work In Progress (Sent - Received - Waste)</h3>
                            <a href="{{ route('print_receive_data.index') }}" class="btn btn-primary float-right">Back
                                to List</a>
                        </div>
                        <div class="card-body" style="overflow-x: auto;">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Sl#</th>
                                        <th>Style</th>
                                        <th>Color</th>
                                        @foreach ($allSizes as $size)
                                            <th>{{ strtoupper($sizeIdToName[$size->id] ?? 'N/A') }} (WIP)</th>
                                            <th>{{ strtoupper($sizeIdToName[$size->id] ?? 'N/A') }} (Waste)</th>
                                        @endforeach
                                        <th>Total Sent</th>
                                        <th>Total Received (Good)</th>
                                        <th>Total Received (Waste)</th>
                                        <th>Total WIP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($wipData as $data)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $data['style'] }}</td>
                                            <td>{{ $data['color'] }}</td>
                                            @foreach ($allSizes as $size)
                                                <td>{{ $data['sizes'][$size->id]['waiting'] ?? 0 }}</td>
                                                <td>{{ $data['sizes'][$size->id]['waste'] ?? 0 }}</td>
                                            @endforeach
                                            <td>{{ $data['total_sent'] }}</td>
                                            <td>{{ $data['total_received'] }}</td>
                                            <td>{{ $data['total_received_waste'] }}</td>
                                            <td>{{ $data['waiting'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ 7 + (count($allSizes) * 2) }}" class="text-center">No WIP
                                                data found.</td>
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