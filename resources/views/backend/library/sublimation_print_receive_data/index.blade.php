<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Sublimation Print/Embroidery Receive Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Sublimation Print/Embroidery Receive Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Print/Emb Receive</li>
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
                            <a href="{{ route('sublimation_print_receive_data.create') }}"
                                class="btn btn-lg btn-outline-primary">
                                <i class="fas fa-plus"></i> Add Print/Emb Receive
                            </a>
                            <div class="btn-group ml-2">
                                <h4 class="btn btn-lg text-center">Reports</h4>
                                <button class="btn btn-lg btn-outline-primary"
                                    onclick="location.href='{{ route('sublimation_print_receive_data.report.total_receive') }}'">Total
                                    Print/Emb Receive</button>
                                <button class="btn btn-lg btn-outline-primary"
                                    onclick="location.href='{{ route('sublimation_print_receive_data.report.balance_quantity') }}'">Print/Emb
                                    Balance</button>
                            </div>

                            <form class="d-flex float-right"
                                action="{{ route('sublimation_print_receive_data.index') }}" method="GET">
                                <input class="form-control me-2" type="search" name="search"
                                    placeholder="Search by Style/Color" value="{{ request('search') }}">
                                <input class="form-control me-2" type="date" name="date"
                                    value="{{ request('date') }}">
                                <button class="btn btn-outline-success" type="submit">Search</button>
                            </form>
                        </div>
                        <div class="card-body" style="overflow-x: auto;">
                            <table class="table table-bordered table-hover text-center">
                                <thead>
                                    <tr>
                                        <th rowspan="2">Date</th>
                                        <th rowspan="2">PO Number</th>
                                        <th rowspan="2">Style</th>
                                        <th rowspan="2">Color</th>
                                        @foreach ($allSizes as $size)
                                            <th colspan="2">{{ strtoupper($size->name) }}</th>
                                        @endforeach
                                        <th rowspan="2">Total Receive</th>
                                        <th rowspan="2">Total Waste</th>
                                        <th rowspan="2">Actions</th>
                                    </tr>
                                    <tr>
                                        @foreach ($allSizes as $size)
                                            <th>Receive</th>
                                            <th>Waste</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($printReceiveData as $data)
                                        <tr>
                                            <td>{{ $data->date }}</td>
                                            <td>{{ $data->po_number }}</td>
                                            <td>{{ $data->productCombination->style->name ?? 'N/A' }}</td>
                                            <td>{{ $data->productCombination->color->name ?? 'N/A' }}</td>
                                            @foreach ($allSizes as $size)
                                                <td>
                                                    {{-- Accessing by size ID --}}
                                                    {{ $data->sublimation_print_receive_quantities[$size->id] ?? 0 }}
                                                   
                                                </td>
                                                <td>
                                                    {{ $data->sublimation_print_receive_waste_quantities[$size->id] ?? 0 }}
                                                </td>
                                            @endforeach
                                            <td>{{ $data->total_sublimation_print_receive_quantity }}</td>
                                            <td>{{ $data->total_sublimation_print_receive_waste_quantity }}</td>
                                            <td>
                                                <a href="{{ route('sublimation_print_receive_data.edit', $data->id) }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <a href="{{ route('sublimation_print_receive_data.show', $data->id) }}"
                                                    class="btn btn-sm btn-outline-info">
                                                    <i class="bi bi-info-circle"></i> Show
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger"
                                                    onclick="confirmDelete('{{ route('sublimation_print_receive_data.destroy', $data->id) }}')">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ 7 + count($allSizes) }}" class="text-center">No print/emb
                                                receive data found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-center">
                                {{ $printReceiveData->appends(request()->query())->links() }}
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
