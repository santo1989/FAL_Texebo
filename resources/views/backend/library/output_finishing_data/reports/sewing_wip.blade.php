<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Sewing WIP Report
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Sewing WIP Report </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('finish_packing_data.index') }}">Finish Packing</a></li>
            <li class="breadcrumb-item active">Sewing WIP Report</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Sewing Work-In-Progress (WIP) Report</h3>
                    <!--back button-->
                    <a href="{{ route('finish_packing_data.index') }}" class="btn btn-secondary mb-3">
                        <i class="fas fa-arrow-left"></i> close
                    </a>
                </div>
                <div class="card-body">
                    @if (empty($wipData))
                        <div class="alert alert-info">
                            No Sewing WIP data available.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Style</th>
                                        <th>Color</th>
                                        @foreach ($allSizes as $size)
                                            <th>{{ $size->name }}</th>
                                        @endforeach
                                        <th>Total WIP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($wipData as $data)
                                        <tr>
                                            <td>{{ $data['style'] }}</td>
                                            <td>{{ $data['color'] }}</td>
                                            @foreach ($allSizes as $size)
                                                <td>{{ $data['sizes'][strtolower($size->name)] ?? 0 }}</td>
                                            @endforeach
                                            <td>{{ $data['total'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2">Grand Total</th>
                                        @foreach ($allSizes as $size)
                                            <th>
                                                @php
                                                    $totalSizeWip = 0;
                                                    foreach ($wipData as $data) {
                                                        $totalSizeWip += $data['sizes'][strtolower($size->name)] ?? 0;
                                                    }
                                                    echo $totalSizeWip;
                                                @endphp
                                            </th>
                                        @endforeach
                                        <th>
                                            @php
                                                $grandTotalWip = 0;
                                                foreach ($wipData as $data) {
                                                    $grandTotalWip += $data['total'];
                                                }
                                                echo $grandTotalWip;
                                            @endphp
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>
