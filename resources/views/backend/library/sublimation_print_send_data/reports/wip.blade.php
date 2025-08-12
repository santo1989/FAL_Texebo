<!-- resources/views/backend/library/print_send_data/reports/wip.blade.php -->
<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Print/Embroidery WIP Report
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Print/Emb Send Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">WIP Report</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title text-center">Print/Embroidery Work-in-Progress Report</h3>
                            <a href="{{ route('print_send_data.index') }}" class="btn btn-lg btn-outline-danger float-right">
                                <i class="fas fa-arrow-left"></i> Close
                            </a>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th rowspan="2">Style</th>
                                        <th rowspan="2">Color</th>
                                        @foreach ($allSizes as $size)
                                            <th colspan="3" class="text-center">{{ strtoupper($size->name) }}</th>
                                        @endforeach
                                        <th rowspan="2">Total Cut</th>
                                        <th rowspan="2">Total Sent</th>
                                        <th rowspan="2">Total Waiting</th>
                                    </tr>
                                    <tr>
                                        @foreach ($allSizes as $size)
                                            <th>Cut</th>
                                            <th>Sent</th>
                                            <th>WIP</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($wipData as $data)
                                        <tr>
                                            <td>{{ $data['style'] }}</td>
                                            <td>{{ $data['color'] }}</td>
                                            @foreach ($allSizes as $size)
                                                <td>{{ $data['sizes'][$size->name]['cut'] ?? 0 }}</td>
                                                <td>{{ $data['sizes'][$size->name]['sent'] ?? 0 }}</td>
                                                <td>{{ $data['sizes'][$size->name]['waiting'] ?? 0 }}</td>
                                            @endforeach
                                            <td>{{ $data['total_cut'] }}</td>
                                            <td>{{ $data['total_sent'] }}</td>
                                            <td>{{ $data['waiting'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ 2 + (count($allSizes) * 3) + 3 }}" class="text-center">No WIP data found.</td>
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
