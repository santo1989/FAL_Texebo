<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Line Input Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Line Input Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Line Input</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <a href="{{ route('home') }}" class="btn btn-lg btn-outline-danger">
                                <i class="fas fa-arrow-left"></i> Close
                            </a>
                            <a href="{{ route('line_input_data.create') }}" class="btn btn-lg btn-outline-primary">
                                <i class="fas fa-plus"></i> Add Line Input
                            </a>
                            <div class="btn-group ml-2">
                                <h4 class="btn btn-lg text-center">Reports</h4>
                                <button class="btn btn-lg btn-outline-primary" onclick="location.href='{{ route('line_input_data.report.total_input') }}'">Total Input</button>
                                <button class="btn btn-lg btn-outline-primary" onclick="location.href='{{ route('line_input_data.report.input_balance') }}'">Input Balance</button>
                            </div>

                            <form class="d-flex float-right" action="{{ route('line_input_data.index') }}" method="GET">
                                <input class="form-control me-2" type="search" name="search"
                                    placeholder="Search by Style/Color" value="{{ request('search') }}">
                                <input class="form-control me-2" type="date" name="date"
                                    value="{{ request('date') }}">
                                <button class="btn btn-outline-success" type="submit">Search</button>
                            </form>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Sl#</th>
                                        <th>Date</th>
                                        <th>PO Number</th>
                                        <th>Buyer</th>
                                        <th>Style</th>
                                        <th>Color</th>
                                        @foreach ($allSizes as $size)
                                            <th>{{ strtoupper($size->name) }}</th>
                                        @endforeach
                                        <th>Total Input</th>
                                        <th>Total Waste</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($lineInputData as $data)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $data->date }}</td>
                                            <td>{{ $data->po_number }}</td>
                                            <td>{{ $data->productCombination->buyer->name ?? 'N/A' }}</td>
                                            <td>{{ $data->productCombination->style->name ?? 'N/A' }}</td>
                                            <td>{{ $data->productCombination->color->name ?? 'N/A' }}</td>
                                            @foreach ($allSizes as $size)
                                                <td>
                                                    @if (isset($data->input_quantities[$size->id]))
                                                        {{ $data->input_quantities[$size->id] }}
                                                        @if (isset($data->input_waste_quantities[$size->id]))
                                                            <br><small class="text-muted">W: {{ $data->input_waste_quantities[$size->id] }}</small>
                                                        @endif
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td>{{ $data->total_input_quantity }}</td>
                                            <td>{{ $data->total_input_waste_quantity ?? 0 }}</td>
                                            <td>
                                                <a href="{{ route('line_input_data.edit', $data->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <a href="{{ route('line_input_data.show', $data->id) }}" class="btn btn-sm btn-outline-info">
                                                    <i class="bi bi-info-circle"></i> Show
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger"
                                                    onclick="confirmDelete('{{ route('line_input_data.destroy', $data->id) }}')">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ 7 + count($allSizes) }}" class="text-center">No line input data found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-center">
                                {{ $lineInputData->appends(request()->query())->links() }}
                            </div>
                        </div>
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
                text: 'This action cannot be undone.',
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
                    form.innerHTML = `@csrf @method('delete')`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</x-backend.layouts.master>