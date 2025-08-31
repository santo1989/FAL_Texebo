<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Total Balance Report
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Total Balance Report </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('output_finishing_data.index') }}">Output Finishing</a></li>
            <li class="breadcrumb-item active">Total Balance Report</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <a href="{{ route('output_finishing_data.index') }}" class="btn btn-secondary mb-3 float-right">
                        <i class="fas fa-arrow-left"></i> Close
                    </a>
                </div>
                <div class="card-body">
                    @if (empty($reportData))
                        <div class="alert alert-info">
                            No balance data available.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Style</th>
                                        <th>Color</th>
                                        @foreach($allSizes as $size)
                                            <th>{{ $size->name }}</th>
                                        @endforeach
                                        <th>Total Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($reportData as $data)
                                        <tr>
                                            <td>{{ $data['style'] }}</td>
                                            <td>{{ $data['color'] }}</td>
                                            @foreach($allSizes as $size)
                                                <td>{{ $data['sizes'][$size->id] ?? 0 }}</td>
                                            @endforeach
                                            <td>{{ $data['total'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2">Grand Total</th>
                                        @foreach($allSizes as $size)
                                            <th>
                                                @php
                                                    $totalSizeBalance = 0;
                                                    foreach ($reportData as $data) {
                                                        $totalSizeBalance += $data['sizes'][$size->id] ?? 0;
                                                    }
                                                    echo $totalSizeBalance;
                                                @endphp
                                            </th>
                                        @endforeach
                                        <th>
                                            @php
                                                $grandTotalBalance = 0;
                                                foreach ($reportData as $data) {
                                                    $grandTotalBalance += $data['total'];
                                                }
                                                echo $grandTotalBalance;
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