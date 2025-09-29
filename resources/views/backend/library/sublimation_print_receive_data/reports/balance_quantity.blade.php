<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Sublimation Print/Embroidery Balance Quantity Report
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Sublimation Print/Embroidery Balance Quantity Report </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('sublimation_print_receive_data.index') }}">Sublimation Print/Embroidery Receive</a>
            </li>
            <li class="breadcrumb-item active">Sublimation Balance Quantity Report</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Sublimation Print/Embroidery Balance Quantities (Sent - Received)</h3>
                            <a href="{{ route('sublimation_print_receive_data.index') }}"
                                class="btn btn-primary float-right">Back
                                to List</a>
                            <form class="d-flex float-right"
                                action="{{ route('sublimation_print_receive_data.report.balance_quantity') }}"
                                method="GET">

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
                                                request('style_id') ||
                                                request('color_id') ||
                                                request('po_number') ||
                                                request('start_date') ||
                                                request('end_date'))
                                            <a href="{{ route('sublimation_print_receive_data.report.balance_quantity') }}"
                                                class="btn btn-outline-secondary">Reset</a>
                                        @endif
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-hover text-center">
                                <thead>
                                    <tr>
                                        <th rowspan="2">Sl#</th>
                                        <th rowspan="2">Style</th>
                                        <th rowspan="2">Color</th>
                                        @foreach ($allSizes as $size)
                                            <th colspan="3">{{ strtoupper($size->name) }}</th>
                                        @endforeach
                                        <th colspan="3">Totals</th>
                                    </tr>
                                    <tr>
                                        @foreach ($allSizes as $size)
                                            <th>Sent </th>
                                            <th>Received</th>
                                            <th>Balance</th>
                                        @endforeach
                                        <th>Sent</th>
                                        <th>Received</th>
                                        <th>Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($reportData as $data)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $data['style'] }}</td>
                                            <td>{{ $data['color'] }}</td>
                                            @foreach ($allSizes as $size)
                                                <td>{{ $data['sizes'][$size->id]['sent'] ?? 0 }}</td>
                                                <td>{{ $data['sizes'][$size->id]['received'] ?? 0 }}</td>
                                                <td>{{ $data['sizes'][$size->id]['balance'] ?? 0 }}</td>
                                            @endforeach
                                            <td>{{ $data['total_sent'] }}</td>
                                            <td>{{ $data['total_received'] }}</td>
                                            <td>{{ $data['total_balance'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ 3 + (count($allSizes) * 3) + 3 }}" class="text-center">No balance
                                                data found for the selected criteria.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3">Grand Totals</th>
                                        @foreach ($allSizes as $size)
                                            <th>
                                                @php
                                                    $grandSent = 0;
                                                    foreach ($reportData as $d) {
                                                        $grandSent += $d['sizes'][$size->id]['sent'] ?? 0;
                                                    }
                                                    echo $grandSent;
                                                @endphp
                                            </th>
                                            <th>
                                                @php
                                                    $grandReceived = 0;
                                                    foreach ($reportData as $d) {
                                                        $grandReceived += $d['sizes'][$size->id]['received'] ?? 0;
                                                    }
                                                    echo $grandReceived;
                                                @endphp
                                            </th>
                                            <th>
                                                @php
                                                    $grandBalance = 0;
                                                    foreach ($reportData as $d) {
                                                        $grandBalance += $d['sizes'][$size->id]['balance'] ?? 0;
                                                    }
                                                    echo $grandBalance;
                                                @endphp
                                            </th>
                                        @endforeach
                                        <th>
                                            @php
                                                $grandTotalSent = 0;
                                                foreach ($reportData as $d) {
                                                    $grandTotalSent += $d['total_sent'];
                                                }
                                                echo $grandTotalSent;
                                            @endphp
                                        </th>
                                        <th>
                                            @php
                                                $grandTotalReceived = 0;
                                                foreach ($reportData as $d) {
                                                    $grandTotalReceived += $d['total_received'];
                                                }
                                                echo $grandTotalReceived;
                                            @endphp
                                        </th>
                                        <th>
                                            @php
                                                $grandTotalBalance = 0;
                                                foreach ($reportData as $d) {
                                                    $grandTotalBalance += $d['total_balance'];
                                                }
                                                echo $grandTotalBalance;
                                            @endphp
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
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