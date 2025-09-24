<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Sewing WIP Report
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Sewing WIP Report </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Sewing WIP Report</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <!-- search form -->
                    <form class="d-flex" action="{{ route('sewing_wip') }}"
                        method="GET">
                        <div class="row g-2 align-items-end">
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
                                <button class="btn btn-outline-success" type="submit">Filter</button>
                                @if (request('search') ||
                                        request('date') ||
                                        request('style_id') ||
                                        request('color_id') ||
                                        request('po_number') ||
                                        request('start_date') ||
                                        request('end_date'))
                                    <a href="{{ route('sewing_wip') }}"
                                        class="btn btn-outline-secondary">Reset</a>
                                @endif
                            </div>
                        </div>
                    </form>
                    <a href="{{ route('output_finishing_data.index') }}" class="btn btn-secondary mt-3 float-right">
                        <i class="fas fa-arrow-left"></i> Close
                    </a>
                </div>
                <div class="card-body">
                    @if (empty($wipData))
                        <div class="alert alert-info">
                            No sewing WIP data available for the selected filters.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th rowspan="2">Style</th>
                                        <th rowspan="2">Color</th>
                                        @foreach ($allSizes as $size)
                                            <th colspan="3" class="text-center">{{ $size->name }}</th>
                                        @endforeach
                                        <th colspan="3" class="text-center">Total</th>
                                    </tr>
                                    <tr>
                                        @foreach ($allSizes as $size)
                                            <th>Input</th>
                                            <th>Output</th>
                                            <th>Balance</th>
                                        @endforeach
                                        <th>Total Input</th>
                                        <th>Total Output</th>
                                        <th>Total Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        // Initialize grand totals
                                        $grandTotalInput = 0;
                                        $grandTotalOutput = 0;
                                        $grandTotalBalance = 0;
                                        $grandTotalSizeInputs = array_fill_keys($allSizes->pluck('id')->toArray(), 0);
                                        $grandTotalSizeOutputs = array_fill_keys($allSizes->pluck('id')->toArray(), 0);
                                        $grandTotalSizeBalances = array_fill_keys($allSizes->pluck('id')->toArray(), 0);
                                    @endphp
                                    @foreach ($wipData as $data)
                                        @php
                                            $rowTotalInput = 0;
                                            $rowTotalOutput = 0;
                                            $rowTotalBalance = 0;
                                        @endphp
                                        <tr>
                                            <td>{{ $data['style'] }}</td>
                                            <td>{{ $data['color'] }}</td>
                                            @foreach ($allSizes as $size)
                                                @php
                                                    $input = $data['sizes_detail'][$size->id]['input'] ?? 0;
                                                    $output = $data['sizes_detail'][$size->id]['output'] ?? 0;
                                                    $balance = $data['sizes_detail'][$size->id]['balance'] ?? 0;

                                                    $rowTotalInput += $input;
                                                    $rowTotalOutput += $output;
                                                    $rowTotalBalance += $balance;

                                                    $grandTotalSizeInputs[$size->id] += $input;
                                                    $grandTotalSizeOutputs[$size->id] += $output;
                                                    $grandTotalSizeBalances[$size->id] += $balance;
                                                @endphp
                                                <td>{{ $input }}</td>
                                                <td>{{ $output }}</td>
                                                <td>{{ $balance }}</td>
                                            @endforeach
                                            <td>{{ $rowTotalInput }}</td>
                                            <td>{{ $rowTotalOutput }}</td>
                                            <td>{{ $rowTotalBalance }}</td>
                                        </tr>
                                        @php
                                            $grandTotalInput += $rowTotalInput;
                                            $grandTotalOutput += $rowTotalOutput;
                                            $grandTotalBalance += $rowTotalBalance;
                                        @endphp
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2">Grand Total</th>
                                        @foreach ($allSizes as $size)
                                            <th>{{ $grandTotalSizeInputs[$size->id] }}</th>
                                            <th>{{ $grandTotalSizeOutputs[$size->id] }}</th>
                                            <th>{{ $grandTotalSizeBalances[$size->id] }}</th>
                                        @endforeach
                                        <th>{{ $grandTotalInput }}</th>
                                        <th>{{ $grandTotalOutput }}</th>
                                        <th>{{ $grandTotalBalance }}</th>
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