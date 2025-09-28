<!-- resources/views/backend/library/print_send_data/reports/ready.blade.php -->
<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Ready to Input Report
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Print/Emb Send Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Ready to Input Report</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title text-center">Ready to Input for Finishing Report</h3>
                            <a href="{{ route('print_send_data.index') }}"
                                class="btn btn-lg btn-outline-danger float-right">
                                <i class="fas fa-arrow-left"></i> Close
                            </a>
                            <form class="d-flex float-right" action="{{ route('print_send_data.report.ready') }}"
                                method="GET">
                                <div class="row g-2">
                                    {{-- ... existing filters (style, color, po, dates, search) ... --}}
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
                                            <a href="{{ route('print_send_data.report.ready') }}"
                                                class="btn btn-outline-secondary">Reset</a>
                                        @endif
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-body" style="overflow-x: auto;">
                            <table class="table table-bordered table-hover text-center">
                                <thead>
                                    <tr>
                                        <th rowspan="2">Style</th>
                                        <th rowspan="2">Color</th>
                                        <th rowspan="2">PO Number(s)</th>
                                        <th rowspan="2">Type</th>
                                        <th colspan="{{ count($allSizes) + 1 }}">Cut Quantities</th>
                                        <th colspan="{{ count($allSizes) + 1 }}">Sent Quantities</th>
                                        <th colspan="{{ count($allSizes) + 1 }}">Received Quantities</th>
                                        <th rowspan="2">Status</th>
                                    </tr>
                                    <tr>
                                        <th>Total</th>
                                        @foreach (array_values($allSizes) as $sizeName)
                                            <th>{{ $sizeName }}</th>
                                        @endforeach
                                        <th>Total</th>
                                        @foreach (array_values($allSizes) as $sizeName)
                                            <th>{{ $sizeName }}</th>
                                        @endforeach
                                        <th>Total</th>
                                        @foreach (array_values($allSizes) as $sizeName)
                                            <th>{{ $sizeName }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($readyData as $data)
                                        <tr>
                                            <td>{{ $data['style'] }}</td>
                                            <td>{{ $data['color'] }}</td>
                                            <td>{{ $data['po_number'] ?? 'N/A' }}</td>
                                            <td>{{ $data['type'] }}</td>

                                            {{-- Cut Quantities --}}
                                            <td>{{ $data['total_cut'] }}</td>
                                            @foreach (array_values($allSizes) as $sizeName)
                                                <td>{{ $data['size_wise_cut'][$sizeName] ?? 0 }}</td>
                                            @endforeach

                                            {{-- Sent Quantities --}}
                                            <td>{{ $data['total_sent'] }}</td>
                                            @foreach (array_values($allSizes) as $sizeName)
                                                <td>{{ $data['size_wise_sent'][$sizeName] ?? 0 }}</td>
                                            @endforeach

                                            {{-- Received Quantities --}}
                                            <td>{{ $data['total_received'] ?? 0 }}</td>
                                            @foreach (array_values($allSizes) as $sizeName)
                                                <td>{{ $data['size_wise_received'][$sizeName] ?? 0 }}</td>
                                            @endforeach

                                            <td>{{ $data['status'] ?? 'N/A' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ 5 + (count($allSizes) * 3) + 3 }}" class="text-center">No data found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
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