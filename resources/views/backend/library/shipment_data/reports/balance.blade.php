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
                    <h3 class="card-title">Production Stage Balance Report</h3>
                    <a href="{{ route('home') }}" class="btn btn-secondary mb-3 float-right">
                        <i class="fas fa-arrow-left"></i> Close
                    </a>
                    <button id="export-excel" class="btn btn-success mb-3 float-left">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </button>
                </div>
                <div class="card-body" style="overflow-x: auto;">
                    <!-- Search Form -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <form method="GET" action="{{ route('shipment_data.report.final_balance') }}">
                                <div class="row">
                                    <div class="col-md-2">
                                        <label>Style</label>
                                        <select name="style_id" class="form-control select2">
                                            <option value="">All Styles</option>
                                            @foreach ($styles as $style)
                                                <option value="{{ $style->id }}"
                                                    {{ $styleId == $style->id ? 'selected' : '' }}>
                                                    {{ $style->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Color</label>
                                        <select name="color_id" class="form-control select2">
                                            <option value="">All Colors</option>
                                            @foreach ($colors as $color)
                                                <option value="{{ $color->id }}"
                                                    {{ $colorId == $color->id ? 'selected' : '' }}>
                                                    {{ $color->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label>PO Number</label>
                                        <select name="po_number" class="form-control select2">
                                            <option value="">All PO Numbers</option>
                                            @foreach ($poNumbers as $poNum)
                                                <option value="{{ $poNum }}"
                                                    {{ $poNumber == $poNum ? 'selected' : '' }}>
                                                    {{ $poNum }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Start Date</label>
                                        <input type="date" name="start_date" class="form-control"
                                            value="{{ $start_date }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label>End Date</label>
                                        <input type="date" name="end_date" class="form-control"
                                            value="{{ $end_date }}">
                                    </div>
                                    <div class="col-md-12 mt-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                        <a href="{{ route('shipment_data.report.final_balance') }}"
                                            class="btn btn-secondary">
                                            <i class="fas fa-refresh"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    @if (empty($groupedData))
                        <div class="alert alert-info">
                            No Balance data available for the selected filters.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm" id="balance-report-table">
                                <thead class="thead-dark">
                                    <tr class="text-center">
                                        <th rowspan="2">PO Number</th>
                                        <th rowspan="2">Style</th>
                                        <th rowspan="2">Color</th>
                                        <th rowspan="2">Size</th>
                                        <th rowspan="2">Order</th>
                                        <th rowspan="2">Cutting</th>
                                        <th colspan="2">Print Send</th>
                                        <th colspan="2">Print Receive</th>
                                        <th colspan="2">Sewing Input</th>
                                        <th colspan="2">Sewing Output</th>
                                        <th colspan="2">Packing</th>
                                        <th colspan="2">Shipment</th>
                                        <th rowspan="2">Ready Goods</th>
                                    </tr>
                                    <tr class="text-center">
                                        <th>Send Qty</th>
                                        <th>Balance</th>
                                        <th>Receive Qty</th>
                                        <th>Balance</th>
                                        <th>Input Qty</th>
                                        <th>Balance</th>
                                        <th>Output Qty</th>
                                        <th>Balance</th>
                                        <th>Packing Qty</th>
                                        <th>Balance</th>
                                        <th>Shipped Qty</th>
                                        <th>Waste Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $allRows = collect($groupedData)->flatMap(function ($group) {
                                            return $group['rows'];
                                        });
                                        
                                        // Calculate grand totals
                                        $grandTotals = [
                                            'order' => $allRows->sum('order'),
                                            'cutting' => $allRows->sum('cutting'),
                                            'print_send' => $allRows->sum('print_send'),
                                            'print_send_balance' => $allRows->sum('print_send_balance'),
                                            'print_receive' => $allRows->sum('print_receive'),
                                            'print_receive_balance' => $allRows->sum('print_receive_balance'),
                                            'sewing_input' => $allRows->sum('sewing_input'),
                                            'sewing_input_balance' => $allRows->sum('sewing_input_balance'),
                                            'finish_output' => $allRows->sum('finish_output'),
                                            'finish_balance' => $allRows->sum('finish_balance'),
                                            'packing' => $allRows->sum('packing'),
                                            'packing_balance' => $allRows->sum('packing_balance'),
                                            'shipment' => $allRows->sum('shipment'),
                                            'shipment_waste' => $allRows->sum('shipment_waste'),
                                            'ready_goods' => $allRows->sum('ready_goods'),
                                        ];
                                    @endphp

                                    <!-- Grand Total Row -->
                                    <tr class="font-weight-bold bg-light">
                                        <td colspan="4" class="text-center">Grand Total</td>
                                        <td class="text-right">{{ number_format($grandTotals['order']) }}</td>
                                        <td class="text-right">{{ number_format($grandTotals['cutting']) }}</td>
                                        <td class="text-right">{{ number_format($grandTotals['print_send']) }}</td>
                                        <td class="text-right">{{ number_format($grandTotals['print_send_balance']) }}</td>
                                        <td class="text-right">{{ number_format($grandTotals['print_receive']) }}</td>
                                        <td class="text-right">{{ number_format($grandTotals['print_receive_balance']) }}</td>
                                        <td class="text-right">{{ number_format($grandTotals['sewing_input']) }}</td>
                                        <td class="text-right">{{ number_format($grandTotals['sewing_input_balance']) }}</td>
                                        <td class="text-right">{{ number_format($grandTotals['finish_output']) }}</td>
                                        <td class="text-right">{{ number_format($grandTotals['finish_balance']) }}</td>
                                        <td class="text-right">{{ number_format($grandTotals['packing']) }}</td>
                                        <td class="text-right">{{ number_format($grandTotals['packing_balance']) }}</td>
                                        <td class="text-right">{{ number_format($grandTotals['shipment']) }}</td>
                                        <td class="text-right">{{ number_format($grandTotals['shipment_waste']) }}</td>
                                        <td class="text-right">{{ number_format($grandTotals['ready_goods']) }}</td>
                                    </tr>

                                    @foreach ($groupedData as $groupKey => $group)
                                        @php
                                            $groupTotals = [
                                                'order' => collect($group['rows'])->sum('order'),
                                                'cutting' => collect($group['rows'])->sum('cutting'),
                                                'print_send' => collect($group['rows'])->sum('print_send'),
                                                'print_send_balance' => collect($group['rows'])->sum('print_send_balance'),
                                                'print_receive' => collect($group['rows'])->sum('print_receive'),
                                                'print_receive_balance' => collect($group['rows'])->sum('print_receive_balance'),
                                                'sewing_input' => collect($group['rows'])->sum('sewing_input'),
                                                'sewing_input_balance' => collect($group['rows'])->sum('sewing_input_balance'),
                                                'finish_output' => collect($group['rows'])->sum('finish_output'),
                                                'finish_balance' => collect($group['rows'])->sum('finish_balance'),
                                                'packing' => collect($group['rows'])->sum('packing'),
                                                'packing_balance' => collect($group['rows'])->sum('packing_balance'),
                                                'shipment' => collect($group['rows'])->sum('shipment'),
                                                'shipment_waste' => collect($group['rows'])->sum('shipment_waste'),
                                                'ready_goods' => collect($group['rows'])->sum('ready_goods'),
                                            ];
                                        @endphp

                                        <!-- Group Total Row -->
                                        <tr class="font-weight-bold" style="background-color: #e9ecef;">
                                            <td colspan="4" class="text-center">
                                                {{ $group['po_number'] }} - {{ $group['style'] }} - {{ $group['color'] }} - Total
                                            </td>
                                            <td class="text-right">{{ number_format($groupTotals['order']) }}</td>
                                            <td class="text-right">{{ number_format($groupTotals['cutting']) }}</td>
                                            <td class="text-right">{{ number_format($groupTotals['print_send']) }}</td>
                                            <td class="text-right">{{ number_format($groupTotals['print_send_balance']) }}</td>
                                            <td class="text-right">{{ number_format($groupTotals['print_receive']) }}</td>
                                            <td class="text-right">{{ number_format($groupTotals['print_receive_balance']) }}</td>
                                            <td class="text-right">{{ number_format($groupTotals['sewing_input']) }}</td>
                                            <td class="text-right">{{ number_format($groupTotals['sewing_input_balance']) }}</td>
                                            <td class="text-right">{{ number_format($groupTotals['finish_output']) }}</td>
                                            <td class="text-right">{{ number_format($groupTotals['finish_balance']) }}</td>
                                            <td class="text-right">{{ number_format($groupTotals['packing']) }}</td>
                                            <td class="text-right">{{ number_format($groupTotals['packing_balance']) }}</td>
                                            <td class="text-right">{{ number_format($groupTotals['shipment']) }}</td>
                                            <td class="text-right">{{ number_format($groupTotals['shipment_waste']) }}</td>
                                            <td class="text-right">{{ number_format($groupTotals['ready_goods']) }}</td>
                                        </tr>

                                        @foreach ($group['rows'] as $index => $row)
                                            <tr>
                                                @if ($index == 0)
                                                    <td rowspan="{{ count($group['rows']) }}">
                                                        {{ $group['po_number'] ?? 'N/A' }}
                                                    </td>
                                                    <td rowspan="{{ count($group['rows']) }}">
                                                        {{ $group['style'] }}
                                                    </td>
                                                    <td rowspan="{{ count($group['rows']) }}">
                                                        {{ $group['color'] }}
                                                    </td>
                                                @endif
                                                <td>{{ $row['size'] }}</td>
                                                <td class="text-right">{{ number_format($row['order']) }}</td>
                                                <td class="text-right">{{ number_format($row['cutting']) }}</td>
                                                <td class="text-right">{{ number_format($row['print_send']) }}</td>
                                                <td class="text-right">{{ number_format($row['print_send_balance']) }}</td>
                                                <td class="text-right">{{ number_format($row['print_receive']) }}</td>
                                                <td class="text-right">{{ number_format($row['print_receive_balance']) }}</td>
                                                <td class="text-right">{{ number_format($row['sewing_input']) }}</td>
                                                <td class="text-right">{{ number_format($row['sewing_input_balance']) }}</td>
                                                <td class="text-right">{{ number_format($row['finish_output']) }}</td>
                                                <td class="text-right">{{ number_format($row['finish_balance']) }}</td>
                                                <td class="text-right">{{ number_format($row['packing']) }}</td>
                                                <td class="text-right">{{ number_format($row['packing_balance']) }}</td>
                                                <td class="text-right">{{ number_format($row['shipment']) }}</td>
                                                <td class="text-right">{{ number_format($row['shipment_waste']) }}</td>
                                                <td class="text-right">{{ number_format($row['ready_goods']) }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination and Export -->
                        <div class="d-flex justify-content-between mt-3">
                            <div>
                                <button id="export-excel" class="btn btn-success">
                                    <i class="fas fa-file-excel"></i> Export to Excel
                                </button>
                            </div>
                            <div>
                                {{ $productCombinations->appends([
                                        'style_id' => $styleId,
                                        'color_id' => $colorId,
                                        'po_number' => $poNumber,
                                        'start_date' => $start_date,
                                        'end_date' => $end_date,
                                    ])->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- Excel Export Library -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
    <script>
        document.getElementById('export-excel').addEventListener('click', function() {
            const originalTable = document.getElementById('balance-report-table');
            const clonedTable = originalTable.cloneNode(true);

            // Process rowspans by duplicating cells
            const rows = clonedTable.getElementsByTagName('tr');
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].querySelectorAll('td, th');
                for (let j = 0; j < cells.length; j++) {
                    const cell = cells[j];
                    if (cell.hasAttribute('rowspan')) {
                        const rowspan = parseInt(cell.getAttribute('rowspan'));
                        if (rowspan > 1) {
                            cell.removeAttribute('rowspan');
                            for (let k = 1; k < rowspan; k++) {
                                if (i + k < rows.length) {
                                    const nextRow = rows[i + k];
                                    const newCell = cell.cloneNode(true);
                                    nextRow.insertBefore(newCell, nextRow.children[j]);
                                }
                            }
                        }
                    }
                }
            }

            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(clonedTable);
            XLSX.utils.book_append_sheet(wb, ws, "Balance Report");

            const dateStr = new Date().toISOString().slice(0, 10);
            const fileName = `balance_report_${dateStr}.xlsx`;

            XLSX.writeFile(wb, fileName);
        });

        // Initialize Select2
        $(document).ready(function () {
            $('.select2').select2({
                placeholder: 'Select an option',
                allowClear: true,
                width: '100%'
            });
        });
    </script>
</x-backend.layouts.master>