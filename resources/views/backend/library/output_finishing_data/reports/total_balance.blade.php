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
                    <!--search form-->
                    <form class="d-flex" action="{{ route('output_finishing_data.report.total_balance') }}" method="GET">
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
                                        <div class="form-group">
                                            <label for="end_date">End Date</label>
                                            <input type="date" name="end_date" id="end_date" class="form-control"
                                                value="{{ request('end_date') }}">
                                        </div>
                                    </div>

                                    <div class="col-md-4 d-flex align-items-end gap-2">
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
                                            <a href="{{ route('output_finishing_data.report.total_balance') }}"
                                                class="btn btn-outline-secondary">Reset</a>
                                        @endif
                                    </div>
                                </div>
                    </form>
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