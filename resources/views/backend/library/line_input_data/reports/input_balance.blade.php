<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Input Balance Report
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Sewing Input Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Input Balance Report</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title text-center">Input Balance Report</h3>
                            <a href="{{ route('line_input_data.index') }}"
                                class="btn btn-lg btn-outline-danger float-right">
                                <i class="fas fa-arrow-left"></i> Close
                            </a>
                            <form class="d-flex float-right"
                                action="{{ route('line_input_data.report.input_balance') }}" method="GET">
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
                                            <a href="{{ route('line_input_data.report.input_balance') }}"
                                                class="btn btn-outline-secondary">Reset</a>
                                        @endif
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-body" style="overflow-x: auto;">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Style</th>
                                        <th>Color</th>
                                        @foreach ($allSizes as $size)
                                            <th colspan="4" class="text-center">{{ strtoupper($size->name) }}</th>
                                        @endforeach
                                        <th>Total Available</th>
                                        <th>Total Input</th>
                                        <th>Total Waste</th>
                                        <th>Total Balance</th>
                                    </tr>
                                    <tr>
                                        <th></th>
                                        <th></th>
                                        @foreach ($allSizes as $size)
                                            <th>Available</th>
                                            <th>Input</th>
                                            <th>Waste</th>
                                            <th>Balance</th>
                                        @endforeach
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($balanceData as $data)
                                        <tr>
                                            <td>{{ $data['style'] }}</td>
                                            <td>{{ $data['color'] }}</td>
                                            @foreach ($allSizes as $size)
                                                @php
                                                    $sizeData = collect($data['sizes'])->firstWhere(
                                                        'name',
                                                        $size->name,
                                                    ) ?? [
                                                        'available' => 0,
                                                        'input' => 0,
                                                        'waste' => 0,
                                                        'balance' => 0,
                                                    ];
                                                @endphp
                                                <td>{{ $sizeData['available'] }}</td>
                                                <td>{{ $sizeData['input'] }}</td>
                                                <td>{{ $sizeData['waste'] }}</td>
                                                <td>{{ $sizeData['balance'] }}</td>
                                            @endforeach
                                            <td>{{ $data['total_available'] }}</td>
                                            <td>{{ $data['total_input'] }}</td>
                                            <td>{{ $data['total_waste'] }}</td>
                                            <td>{{ $data['total_balance'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ count($allSizes) * 4 + 6 }}" class="text-center">No data
                                                found.</td>
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
</x-backend.layouts.master>
