<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Cutting Report
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Cutting Report </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Cutting Report</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title text-center">Total Cutting Report</h3>
                            <a href="{{ route('home') }}" class="btn btn-lg btn-outline-danger">
                                <i class="fas fa-arrow-left"></i> Close
                            </a>
                            <div class="card-tools pt-1">
                                <form action="{{ route('cutting_data_report') }}" method="GET" class="form-inline">
                                    <label for="start_date" class="mr-2">From:</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control mr-2"
                                        value="{{ request('start_date') }}">
                                    <label for="end_date" class="mr-2">To:</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control mr-2"
                                        value="{{ request('end_date') }}">

                                    <label for="style_id" class="mr-2">Style:</label>
                                    <select name="style_id" id="style_id" class="form-control mr-2">
                                        <option value="">All Styles</option>
                                        @foreach ($styles as $style)
                                            <option value="{{ $style->id }}"
                                                {{ request('style_id') == $style->id ? 'selected' : '' }}>
                                                {{ $style->name }}</option>
                                        @endforeach
                                    </select>

                                    <label for="color_id" class="mr-2">Color:</label>
                                    <select name="color_id" id="color_id" class="form-control mr-2">
                                        <option value="">All Colors</option>
                                        @foreach ($colors as $color)
                                            <option value="{{ $color->id }}"
                                                {{ request('color_id') == $color->id ? 'selected' : '' }}>
                                                {{ $color->name }}</option>
                                        @endforeach
                                    </select>

                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="{{ route('cutting_data_report') }}"
                                        class="btn btn-secondary ml-2">Reset</a>
                                </form>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Style</th>
                                        <th>Color</th>
                                        @foreach ($allSizes as $size)
                                            <th>{{ strtoupper($size->name) }}</th>
                                        @endforeach
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($reportData as $data)
                                        <tr>
                                            <td>{{ $data['style'] }}</td>
                                            <td>{{ $data['color'] }}</td>
                                            @foreach ($allSizes as $size)
                                                <td>{{ $data['sizes'][strtolower($size->name)] ?? '' }}</td>
                                            @endforeach
                                            <td>{{ $data['total'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ 2 + count($allSizes) + 1 }}" class="text-center">No report
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
</x-backend.layouts.master>
