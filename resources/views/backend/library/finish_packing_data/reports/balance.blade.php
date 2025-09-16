<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Balance Report
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Balance Report </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('finish_packing_data.index') }}">Finish Packing</a></li>
            <li class="breadcrumb-item active">Balance Report</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Stage Balance Report (Size Wise)</h3>
                    <a href="{{ route('finish_packing_data.index') }}" class="btn btn-secondary mb-3 float-right">
                        <i class="fas fa-arrow-left"></i> Close
                    </a>
                    <form action="{{ route('finish_packing_data.report.balance') }}" method="GET"
                        class="form-inline float-right mr-2">
                        <div class="row g-2">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="style_id">Style</label>
                                    <select name="style_id[]" id="style_id" class="form-control" multiple>
                                        <option value="">Select Style</option>
                                        @foreach ($allStyles as $style)
                                            <option value="{{ $style->id }}"
                                                {{ in_array($style->id, (array) request('style_id')) ? 'selected' : '' }}>
                                                {{ $style->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="color_id">Color</label>
                                    <select name="color_id[]" id="color_id" class="form-control" multiple>
                                        <option value="">Select Color</option>
                                        @foreach ($allColors as $color)
                                            <option value="{{ $color->id }}"
                                                {{ in_array($color->id, (array) request('color_id')) ? 'selected' : '' }}>
                                                {{ $color->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="po_number">PO Number</label>
                                    <select name="po_number[]" id="po_number" class="form-control" multiple>
                                        <option value="">Select PO Number</option>
                                        @foreach ($distinctPoNumbers as $poNumber)
                                            <option value="{{ $poNumber }}"
                                                {{ in_array($poNumber, (array) request('po_number')) ? 'selected' : '' }}>
                                                {{ $poNumber }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control"
                                        value="{{ request('start_date') }}">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="end_date">End Date</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control"
                                        value="{{ request('end_date') }}">
                                </div>
                            </div>

                            <div class="col-md-2 d-flex align-items-end gap-2">
                                <input class="form-control me-2" type="search" name="search"
                                    placeholder="Search by PO/Style/Color" value="{{ request('search') }}">
                                <button class="btn btn-outline-success" type="submit">Search</button>
                                @if (request('search') ||
                                        request('date') ||
                                        request('style_id') ||
                                        request('color_id') ||
                                        request('po_number') ||
                                        request('start_date') ||
                                        request('end_date'))
                                    <a href="{{ route('finish_packing_data.report.balance') }}"
                                        class="btn btn-outline-secondary">Reset</a>
                                @endif
                            </div>
                        </div>
                    </form>

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
                                            Sewing WIP (Received from Print - Sewing Input)</th>
                                        <th colspan="{{ count($allSizes) }}" class="text-center bg-success text-white">
                                            Packing WIP (Sewing Input - Packed)</th>
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
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
    <script>
        $(document).ready(function() {
            $('#style_id, #color_id, #po_number').select2({
                placeholder: 'Select an option',
                allowClear: true,
                width: '100%'
            });
        });
    </script>
</x-backend.layouts.master>
