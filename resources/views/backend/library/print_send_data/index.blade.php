<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Print/Embroidery Send Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Print/Embroidery Send Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Print/Emb Send</li>
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
                            <a href="{{ route('print_send_data.create') }}" class="btn btn-lg btn-outline-primary">
                                <i class="fas fa-plus"></i> Add Print/Emb Send
                            </a>
                            <div class="btn-group ml-2">
                                <h4 class="btn btn-lg text-center">Reports</h4>
                                <div class="dropdown">
                                    <button class="btn btn-lg btn-outline-primary dropdown-toggle" type="button" id="reportsDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        Select Report
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="reportsDropdown">
                                        <a class="dropdown-item" href="{{ route('print_send_data.report.total') }}">Total Print/Emb Send</a>
                                        <a class="dropdown-item" href="{{ route('print_send_data.report.wip') }}">WIP (Waiting)</a>
                                        <a class="dropdown-item" href="{{ route('print_send_data.report.ready') }}">Ready to Input</a>
                                    </div>
                                </div>
                            </div>

                            <form class="d-flex float-right" action="{{ route('print_send_data.index') }}" method="GET">
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
                                            <a href="{{ route('print_send_data.index') }}"
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
                                        <th rowspan="2">Sl#</th>
                                        <th rowspan="2">Date</th>
                                        <th rowspan="2">PO Number</th>
                                        <th rowspan="2">Buyer</th>
                                        <th rowspan="2">Style</th>
                                        <th rowspan="2">Color</th>
                                        @foreach ($allSizes as $size)
                                            <th colspan="2">{{ strtoupper($size->name) }}</th>
                                        @endforeach
                                        <th rowspan="2">Total Send</th>
                                        <th rowspan="2">Total Waste</th>
                                        <th rowspan="2">Actions</th>
                                    </tr>
                                    <tr>
                                        @foreach ($allSizes as $size)
                                            <th>Send</th>
                                            <th>Waste</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($printSendData as $data)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $data->date }}</td>
                                            <td>{{ $data->po_number }}</td>
                                            <td>{{ $data->productCombination->buyer->name ?? 'N/A' }}</td>
                                            <td>{{ $data->productCombination->style->name ?? 'N/A' }}</td>
                                            <td>{{ $data->productCombination->color->name ?? 'N/A' }}</td>
                                            @foreach ($allSizes as $size)
                                                <td>
                                                    {{ $data->send_quantities[$size->id] ?? 0 }}
                                                </td>
                                                <td>
                                                    {{ $data->send_waste_quantities[$size->id] ?? 0 }}
                                                </td>
                                            @endforeach
                                            <td>{{ $data->total_send_quantity }}</td>
                                            <td>{{ $data->total_send_waste_quantity }}</td>
                                            <td>
                                                <a href="{{ route('print_send_data.edit', $data->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <a href="{{ route('print_send_data.show', $data->id) }}" class="btn btn-sm btn-outline-info">
                                                    <i class="bi bi-info-circle"></i> Show
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger"
                                                    onclick="confirmDelete('{{ route('print_send_data.destroy', $data->id) }}')">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ 9 + (count($allSizes) * 2) }}" class="text-center">No print/emb send data found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-center">
                                {{ $printSendData->appends(request()->query())->links() }}
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

        document.addEventListener('DOMContentLoaded', function() {
            const dropdownToggle = document.getElementById('reportsDropdown');
            if (dropdownToggle) {
                dropdownToggle.addEventListener('click', function() {
                    const dropdownMenu = this.nextElementSibling;
                    dropdownMenu.classList.toggle('show');
                });
            }
        });
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