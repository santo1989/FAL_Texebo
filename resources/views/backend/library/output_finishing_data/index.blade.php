<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Output Finishing Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Output Finishing Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Output Finishing</li>
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
                    <a href="{{ route('output_finishing_data.create') }}" class="btn btn-lg btn-outline-primary">
                        <i class="fas fa-plus"></i> Add Output Data
                    </a>
                    <a href="{{ route('output_finishing_data.report.total_balance') }}"
                        class="btn btn-lg btn-outline-info">
                        <i class="fas fa-balance-scale"></i> Total Balance Report
                    </a>
                    <a href="{{ route('sewing_wip') }}"
                        class="btn btn-lg btn-outline-warning">
                        <i class="fas fa-industry"></i> Sewing WIP Report
                    </a>
                    <form class="d-flex float-right" action="{{ route('output_finishing_data.index') }}" method="GET">
                        <input class="form-control me-2" type="search" name="search"
                            placeholder="Search by Style/Color" value="{{ request('search') }}">
                        <input class="form-control me-2" type="date" name="date" value="{{ request('date') }}">
                        <button class="btn btn-outline-success" type="submit">Search</button>
                        @if (request('search') || request('date'))
                            <a href="{{ route('output_finishing_data.index') }}" class="btn btn-secondary ml-2">Reset</a>
                        @endif
                    </form>
                </div>
                <div class="card-body" style="overflow-x: auto;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover text-center">
                            <thead>
                                <tr>
                                    <th rowspan="2">Date</th>
                                    <th rowspan="2">PO Number</th>
                                    <th rowspan="2">Buyer</th>
                                    <th rowspan="2">Style</th>
                                    <th rowspan="2">Color</th>
                                    @foreach ($allSizes as $size)
                                        <th colspan="2">{{ $size->name }}</th>
                                    @endforeach
                                    <th rowspan="2">Total Output</th>
                                    <th rowspan="2">Total Waste</th>
                                    <th rowspan="2">Actions</th>
                                </tr>
                                <tr>
                                    @foreach ($allSizes as $size)
                                        <th>Output</th>
                                        <th>Waste</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($outputFinishingData as $key => $data)
                                    <tr>
                                        
                                        <td>{{ \Carbon\Carbon::parse($data->date)->format('d-M-Y') }}</td>
                                        <td>{{ $data->po_number }}</td>
                                        <td>{{ $data->productCombination->buyer->name ?? 'N/A' }}</td>
                                        <td>{{ $data->productCombination->style->name ?? 'N/A' }}</td>
                                        <td>{{ $data->productCombination->color->name ?? 'N/A' }}</td>
                                        @foreach ($allSizes as $size)
                                            <td>{{ $data->output_quantities[$size->id] ?? 0 }}</td>
                                            <td>{{ $data->output_waste_quantities[$size->id] ?? 0 }}</td>
                                        @endforeach
                                        <td>{{ $data->total_output_quantity }}</td>
                                        <td>{{ $data->total_output_waste_quantity ?? 0 }}</td>
                                        <td>
                                            <a href="{{ route('output_finishing_data.show', $data->id) }}"
                                                class="btn btn-sm btn-info">Show</a>
                                            <a href="{{ route('output_finishing_data.edit', $data->id) }}"
                                                class="btn btn-sm btn-warning">Edit</a>
                                            <form action="{{ route('output_finishing_data.destroy', $data->id) }}"
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
                                        <td colspan="{{ 8 + count($allSizes) }}" class="text-center">No output finishing data found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center">
                        {{ $outputFinishingData->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>

{{-- <x-backend.layouts.master>
    <x-slot name="pageTitle">
        Output Finishing Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Output Finishing Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Output Finishing</li>
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
                    <a href="{{ route('output_finishing_data.create') }}" class="btn btn-lg btn-outline-primary">
                        <i class="fas fa-plus"></i> Add Output Data
                    </a>
                    <a href="{{ route('output_finishing_data.report.total_balance') }}"
                        class="btn btn-lg btn-outline-info">
                        <i class="fas fa-balance-scale"></i> Total Balance Report
                    </a>
                    <form class="d-flex float-right" action="{{ route('output_finishing_data.index') }}" method="GET">
                        <input class="form-control me-2" type="search" name="search"
                            placeholder="Search by Style/Color" value="{{ request('search') }}">
                        <input class="form-control me-2" type="date" name="date" value="{{ request('date') }}">
                        <button class="btn btn-outline-success" type="submit">Search</button>
                        @if (request('search') || request('date'))
                            <a href="{{ route('output_finishing_data.index') }}" class="btn btn-secondary ml-2">Reset</a>
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
                                    <th>Total Output</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($outputFinishingData as $key => $data)
                                    <tr>
                                        <td>{{ $outputFinishingData->firstItem() + $key }}</td>
                                        <td>{{ \Carbon\Carbon::parse($data->date)->format('d-M-Y') }}</td>
                                        <td>{{ $data->productCombination->buyer->name ?? 'N/A' }}</td>
                                        <td>{{ $data->productCombination->style->name ?? 'N/A' }}</td>
                                        <td>{{ $data->productCombination->color->name ?? 'N/A' }}</td>
                                        @foreach ($allSizes as $size)
                                            <td>{{ $data->output_quantities[$size->name] ?? 0 }}</td>
                                        @endforeach
                                        <td>{{ $data->total_output_quantity }}</td>
                                        <td>
                                            <a href="{{ route('output_finishing_data.show', $data->id) }}"
                                                class="btn btn-sm btn-info">Show</a>
                                            <a href="{{ route('output_finishing_data.edit', $data->id) }}"
                                                class="btn btn-sm btn-warning">Edit</a>
                                            <form action="{{ route('output_finishing_data.destroy', $data->id) }}"
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
                                        <td colspan="{{ 6 + count($allSizes) }}" class="text-center">No output finishing data found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center">
                        {{ $outputFinishingData->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master> --}}