<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Input Balance Report
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Line Input Data </x-slot>
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
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Style</th>
                                        <th>Color</th>
                                        @foreach ($allSizes as $size)
                                            <th colspan="3" class="text-center">{{ strtoupper($size->name) }}</th>
                                        @endforeach
                                        <th>Total Available</th>
                                        <th>Total Input</th>
                                        <th>Total Balance</th>
                                    </tr>
                                    <tr>
                                        <th></th>
                                        <th></th>
                                        @foreach ($allSizes as $size)
                                            <th>Available</th>
                                            <th>Input</th>
                                            <th>Balance</th>
                                        @endforeach
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
                                                <td>{{ $data['sizes'][strtolower($size->name)]['available'] ?? 0 }}</td>
                                                <td>{{ $data['sizes'][strtolower($size->name)]['input'] ?? 0 }}</td>
                                                <td>{{ $data['sizes'][strtolower($size->name)]['balance'] ?? 0 }}</td>
                                            @endforeach
                                            <td>{{ $data['total_available'] }}</td>
                                            <td>{{ $data['total_input'] }}</td>
                                            <td>{{ $data['total_balance'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ count($allSizes) * 3 + 5 }}" class="text-center">No data
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
