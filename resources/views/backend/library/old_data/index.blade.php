<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Old Order Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Old Order Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Old Orders</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">All Old Order Data</h4>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <a href="{{ route('home') }}" class="btn btn-secondary">Back</a>
                            <a href="{{ route('old_data_create') }}" class="btn btn-primary">Add New</a>
                            <a href="{{ route('old_data_index') }}" class="btn btn-info">View All</a>
                        </div>
                    </div>
                    <form class="mt-3" action="{{ route('old_data_index') }}" method="GET">
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

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="search_input">Search</label>
                                    <input class="form-control" type="search" name="search" id="search_input"
                                        placeholder="PO/Style/Color" value="{{ request('search') }}">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="per_page">Items per page</label>
                                    <select name="per_page" id="per_page" class="form-control">
                                        <option value="10" {{ request('per_page', $perPage) == 10 ? 'selected' : '' }}>10</option>
                                        <option value="25" {{ request('per_page', $perPage) == 25 ? 'selected' : '' }}>25</option>
                                        <option value="50" {{ request('per_page', $perPage) == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ request('per_page', $perPage) == 100 ? 'selected' : '' }}>100</option>
                                        <option value="{{ $paginatedOldData->total() }}" {{ request('per_page', $perPage) == $paginatedOldData->total() ? 'selected' : '' }}>All</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4 d-flex justify-content-start gap-2">
                                <button class="btn btn-outline-success" type="submit">Filter</button>
                                @if (request()->query()) {{-- Check if any query parameters exist --}}
                                    <a href="{{ route('old_data_index') }}" class="btn btn-outline-secondary">Reset Filters</a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>PO Number</th>
                                    <th>Old Order</th>
                                    <th>Product Combination ID</th>
                                    <th>Style</th>
                                    <th>Color</th>
                                    <th>Stage</th>
                                    <th>Quantities (by Size name)</th>
                                    <th>Total Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paginatedOldData as $data) {{-- Use the paginated variable --}}
                                    <tr>
                                        <td>{{ $data['id'] }}</td>
                                        <td>{{ $data['date'] }}</td>
                                        <td>{{ $data['po_number'] }}</td>
                                        <td>{{ $data['old_order'] == 'yes' ? 'Yes' : 'No' }}</td>
                                        <td>{{ $data['product_combination_id'] }}</td>
                                        <td>{{ $data['style_name'] }}</td>
                                        <td>{{ $data['color_name'] }}</td>
                                        <td>{{ $data['stage'] }}</td>
                                        <td>
                                            @if (!empty($data['quantities']))
                                                <ul class="list-unstyled mb-0">
                                                    @foreach ($data['quantities'] as $sizeId => $qty)
                                                        @php
                                                            $sizeName =
                                                                $allSizes->where('id', $sizeId)->first()->name ??
                                                                'Unknown';
                                                        @endphp
                                                        <li><strong>{{ $sizeName }}:</strong>
                                                            {{ $qty }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ $data['total_quantity'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">No old order data found matching your criteria.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center">
                        {{ $paginatedOldData->links() }} {{-- Render pagination links --}}
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

            // Submit form when per_page changes
            $('#per_page').on('change', function() {
                $(this).closest('form').submit();
            });
        });
    </script>

</x-backend.layouts.master>