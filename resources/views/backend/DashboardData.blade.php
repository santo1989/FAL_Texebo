<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Real-time Production Monitoring Dashboard
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader">
                <div class="row">
                    <div class="col-12">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-tachometer-alt me-2"></i>Production Dashboard
                            <small class="text-muted">Real-time Production Monitoring</small>
                        </h1>
                    </div>
                </div>
            </x-slot>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <div class="container-fluid">
        <!-- Key Metrics Overview -->
        <div class="row mb-4">
            <!-- Total Orders -->
            <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2 metric-card" data-metric="total_orders">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Orders
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalOrders">
                                    {{ number_format($ordersData->total_orders ?? 0) }}
                                </div>
                                <div class="mt-2 text-xs">
                                    @foreach ($statuses as $status)
                                        <span class="{{ $status == 'running' ? 'text-success' : 'text-info' }}">
                                            <i class="fas fa-check-circle me-1"></i>
                                            {{ number_format($ordersData->{$status . '_orders'} ?? 0) }}
                                            {{ ucfirst($status) }}
                                        </span><br>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cutting Efficiency -->
            <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2 metric-card" data-metric="cutting_efficiency">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Cutting Efficiency
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $efficiencies['cutting'] }}%
                                </div>
                                <div class="mt-2 text-xs text-muted">
                                    Cut: {{ number_format($cuttingData->total_cut ?? 0) }} |
                                    Waste: {{ number_format($cuttingData->total_cut_waste ?? 0) }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-cut fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sewing Efficiency -->
            <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2 metric-card" data-metric="sewing_efficiency">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Sewing Efficiency
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $efficiencies['sewing'] }}%
                                </div>
                                <div class="mt-2 text-xs text-muted">
                                    In: {{ number_format($sewingData->total_input ?? 0) }} |
                                    Out: {{ number_format($sewingData->total_output ?? 0) }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-tools fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Packing Progress -->
            <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2 metric-card" data-metric="packing_progress">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Packing Progress
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($packingData->total_packed ?? 0) }}
                                </div>
                                <div class="mt-2 text-xs text-muted">
                                    Shipped: {{ number_format($packingData->total_shipped ?? 0) }}
                                    ({{ $efficiencies['packing'] }}%)
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-box fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Waste -->
            <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                <div class="card border-left-danger shadow h-100 py-2 metric-card" data-metric="total_waste">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    Total Waste
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format(array_sum($wasteData)) }}
                                </div>
                                <div class="mt-2 text-xs text-muted">
                                    {{ $ordersData->total_orders > 0 ? round((array_sum($wasteData) / $ordersData->total_orders) * 100, 2) : 0 }}%
                                    of total
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-recycle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Completion Rate -->
            <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                <div class="card border-left-secondary shadow h-100 py-2 metric-card" data-metric="completion_rate">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                    Completion Rate
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $ordersData->total_orders > 0 ? round((($packingData->total_shipped ?? 0) / $ordersData->total_orders) * 100, 1) : 0 }}%
                                </div>
                                <div class="mt-2 text-xs text-muted">
                                    {{ number_format($packingData->total_shipped ?? 0) }}/{{ number_format($ordersData->total_orders ?? 0) }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ready Goods Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-box me-2"></i>Ready Goods Overview - {{ Carbon\Carbon::now()->format('M Y') }}
                        </h6>
                        <form method="GET" action="{{ route('dashboard') }}" class="d-flex align-items-center">
                            <div class="form-group me-2">
                                <select name="month_year" id="month_year" class="form-control">
                                    <option value="">All Months</option>
                                    @foreach ($monthYears as $my)
                                        <option value="{{ $my }}"
                                            {{ $my == request('month_year', Carbon\Carbon::now()->format('Y-m')) ? 'selected' : '' }}>
                                            {{ Carbon\Carbon::createFromFormat('Y-m', $my)->format('M Y') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="btn btn-outline-success btn-sm" type="submit">Filter</button>
                        </form>
                    </div>
                    <div class="card-body">
                        <!-- Ready Goods Cards -->
                        <div class="row mb-4">
                            <!-- Total Ready Goods -->
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    Total Ready Goods
                                                </div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    {{ number_format($cardData['total_ready']) }} Pcs
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-box fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Total by Style -->
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card border-left-success shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                    Total by Style
                                                </div>
                                                <div class="text-xs text-muted">
                                                    @foreach ($cardData['by_style'] as $style => $quantity)
                                                        <div>{{ $style }}: {{ number_format($quantity) }} Pcs</div>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-tshirt fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Total by Color -->
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card border-left-info shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                    Total by Color
                                                </div>
                                                <div class="text-xs text-muted">
                                                    @foreach ($cardData['by_color'] as $color => $quantity)
                                                        <div>{{ $color }}: {{ number_format($quantity) }} Pcs</div>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-palette fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Total by PO Number -->
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card border-left-warning shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                    Total by PO Number
                                                </div>
                                                <div class="text-xs text-muted">
                                                    @foreach ($cardData['by_po_number'] as $po => $quantity)
                                                        <div>{{ $po }}: {{ number_format($quantity) }} Pcs</div>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ready Goods Table -->
                        @if (empty($reportData))
                            <div class="alert alert-info">
                                No ready goods data available for {{ Carbon::now()->format('M Y') }}.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>PO Number</th>
                                            <th>Style</th>
                                            <th>Color</th>
                                            @foreach ($allSizes as $size)
                                                <th>{{ $size->name }}</th>
                                            @endforeach
                                            <th>Total Ready</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($reportData as $data)
                                            <tr>
                                                <td>{{ $data['po_number'] }}</td>
                                                <td>{{ $data['style'] }}</td>
                                                <td>{{ $data['color'] }}</td>
                                                @foreach ($allSizes as $size)
                                                    <td>{{ number_format($data['sizes'][$size->id] ?? 0) }}</td>
                                                @endforeach
                                                <td>{{ number_format($data['total']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3">Grand Total</th>
                                            @foreach ($allSizes as $size)
                                                <th>
                                                    @php
                                                        $totalSizeReady = 0;
                                                        foreach ($reportData as $data) {
                                                            $totalSizeReady += $data['sizes'][$size->id] ?? 0;
                                                        }
                                                        echo number_format($totalSizeReady);
                                                    @endphp
                                                </th>
                                            @endforeach
                                            <th>{{ number_format($cardData['total_ready']) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @endif
                        <div class="mt-3">
                            <a href="{{ route('shipment_data.report.ready_goods') }}" class="btn btn-primary">
                                View Full Ready Goods Report
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Visualizations -->
        <div class="row">
            <!-- Production Flow Chart -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-project-diagram me-2"></i>Monthly Production Flow - {{ date('Y') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">
                            <canvas id="productionFlowChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Waste Distribution -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-pie me-2"></i>Waste Distribution
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-pie pt-4 pb-2">
                            <canvas id="wasteDistributionChart" height="250"></canvas>
                        </div>
                        <div class="mt-4 text-center small">
                            <span class="me-2">
                                <i class="fas fa-circle text-primary"></i> Cutting:
                                {{ number_format($wasteData['cutting']) }}
                            </span>
                            <span class="me-2">
                                <i class="fas fa-circle text-success"></i> Printing:
                                {{ number_format($wasteData['printing']) }}
                            </span>
                            <span class="me-2">
                                <i class="fas fa-circle text-info"></i> Sewing:
                                {{ number_format($wasteData['sewing']) }}
                            </span>
                            <span>
                                <i class="fas fa-circle text-warning"></i> Packing:
                                {{ number_format($wasteData['packing']) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Process Breakdown -->
        <div class="row">
            <!-- Printing Process -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Printing Process
                                </div>
                                <div class="h6 mb-1 font-weight-bold text-gray-800">
                                    Sublimation: {{ number_format($printingData->total_sublimation_sent ?? 0) }} Sent
                                </div>
                                <div class="h6 mb-1 font-weight-bold text-gray-800">
                                    Print/Emb: {{ number_format($printData->total_print_sent ?? 0) }} Sent
                                </div>
                                <div class="progress progress-sm mt-2">
                                    <div class="progress-bar bg-info" role="progressbar"
                                        style="width: {{ ($printingData->total_sublimation_received ?? 0) > 0 ? (($printingData->total_sublimation_received ?? 0) / ($printingData->total_sublimation_sent ?? 1)) * 100 : 0 }}%"
                                        aria-valuenow="{{ ($printingData->total_sublimation_received ?? 0) > 0 ? (($printingData->total_sublimation_received ?? 0) / ($printingData->total_sublimation_sent ?? 1)) * 100 : 0 }}"
                                        aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sewing Process Details -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Sewing Process
                                </div>
                                <div class="h6 mb-1 font-weight-bold text-gray-800">
                                    Input: {{ number_format($sewingData->total_input ?? 0) }}
                                </div>
                                <div class="h6 mb-1 font-weight-bold text-gray-800">
                                    Output: {{ number_format($sewingData->total_output ?? 0) }}
                                </div>
                                <div class="progress progress-sm mt-2">
                                    <div class="progress-bar bg-warning" role="progressbar"
                                        style="width: {{ $efficiencies['sewing'] }}%"
                                        aria-valuenow="{{ $efficiencies['sewing'] }}" aria-valuemin="0"
                                        aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipment Progress Details -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Shipment Progress
                                </div>
                                <div class="h6 mb-1 font-weight-bold text-gray-800">
                                    Packed: {{ number_format($packingData->total_packed ?? 0) }}
                                </div>
                                <div class="h6 mb-1 font-weight-bold text-gray-800">
                                    Shipped: {{ number_format($packingData->total_shipped ?? 0) }}
                                </div>
                                <div class="progress progress-sm mt-2">
                                    <div class="progress-bar bg-success" role="progressbar"
                                        style="width: {{ $efficiencies['packing'] }}%"
                                        aria-valuenow="{{ $efficiencies['packing'] }}" aria-valuemin="0"
                                        aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overall Efficiency -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Overall Efficiency
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $efficiencies['overall'] }}%
                                </div>
                                <div class="mt-2 text-xs text-muted">
                                    <div>Cutting: {{ $efficiencies['cutting'] }}%</div>
                                    <div>Sewing: {{ $efficiencies['sewing'] }}%</div>
                                    <div>Packing: {{ $efficiencies['packing'] }}%</div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities and Quick Actions -->
        <div class="row">
            <!-- Recent Activities -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-bell me-2"></i>Recent Activities
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="activity-feed">
                            @forelse($recentActivities as $activity)
                                <div class="feed-item d-flex align-items-center mb-3 p-2 border rounded">
                                    <div class="feed-icon me-3">
                                        @if ($activity['type'] == 'shipment')
                                            <i class="fas fa-shipping-fast text-success fa-lg"></i>
                                        @elseif($activity['type'] == 'cutting')
                                            <i class="fas fa-cut text-danger fa-lg"></i>
                                        @elseif($activity['type'] == 'printing')
                                            <i class="fas fa-print text-info fa-lg"></i>
                                        @elseif($activity['type'] == 'sewing')
                                            <i class="fas fa-tshirt text-warning fa-lg"></i>
                                        @else
                                            <i class="fas fa-clipboard-list text-primary fa-lg"></i>
                                        @endif
                                    </div>
                                    <div class="feed-content flex-grow-1">
                                        <div class="text-sm font-weight-bold">
                                            {{ ucfirst($activity['type']) }} - {{ $activity['po'] }}
                                            @if (isset($activity['style']))
                                                <small class="text-muted">({{ $activity['style'] }})</small>
                                            @endif
                                        </div>
                                        <div class="text-xs text-muted">
                                            {{ number_format($activity['quantity']) }} Pcs • {{ $activity['time'] }}
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <div>No recent activities</div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <a href="{{ route('order_data.index') }}"
                                    class="btn btn-primary btn-block btn-hover">
                                    <i class="fas fa-plus me-2"></i>New Order
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="{{ route('cutting_data.index') }}"
                                    class="btn btn-success btn-block btn-hover">
                                    <i class="fas fa-cut me-2"></i>Cutting Entry
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="{{ route('line_input_data.index') }}"
                                    class="btn btn-info btn-block btn-hover">
                                    <i class="fas fa-keyboard me-2"></i>Sewing Input
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="{{ route('shipment_data.index') }}"
                                    class="btn btn-warning btn-block btn-hover">
                                    <i class="fas fa-truck me-2"></i>Shipment
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="{{ route('shipment_data.report.final_balance') }}"
                                    class="btn btn-danger btn-block btn-hover">
                                    <i class="fas fa-chart-bar me-2"></i>Reports
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="{{ route('shipment_data.report.ready_goods') }}"
                                    class="btn btn-secondary btn-block btn-hover">
                                    <i class="fas fa-box me-2"></i>Ready Goods Report
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Monthly Data -->
        <div class="modal fade" id="metricModal" tabindex="-1" aria-labelledby="metricModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="metricModalLabel">Monthly Data</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="chart-area mb-4">
                            <canvas id="metricChart" height="300"></canvas>
                        </div>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th id="metricTableHeader">Value</th>
                                </tr>
                            </thead>
                            <tbody id="metricTableBody"></tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .card {
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
        }

        .border-left-primary {
            border-left: 4px solid #4e73df !important;
        }

        .border-left-success {
            border-left: 4px solid #1cc88a !important;
        }

        .border-left-info {
            border-left: 4px solid #36b9cc !important;
        }

        .border-left-warning {
            border-left: 4px solid #f6c23e !important;
        }

        .border-left-danger {
            border-left: 4px solid #e74a3b !important;
        }

        .border-left-secondary {
            border-left: 4px solid #858796 !important;
        }

        .btn-hover {
            transition: all 0.3s ease;
            border-radius: 10px;
            padding: 12px 10px;
        }

        .btn-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .activity-feed .feed-item {
            transition: all 0.3s ease;
        }

        .activity-feed .feed-item:hover {
            background: #f8f9fc;
            transform: translateX(5px);
        }

        .progress {
            border-radius: 10px;
            height: 10px !important;
        }

        .progress-bar {
            border-radius: 10px;
        }

        .table th,
        .table td {
            vertical-align: middle;
            text-align: center;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#month_year').select2({
                placeholder: 'Select Month-Year',
                allowClear: true,
                width: '100%'
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Production Flow Chart
            const flowCtx = document.getElementById('productionFlowChart').getContext('2d');
            const productionFlowChart = new Chart(flowCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode(array_column($monthlyTrends, 'month')) !!},
                    datasets: [{
                            label: 'Orders',
                            data: {!! json_encode(array_column($monthlyTrends, 'orders')) !!},
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Shipments',
                            data: {!! json_encode(array_column($monthlyTrends, 'shipments')) !!},
                            borderColor: '#1cc88a',
                            backgroundColor: 'rgba(28, 200, 138, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });

            // Waste Distribution Chart
            const wasteCtx = document.getElementById('wasteDistributionChart').getContext('2d');
            const wasteDistributionChart = new Chart(wasteCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Cutting', 'Printing', 'Sewing', 'Packing'],
                    datasets: [{
                        data: [
                            {{ $wasteData['cutting'] }},
                            {{ $wasteData['printing'] }},
                            {{ $wasteData['sewing'] }},
                            {{ $wasteData['packing'] }}
                        ],
                        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'],
                        hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a'],
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    cutout: '70%',
                },
            });

            // Initialize modal
            const metricModal = new bootstrap.Modal(document.getElementById('metricModal'));
            let metricChart = null;

            // Handle card clicks
            document.querySelectorAll('.metric-card').forEach(card => {
                card.addEventListener('click', function() {
                    const metric = this.getAttribute('data-metric');
                    fetch('{{ route('dashboard.data') }}')
                        .then(response => response.json())
                        .then(data => {
                            const monthlyData = data.monthly_data;
                            const labels = Object.keys(monthlyData);
                            const values = labels.map(month => monthlyData[month][metric].value);
                            const label = monthlyData[labels[0]][metric].label;
                            const unit = monthlyData[labels[0]][metric].unit;

                            // Update modal title
                            document.getElementById('metricModalLabel').textContent =
                                `Monthly ${label}`;

                            // Update table header
                            document.getElementById('metricTableHeader').textContent =
                                `${label} (${unit})`;

                            // Update table body
                            const tableBody = document.getElementById('metricTableBody');
                            tableBody.innerHTML = labels.map((month, index) => `
                            <tr>
                                <td>${month}</td>
                                <td>${values[index].toLocaleString()}${unit === '%' ? '%' : ''}</td>
                            </tr>
                        `).join('');

                            // Destroy previous chart if exists
                            if (metricChart) {
                                metricChart.destroy();
                            }

                            // Create new chart
                            const metricCtx = document.getElementById('metricChart').getContext('2d');
                            metricChart = new Chart(metricCtx, {
                                type: 'line',
                                data: {
                                    labels: labels,
                                    datasets: [{
                                        label: label,
                                        data: values,
                                        borderColor: '#4e73df',
                                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                                        tension: 0.4,
                                        fill: true
                                    }]
                                },
                                options: {
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: true,
                                            position: 'top'
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                callback: function(value) {
                                                    return value.toLocaleString() + (unit === '%' ? '%' : '');
                                                }
                                            }
                                        }
                                    }
                                }
                            });

                            // Show modal
                            metricModal.show();
                        })
                        .catch(error => console.error('Error fetching monthly data:', error));
                });
            });

            // Auto-refresh dashboard every 2 minutes
            setInterval(() => {
                fetch('{{ route('dashboard.data') }}')
                    .then(response => response.json())
                    .then(data => {
                        // Update key metrics
                        document.getElementById('totalOrders').textContent = data.total_orders.toLocaleString();

                        // Update status metrics
                        const statusContainer = document.querySelector('#totalOrders').parentElement.querySelector('.mt-2.text-xs');
                        statusContainer.innerHTML = data.statuses.map(s => `
                        <span class="${s.status === 'running' ? 'text-success' : 'text-info'}">
                            <i class="fas fa-check-circle me-1"></i>
                            ${s.quantity.toLocaleString()} ${s.status.charAt(0).toUpperCase() + s.status.slice(1)}
                        </span><br>
                    `).join('');

                        // Update charts
                        productionFlowChart.data.datasets[0].data = data.monthly_orders;
                        productionFlowChart.data.datasets[1].data = data.monthly_shipments;
                        productionFlowChart.update();

                        wasteDistributionChart.data.datasets[0].data = data.waste_distribution;
                        wasteDistributionChart.update();
                    })
                    .catch(error => console.error('Error refreshing data:', error));
            }, 120000);

            function animateValue(element, start, end, duration) {
                let startTimestamp = null;
                const step = (timestamp) => {
                    if (!startTimestamp) startTimestamp = timestamp;
                    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                    element.textContent = Math.floor(progress * (end - start) + start).toLocaleString();
                    if (progress < 1) {
                        window.requestAnimationFrame(step);
                    }
                };
                window.requestAnimationFrame(step);
            }

            // Animate key metrics on page load
            setTimeout(() => {
                const totalOrders = {{ $ordersData->total_orders ?? 0 }};
                if (totalOrders > 0) {
                    animateValue(document.getElementById('totalOrders'), 0, totalOrders, 2000);
                }
            }, 500);
        });
    </script>
</x-backend.layouts.master>