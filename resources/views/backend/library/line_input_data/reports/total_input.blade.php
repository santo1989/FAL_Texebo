<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Total Input Report
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Sewing Input Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Total Input Report</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title text-center">Total Input Report</h3>
                            <a href="{{ route('line_input_data.index') }}"
                                class="btn btn-lg btn-outline-danger float-right">
                                <i class="fas fa-arrow-left"></i> Close
                            </a>
                        </div>
                        <div class="card-body" style="overflow-x: auto;">
                            <table class="table table-bordered table-hover text-center">
                                <thead>
                                    <tr>
                                        <th rowspan="2">Style</th>
                                        <th rowspan="2">Color</th>
                                        @foreach ($allSizes as $size)
                                            <th colspan="2">{{ strtoupper($size->name) }}</th>
                                        @endforeach
                                        <th rowspan="2">Total Input</th>
                                        <th rowspan="2">Total Waste</th>
                                    </tr>
                                    <tr>
                                        @foreach ($allSizes as $size)
                                            <th>Input</th>
                                            <th>Waste</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($reportData as $data)
                                        <tr>
                                            <td>{{ $data['style'] }}</td>
                                            <td>{{ $data['color'] }}</td>
                                            @foreach ($allSizes as $size)
                                                <td>{{ $data['sizes'][strtolower($size->name)] ?? 0 }}</td>
                                                <td>{{ $data['waste_sizes'][strtolower($size->name)] ?? 0 }}</td>
                                            @endforeach
                                            <td>{{ $data['total'] }}</td>
                                            <td>{{ $data['total_waste'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ count($allSizes) * 2 + 4 }}" class="text-center">No data
                                                found.
                                            </td>
                                        </tr>
                                    @endforelse

                                    @if (!empty($reportData))
                                        <tr>
                                            <td colspan="2" class="text-center"><strong>Total</strong></td>
                                            @foreach ($allSizes as $size)
                                                <td><strong>{{ $totalInputBySize[strtolower($size->name)] ?? 0 }}</strong>
                                                </td>
                                                <td><strong>{{ $totalWasteBySize[strtolower($size->name)] ?? 0 }}</strong>
                                                </td>
                                            @endforeach
                                            <td><strong>{{ $grandTotalInput }}</strong></td>
                                            <td><strong>{{ $grandTotalWaste }}</strong></td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>
