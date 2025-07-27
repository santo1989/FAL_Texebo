<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Balance Report
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Balance Report </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('shipment_data.index') }}">Shipment</a></li>
            <li class="breadcrumb-item active">Balance Report</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Stage Balance Report (Size Wise)</h3>
                    <a href="{{ route('shipment_data.index') }}" class="btn btn-secondary mb-3 float-right">
                        <i class="fas fa-arrow-left"></i> Close
                    </a>
                </div>
                <div class="card-body">
                    @if (empty($reportData))
                        <div class="alert alert-info">
                            No Balance data available.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th rowspan="2">Style</th>
                                        <th rowspan="2">Color</th>
                                        <th colspan="{{ count($allSizes) }}" class="text-center bg-info text-white">
                                            Cutting (Total Cut)</th>
                                        <th colspan="{{ count($allSizes) }}" class="text-center bg-primary text-white">
                                            Print WIP (Sent - Received)</th>
                                        <th colspan="{{ count($allSizes) }}" class="text-center bg-warning text-dark">
                                            Sewing WIP (Received from Print - Line Input)</th>
                                        <th colspan="{{ count($allSizes) }}" class="text-center bg-success text-white">
                                            Packing WIP (Line Input - Packed)</th>
                                        <th colspan="{{ count($allSizes) }}" class="text-center bg-secondary text-white">
                                            Finish Packing (Packed - Shipped)</th>
                                        <th colspan="{{ count($allSizes) }}" class="text-center bg-dark text-white">
                                            Shipment (Shipped)</th>
                                        <th colspan="{{ count($allSizes) }}" class="text-center bg-light text-dark">
                                            Total (Cut - Shipped)</th>
                                    </tr>
                                    <tr>
                                        {{-- Size headers for Cutting --}}
                                        @foreach ($allSizes as $size)
                                            <th class="bg-info text-white">{{ $size->name }}</th>
                                        @endforeach
                                        {{-- Size headers for Print WIP --}}
                                        @foreach ($allSizes as $size)
                                            <th class="bg-primary text-white">{{ $size->name }}</th>
                                        @endforeach
                                        {{-- Size headers for Sewing WIP --}}
                                        @foreach ($allSizes as $size)
                                            <th class="bg-warning text-dark">{{ $size->name }}</th>
                                        @endforeach
                                        {{-- Size headers for Packing WIP --}}
                                        @foreach ($allSizes as $size)
                                            <th class="bg-success text-white">{{ $size->name }}</th>
                                        @endforeach
                                        {{-- Size headers for Finish Packing --}}
                                        @foreach ($allSizes as $size)
                                            <th class="bg-secondary text-white">{{ $size->name }}</th>
                                        @endforeach
                                        {{-- Size headers for Shipment --}}
                                        @foreach ($allSizes as $size)
                                            <th class="bg-dark text-white">{{ $size->name }}</th>
                                        @endforeach
                                        {{-- Size headers for Total --}}
                                        @foreach ($allSizes as $size)
                                            <th class="bg-light text-dark">{{ $size->name }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($reportData as $data)
                                        <tr>
                                            <td>{{ $data['style'] }}</td>
                                            <td>{{ $data['color'] }}</td>
                                            {{-- Cutting Quantities --}}
                                            @foreach ($allSizes as $size)
                                                <td>{{ $data['stage_balances']['cutting'][strtolower($size->name)] ?? 0 }}
                                                </td>
                                            @endforeach
                                            {{-- Print WIP Quantities --}}
                                            @foreach ($allSizes as $size)
                                                <td>{{ $data['stage_balances']['print_wip'][strtolower($size->name)] ?? 0 }}
                                                </td>
                                            @endforeach
                                            {{-- Sewing WIP Quantities --}}
                                            @foreach ($allSizes as $size)
                                                <td>{{ $data['stage_balances']['sewing_wip'][strtolower($size->name)] ?? 0 }}
                                                </td>
                                            @endforeach
                                            {{-- Packing WIP Quantities --}}
                                            @foreach ($allSizes as $size)
                                                <td>{{ $data['stage_balances']['packing_wip'][strtolower($size->name)] ?? 0 }}
                                                </td>
                                            @endforeach
                                            {{-- Finish Packing Quantities --}}
                                            @foreach ($allSizes as $size)
                                                <td>{{ $data['stage_balances']['finish_packing'][strtolower($size->name)] ?? 0 }}
                                                </td>
                                            @endforeach
                                            {{-- Shipment Quantities --}}
                                            @foreach ($allSizes as $size)
                                                <td>{{ $data['stage_balances']['shipment'][strtolower($size->name)] ?? 0 }}
                                                </td>
                                            @endforeach
                                            {{-- Total Quantities --}}
                                            @foreach ($allSizes as $size)
                                                <td>{{ $data['stage_balances']['total'][strtolower($size->name)] ?? 0 }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2">Grand Totals</th>
                                        {{-- Grand Total for Cutting --}}
                                        @foreach ($allSizes as $size)
                                            <th>
                                                @php
                                                    $grandTotalCutting = 0;
                                                    foreach ($reportData as $data) {
                                                        $grandTotalCutting +=
                                                            $data['stage_balances']['cutting'][
                                                                strtolower($size->name)
                                                            ] ?? 0;
                                                    }
                                                    echo $grandTotalCutting;
                                                @endphp
                                            </th>
                                        @endforeach
                                        {{-- Grand Total for Print WIP --}}
                                        @foreach ($allSizes as $size)
                                            <th>
                                                @php
                                                    $grandTotalPrintWip = 0;
                                                    foreach ($reportData as $data) {
                                                        $grandTotalPrintWip +=
                                                            $data['stage_balances']['print_wip'][
                                                                strtolower($size->name)
                                                            ] ?? 0;
                                                    }
                                                    echo $grandTotalPrintWip;
                                                @endphp
                                            </th>
                                        @endforeach
                                        {{-- Grand Total for Sewing WIP --}}
                                        @foreach ($allSizes as $size)
                                            <th>
                                                @php
                                                    $grandTotalSewingWip = 0;
                                                    foreach ($reportData as $data) {
                                                        $grandTotalSewingWip +=
                                                            $data['stage_balances']['sewing_wip'][
                                                                strtolower($size->name)
                                                            ] ?? 0;
                                                    }
                                                    echo $grandTotalSewingWip;
                                                @endphp
                                            </th>
                                        @endforeach
                                        {{-- Grand Total for Packing WIP --}}
                                        @foreach ($allSizes as $size)
                                            <th>
                                                @php
                                                    $grandTotalPackingWip = 0;
                                                    foreach ($reportData as $data) {
                                                        $grandTotalPackingWip +=
                                                            $data['stage_balances']['packing_wip'][
                                                                strtolower($size->name)
                                                            ] ?? 0;
                                                    }
                                                    echo $grandTotalPackingWip;
                                                @endphp
                                            </th>
                                        @endforeach
                                        {{-- Grand Total for Finish Packing --}}
                                        @foreach ($allSizes as $size)
                                            <th>
                                                @php
                                                    $grandTotalFinishPacking = 0;
                                                    foreach ($reportData as $data) {
                                                        $grandTotalFinishPacking +=
                                                            $data['stage_balances']['finish_packing'][
                                                                strtolower($size->name)
                                                            ] ?? 0;
                                                    }
                                                    echo $grandTotalFinishPacking;
                                                @endphp
                                            </th>
                                        @endforeach
                                        {{-- Grand Total for Shipment --}}
                                        @foreach ($allSizes as $size)
                                            <th>
                                                @php
                                                    $grandTotalShipment = 0;
                                                    foreach ($reportData as $data) {
                                                        $grandTotalShipment +=
                                                            $data['stage_balances']['shipment'][
                                                                strtolower($size->name)
                                                            ] ?? 0;
                                                    }
                                                    echo $grandTotalShipment;
                                                @endphp
                                            </th>
                                        @endforeach
                                        @foreach ($allSizes as $size)
                                            <th>
                                                @php
                                                    $grandTotalTotal = 0;
                                                    foreach ($reportData as $data) {
                                                        $grandTotalTotal +=
                                                            $data['stage_balances']['total'][
                                                                strtolower($size->name)
                                                            ] ?? 0;
                                                    }
                                                    echo $grandTotalTotal;
                                                @endphp
                                            </th>
                                        @endforeach
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
