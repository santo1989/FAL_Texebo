<!-- resources/views/backend/library/sublimation_print_send_data/reports/total.blade.php -->
<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Total Sublimation Print/Embroidery Send Report
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
                            <h3 class="card-title text-center">Total Sublimation Print/Embroidery Send Report</h3>
                            <a href="{{ route('sublimation_print_send_data.index') }}"
                                class="btn btn-lg btn-outline-danger">
                                <i class="fas fa-arrow-left"></i> Close
                            </a>
                            <div class="card-tools pt-1">
                                <form action="{{ route('sublimation_print_send_data.report.total') }}" method="GET"
                                    class="form-inline">
                                    <label for="start_date" class="mr-2">From:</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control mr-2"
                                        value="{{ request('start_date') }}">
                                    <label for="end_date" class="mr-2">To:</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control mr-2"
                                        value="{{ request('end_date') }}">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="{{ route('sublimation_print_send_data.report.total') }}"
                                        class="btn btn-secondary ml-2">Reset</a>
                                </form>
                            </div>
                        </div>
                        <div class="card-body" style="overflow-x: auto;">
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
                                                <td>{{ $data['sizes'][strtolower($size->name)] ?? 0 }}</td>
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
