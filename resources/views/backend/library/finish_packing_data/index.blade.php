<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Finish Packing Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Finish Packing Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Finish Packing</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            @if (session('message'))
                <div class="alert alert-success">
                    {{ session('message') }}
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <a href="{{ route('home') }}" class="btn btn-lg btn-outline-danger">
                        <i class="fas fa-arrow-left"></i> Close
                    </a>
                    @canany(['Admin', 'Packing', 'Supervisor'])
                    <a href="{{ route('finish_packing_data.create') }}" class="btn btn-lg btn-outline-primary">
                        <i class="fas fa-plus"></i> Add Finish Packing Data
                    </a>
                    @endcanany
                    <a href="{{ route('finish_packing_data.report.total_packing') }}"
                        class="btn btn-lg btn-outline-info">
                        <i class="fas fa-chart-bar"></i> Total Packing Report
                    </a>
                    <a href="{{ route('finish_packing_data.report.sewing_wip') }}"
                        class="btn btn-lg btn-outline-info ml-2">
                        <i class="fas fa-cogs"></i> Sewing WIP Report
                    </a>
                    <a href="{{ route('finish_packing_data.report.balance') }}"
                        class="btn btn-lg btn-outline-info ml-2">
                        <i class="fas fa-balance-scale"></i> Balance Report
                    </a>
                    <form class="d-flex float-right" action="{{ route('finish_packing_data.index') }}" method="GET">
                      
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
                                    <a href="{{ route('finish_packing_data.index') }}"
                                        class="btn btn-outline-secondary">Reset</a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>SL#</th>
                                    <th>Date</th>
                                    <th>PO Number</th>
                                    <th>Buyer</th>
                                    <th>Style</th>
                                    <th>Color</th>
                                    @foreach ($allSizes as $size)
                                        <th>{{ $size->name }}</th>
                                    @endforeach
                                    <th>Total Packed</th>
                                    <th>Total Waste</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($finishPackingData as $key => $data)
                                    <tr>
                                        <td>{{ $finishPackingData->firstItem() + $key }}</td>
                                        <td>{{ \Carbon\Carbon::parse($data->date)->format('d-M-Y') }}</td>
                                        <td>{{ $data->po_number }}</td>
                                        <td>{{ $data->productCombination->buyer->name ?? 'N/A' }}</td>
                                        <td>{{ $data->productCombination->style->name ?? 'N/A' }}</td>
                                        <td>{{ $data->productCombination->color->name ?? 'N/A' }}</td>
                                        @foreach ($allSizes as $size)
                                            <td>{{ $data->packing_quantities[$size->id] ?? 0 }}</td>
                                        @endforeach
                                        <td>{{ $data->total_packing_quantity }}</td>
                                        <td>{{ $data->total_packing_waste_quantity ?? 0 }}</td>
                                        <td>
                                            <a href="{{ route('finish_packing_data.show', $data->id) }}"
                                                class="btn btn-sm btn-info">Show</a>
                                                @canany(['Admin', 'Packing', 'Supervisor'])
                                            <a href="{{ route('finish_packing_data.edit', $data->id) }}"
                                                class="btn btn-sm btn-warning">Edit</a>
                                                
                                            <form action="{{ route('finish_packing_data.destroy', $data->id) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                            @endcanany
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 8 + count($allSizes) }}" class="text-center">No finish packing data found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center">
                        {{ $finishPackingData->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Include SweetAlert JS (if not already in your master layout) --}}
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(url) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You won\'t be able to revert this!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    let form = document.createElement('form');
                    form.method = 'POST';
                    form.action = url;
                    form.innerHTML = `@csrf @method('delete')`; // Add CSRF token and Method Spoofing
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
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


{{-- <x-backend.layouts.master>
    <x-slot name="pageTitle">
        Finish Packing Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Finish Packing Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Finish Packing</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            @if (session('message'))
                <div class="alert alert-success">
                    {{ session('message') }}
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    
                    <a href="{{ route('home') }}" class="btn btn-lg btn-outline-danger">
                        <i class="fas fa-arrow-left"></i> Close
                    </a>
                    <a href="{{ route('finish_packing_data.create') }}" class="btn btn-lg btn-outline-primary">
                        <i class="fas fa-plus"></i> Add Finish Packing Data
                    </a>
                    

                    <a href="{{ route('finish_packing_data.report.total_packing') }}"
                        class="btn btn-lg btn-outline-info">
                        <i class="fas fa-chart-bar"></i> Total Packing Report
                    </a>

                    <a href="{{ route('finish_packing_data.report.sewing_wip') }}"
                        class="btn btn-lg btn-outline-info ml-2">
                        <i class="fas fa-cogs"></i> Sewing WIP Report
                    </a>

                    <a href="{{ route('finish_packing_data.report.balance') }}"
                        class="btn btn-lg btn-outline-info ml-2">
                        <i class="fas fa-balance-scale"></i> Balance Report
                    </a>
                   
                    <form class="d-flex float-right" action="{{ route('finish_packing_data.index') }}" method="GET">
                        <input class="form-control me-2" type="search" name="search"
                            placeholder="Search by Style/Color" value="{{ request('search') }}">
                        <input class="form-control me-2" type="date" name="date" value="{{ request('date') }}">
                        <button class="btn btn-outline-success" type="submit">Search</button>
                        @if (request('search') || request('date'))
                            <a href="{{ route('finish_packing_data.index') }}" class="btn btn-secondary ml-2">Reset</a>
                        @endif
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>SL#</th>
                                    <th>Date</th>
                                    <th>Buyer</th>
                                    <th>Style</th>
                                    <th>Color</th>
                                    @foreach ($allSizes as $size)
                                        <th>{{ $size->name }}</th>
                                    @endforeach
                                    <th>Total Packed</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($finishPackingData as $key => $data)
                                    <tr>
                                        <td>{{ $finishPackingData->firstItem() + $key }}</td>
                                        <td>{{ \Carbon\Carbon::parse($data->date)->format('d-M-Y') }}</td>
                                        <td>{{ $data->productCombination->buyer->name ?? 'N/A' }}</td>
                                        <td>{{ $data->productCombination->style->name ?? 'N/A' }}</td>
                                        <td>{{ $data->productCombination->color->name ?? 'N/A' }}</td>
                                        @foreach ($allSizes as $size)
                                            <td>{{ $data->packing_quantities[$size->name] ?? 0 }}</td>
                                        @endforeach
                                        <td>{{ $data->total_packing_quantity }}</td>
                                        <td>
                                            <a href="{{ route('finish_packing_data.show', $data->id) }}"
                                                class="btn btn-sm btn-info">Show</a>
                                            <a href="{{ route('finish_packing_data.edit', $data->id) }}"
                                                class="btn btn-sm btn-warning">Edit</a>
                                            <form action="{{ route('finish_packing_data.destroy', $data->id) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 6 + count($allSizes) }}" class="text-center">No finish packing
                                            data found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center">
                        {{ $finishPackingData->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

   
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(url) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You won\'t be able to revert this!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    let form = document.createElement('form');
                    form.method = 'POST';
                    form.action = url;
                    form.innerHTML = `@csrf @method('delete')`; // Add CSRF token and Method Spoofing
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</x-backend.layouts.master> --}}
