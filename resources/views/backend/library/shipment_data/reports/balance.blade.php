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
                                    <div class="col-md-3">
                                        <label>Style</label>
                                        <select name="style_id" class="form-control">
                                            <option value="">All Styles</option>
                                            @foreach ($styles as $style)
                                                <option value="{{ $style->id }}"
                                                    {{ $styleId == $style->id ? 'selected' : '' }}>
                                                    {{ $style->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Color</label>
                                        <select name="color_id" class="form-control">
                                            <option value="">All Colors</option>
                                            @foreach ($colors as $color)
                                                <option value="{{ $color->id }}"
                                                    {{ $colorId == $color->id ? 'selected' : '' }}>
                                                    {{ $color->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>PO Number</label>
                                        <input type="text" name="po_number" class="form-control"
                                            value="{{ request('po_number') }}" placeholder="Enter PO Number">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Start Date</label>
                                        <input type="date" name="start_date" class="form-control"
                                            value="{{ $start_date }}">
                                        <label>End Date</label>
                                        <input type="date" name="end_date" class="form-control"
                                            value="{{ $end_date }}">
                                    </div>
                                    <div class="col-md-3" style="margin-top: 30px">
                                        <button type="submit" class="btn btn-primary">Search</button>
                                        <a href="{{ route('shipment_data.report.final_balance') }}"
                                            class="btn btn-secondary">Reset</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    @if (empty($groupedData))
                        <div class="alert alert-info">
                            No Balance data available.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm" id="balance-report-table">
                                <thead>
                                    <tr class="text-center">
                                        <th rowspan="2">PO Number</th>
                                        <th rowspan="2">Style</th>
                                        <th rowspan="2">Color</th>
                                        <th rowspan="2">Size</th>
                                        <th rowspan="1">Order</th>
                                        <th rowspan="1">Cutting</th>
                                        <th colspan="2">Print Send</th>
                                        <th colspan="2">Print Receive</th>
                                        <th colspan="2">Sewing Input</th>
                                        <th colspan="2">Finishing Output</th> <!-- New column -->
                                        <th colspan="2">Packing</th>
                                        <th colspan="2">Shipment</th>
                                        <th rowspan="2">Ready Goods Qty</th>
                                    </tr>
                                    <tr class="text-center">
                                        <th>Qty</th>
                                        <th>Qty</th>
                                        <th>Send Qty</th>
                                        <th>Balance<br>(Cutting-Print Send)</th>
                                        <th>Receive Qty</th>
                                        <th>Balance<br>(Send-Receive)</th>
                                        <th>Input Qty</th>
                                        <th>Balance<br>(Receive-Input)</th>
                                        <th>Output Qty</th> <!-- New column -->
                                        <th>Balance<br>(Input-Output)</th> <!-- New column -->
                                        <th>Packing Qty</th>
                                        <th>Balance<br>(Output-Packing)</th>
                                        <th>Shipped Qty</th>
                                        <th>Waste Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                     @php
                                        $allRows = collect($groupedData)->flatMap(function ($group) {
                                            return $group['rows'];
                                        });
                                    @endphp

                                    <tr class="font-weight-bold">
                                        <td colspan="4" class="text-center">Total</td>
                                        <td class="text-right">{{ $allRows->sum('order') }}</td>
                                        <td class="text-right">{{ $allRows->sum('cutting') }}</td>
                                        <td class="text-right">{{ $allRows->sum('print_send') }}</td>
                                        <td class="text-right">{{ $allRows->sum('print_send_balance') }}</td>
                                        <td class="text-right">{{ $allRows->sum('print_receive') }}</td>
                                        <td class="text-right">{{ $allRows->sum('print_receive_balance') }}</td>
                                        <td class="text-right">{{ $allRows->sum('sewing_input') }}</td>
                                        <td class="text-right">{{ $allRows->sum('sewing_input_balance') }}</td>
                                        <td class="text-right">{{ $allRows->sum('finish_output') }}</td>
                                        <!-- New column -->
                                        <td class="text-right">{{ $allRows->sum('finish_balance') }}</td>
                                        <!-- New column -->
                                        <td class="text-right">{{ $allRows->sum('packing') }}</td>
                                        <td class="text-right">{{ $allRows->sum('packing_balance') }}</td>
                                        <td class="text-right">{{ $allRows->sum('shipment') }}</td>
                                        <td class="text-right">{{ $allRows->sum('shipment_waste') }}</td>
                                        <td class="text-right">{{ $allRows->sum('ready_goods') }}</td>
                                    </tr>
                                    @foreach ($groupedData as $groupKey => $group)
                                        @foreach ($group['rows'] as $index => $row)
                                            <tr>
                                                @if ($index == 0)
                                                    <td rowspan="{{ count($group['rows']) }}">
                                                        {{ $group['po_number'] ?? 'N/A' }}</td>
                                                    <td rowspan="{{ count($group['rows']) }}">{{ $group['style'] }}
                                                    </td>
                                                    <td rowspan="{{ count($group['rows']) }}">{{ $group['color'] }}
                                                    </td>
                                                @endif
                                                <td>{{ $row['size'] }}</td>
                                                <td class="text-right">{{ $row['order'] }}</td>
                                                <td class="text-right">{{ $row['cutting'] }}</td>
                                                <td class="text-right">{{ $row['print_send'] }}</td>
                                                <td class="text-right">{{ $row['print_send_balance'] }}</td>
                                                <td class="text-right">{{ $row['print_receive'] }}</td>
                                                <td class="text-right">{{ $row['print_receive_balance'] }}</td>
                                                <td class="text-right">{{ $row['sewing_input'] }}</td>
                                                <td class="text-right">{{ $row['sewing_input_balance'] }}</td>
                                                <td class="text-right">{{ $row['finish_output'] }}</td>
                                                <!-- New column -->
                                                <td class="text-right">{{ $row['finish_balance'] }}</td>
                                                <!-- New column -->
                                                <td class="text-right">{{ $row['packing'] }}</td>
                                                <td class="text-right">{{ $row['packing_balance'] }}</td>

                                                <td class="text-right">{{ $row['shipment'] }}</td>
                                                <td class="text-right">{{ $row['shipment_waste'] ?? 0 }}</td>
                                                <td class="text-right">{{ $row['ready_goods'] }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach 

                                    <tr class="font-weight-bold">
                                        <td colspan="4" class="text-center">Total</td>
                                        <td class="text-right">{{ $allRows->sum('order') }}</td>
                                        <td class="text-right">{{ $allRows->sum('cutting') }}</td>
                                        <td class="text-right">{{ $allRows->sum('print_send') }}</td>
                                        <td class="text-right">{{ $allRows->sum('print_send_balance') }}</td>
                                        <td class="text-right">{{ $allRows->sum('print_receive') }}</td>
                                        <td class="text-right">{{ $allRows->sum('print_receive_balance') }}</td>
                                        <td class="text-right">{{ $allRows->sum('sewing_input') }}</td>
                                        <td class="text-right">{{ $allRows->sum('sewing_input_balance') }}</td>
                                        <td class="text-right">{{ $allRows->sum('finish_output') }}</td>
                                        <!-- New column -->
                                        <td class="text-right">{{ $allRows->sum('finish_balance') }}</td>
                                        <!-- New column -->
                                        <td class="text-right">{{ $allRows->sum('packing') }}</td>
                                        <td class="text-right">{{ $allRows->sum('packing_balance') }}</td>
                                        <td class="text-right">{{ $allRows->sum('shipment') }}</td>
                                        <td class="text-right">{{ $allRows->sum('shipment_waste') }}</td>
                                        <td class="text-right">{{ $allRows->sum('ready_goods') }}</td>
                                    </tr>
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
                                        'po_number' => request('po_number'),
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
            // Create a new table without rowspans for Excel
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
                            // Duplicate the cell content in subsequent rows
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

            // Create workbook and worksheet
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(clonedTable);

            // Add worksheet to workbook
            XLSX.utils.book_append_sheet(wb, ws, "Balance Report");

            // Generate filename with date
            const dateStr = new Date().toISOString().slice(0, 10);
            const fileName = `balance_report_${dateStr}.xlsx`;

            // Export to Excel
            XLSX.writeFile(wb, fileName);
        });
    </script>
     <script>
        $(document).ready(function () {
            $('#style_id, #color_id, #po_number').select2({
                placeholder: 'Select an option',
                allowClear: true,
                width: '100%'
            });
        });
    </script>
</x-backend.layouts.master>
