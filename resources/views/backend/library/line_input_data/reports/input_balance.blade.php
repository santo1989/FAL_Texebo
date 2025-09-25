<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Input Balance Report
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Sewing Input Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Sewing Input Balance Report</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title text-center">Sewing Input Balance Report</h3>
                            <a href="{{ route('line_input_data.index') }}"
                                class="btn btn-lg btn-outline-danger float-right ml-2">
                                <i class="fas fa-arrow-left"></i> Close
                            </a>
                            <form class="float-right"
                                action="{{ route('line_input_data.report.input_balance') }}" method="GET">
                                <div class="row g-2 align-items-end"> {{-- Added align-items-end for button alignment --}}
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="style_id">Style</label>
                                            <select name="style_id[]" id="style_id" class="form-control select2" multiple>
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
                                            <select name="color_id[]" id="color_id" class="form-control select2" multiple>
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
                                            <select name="po_number[]" id="po_number" class="form-control select2" multiple>
                                                <option value="">Select PO Number</option>
                                                @foreach ($distinctPoNumbers as $poNum) {{-- Changed variable name to avoid conflict --}}
                                                    <option value="{{ $poNum }}"
                                                        {{ in_array($poNum, (array) request('po_number')) ? 'selected' : '' }}>
                                                        {{ $poNum }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="start_date">Start Date (Input)</label>
                                            <input type="date" name="start_date" id="start_date" class="form-control"
                                                value="{{ request('start_date') }}">
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="end_date">End Date (Input)</label>
                                            <input type="date" name="end_date" id="end_date" class="form-control"
                                                value="{{ request('end_date') }}">
                                        </div>
                                    </div>

                                    <div class="col-md-2 d-flex gap-2"> {{-- Removed search input here, added a dedicated search below --}}
                                        <button class="btn btn-outline-success" type="submit">Filter</button>
                                        @if (request()->filled('search') ||
                                                request()->filled('style_id') ||
                                                request()->filled('color_id') ||
                                                request()->filled('po_number') ||
                                                request()->filled('start_date') ||
                                                request()->filled('end_date'))
                                            <a href="{{ route('line_input_data.report.input_balance') }}"
                                                class="btn btn-outline-secondary">Reset</a>
                                        @endif
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="search">Search (Style/Color)</label>
                                            <input class="form-control" type="search" name="search"
                                                placeholder="Search by Style/Color" value="{{ request('search') }}">
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-body" style="overflow-x: auto;">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th rowspan="2">PO Number</th> {{-- Added PO Number column header --}}
                                        <th rowspan="2">Style</th>
                                        <th rowspan="2">Color</th>
                                        @foreach ($allSizes as $size)
                                            <th colspan="3" class="text-center">{{ strtoupper($size->name) }}</th>
                                        @endforeach
                                        <th rowspan="2">Total Available</th>
                                        <th rowspan="2">Total Input</th>
                                        <th rowspan="2">Total Balance</th>
                                    </tr>
                                    <tr>
                                        @foreach ($allSizes as $size)
                                            <th>Available</th>
                                            <th>Input</th>
                                            <th>Balance</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($balanceData as $data)
                                        <tr>
                                            <td>{{ $data['po_number'] }}</td> {{-- Display PO Number --}}
                                            <td>{{ $data['style'] }}</td>
                                            <td>{{ $data['color'] }}</td>
                                            @foreach ($allSizes as $size)
                                                @php
                                                    // Ensure we are getting size data by its ID, not name, for accuracy
                                                    $sizeData = $data['sizes'][$size->id] ?? [
                                                        'available' => 0,
                                                        'input' => 0,
                                                        'waste' => 0, // Ensure waste is initialized
                                                        'balance' => 0,
                                                    ];
                                                @endphp
                                                <td>{{ $sizeData['available'] }}</td>
                                                <td>{{ $sizeData['input'] }}</td>
                                                <td>{{ $sizeData['balance'] }}</td>
                                            @endforeach
                                            <td>{{ $data['total_available'] }}</td>
                                            <td>{{ $data['total_input'] }}</td>
                                            <td>{{ $data['total_balance'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            {{-- Adjusted colspan: 3 base cols + (3 * num_sizes) + 4 totals = 7 + (3 * num_sizes) --}}
                                            <td colspan="{{ 4 + (count($allSizes) * 3) + 3 }}" class="text-center">No data found.</td>
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
            $('.select2').select2({ // Changed to .select2 class
                placeholder: 'Select an option',
                allowClear: true,
                width: '100%'
            });
        });
    </script>
</x-backend.layouts.master>
