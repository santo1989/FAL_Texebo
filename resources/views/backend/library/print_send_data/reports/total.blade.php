<!-- resources/views/backend/library/print_send_data/reports/total.blade.php -->
<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Total Print/Embroidery Send Report
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Print/Emb Send Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Total Send Report</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title text-center">Total Print/Embroidery Send Report</h3>
                            <a href="{{ route('print_send_data.index') }}" class="btn btn-lg btn-outline-danger">
                                <i class="fas fa-arrow-left"></i> Close
                            </a>
                            <div class="card-tools pt-1">
                                <form action="{{ route('print_send_data.report.total') }}" method="GET"
                                    class="form-inline">
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
                                                <input type="date" name="start_date" id="start_date"
                                                    class="form-control" value="{{ request('start_date') }}">
                                            </div>
                                        </div>

                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="end_date">End Date</label>
                                                <input type="date" name="end_date" id="end_date"
                                                    class="form-control" value="{{ request('end_date') }}">
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
                                                <a href="{{ route('print_send_data.report.total') }}"
                                                    class="btn btn-outline-secondary">Reset</a>
                                            @endif
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="card-body" style="overflow-x: auto;">
                            <table class="table table-bordered table-hover text-center">
                                <thead>
                                    <tr>
                                        <th rowspan="2">Style</th>
                                        <th rowspan="2">Color</th>
                                        @foreach ($allSizes as $size)
                                            <th colspan="2">{{ strtoupper($size->name) }}</th>
                                        @endforeach
                                        <th rowspan="2">Total Send</th>
                                        <th rowspan="2">Total Waste</th>
                                    </tr>
                                    <tr>
                                        @foreach ($allSizes as $size)
                                            <th>Send</th>
                                            <th>Waste</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($reportData as $data)
                                        <tr>
                                            <td>{{ $data['style'] }}</td>
                                            <td>{{ $data['color'] }}</td>
                                            @foreach ($allSizes as $size)
                                                <td>{{ $data['sizes'][$size->id]['send'] ?? 0 }}</td>
                                                <td>{{ $data['sizes'][$size->id]['waste'] ?? 0 }}</td>
                                            @endforeach
                                            <td>{{ $data['total_send'] }}</td>
                                            <td>{{ $data['total_waste'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ 2 + count($allSizes) * 2 + 2 }}" class="text-center">No
                                                report
                                                data found for the selected criteria.</td>
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
