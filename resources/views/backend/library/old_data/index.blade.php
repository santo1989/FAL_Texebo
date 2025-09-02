<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Order Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Order Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Order</li>
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
                    <!--back button-->
                    <a href="{{ route('home') }}" class="btn btn-secondary">Back</a>
                    <a href="{{ route('old_data_create') }}" class="btn btn-primary">Add New</a>
                    <a href="{{ route('old_data_index') }}" class="btn btn-secondary">View All</a>

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
                                    {{-- <th>Size (from PC)</th> --}} {{-- Might be irrelevant if quantities are per size --}}
                                    <th>Stage</th>
                                    <th>Quantities (by Size name)</th>
                                    <th>Total Qty</th>
                                    <th>Waste Quantities (by Size name)</th>
                                    <th>Total Waste</th>
                                    {{-- Add more columns as needed, e.g., actions --}}
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($allOldData as $data)
                                    <tr>
                                        <td>{{ $data['id'] }}</td>
                                        <td>{{ $data['date'] }}</td>
                                        <td>{{ $data['po_number'] }}</td>
                                        <td>{{ $data['old_order'] == 'yes' ? 'Yes' : 'No' }}</td>
                                        <td>{{ $data['product_combination_id'] }}</td>
                                        <td>{{ $data['style_name'] }}</td>
                                        <td>{{ $data['color_name'] }}</td>
                                        {{-- <td>{{ $data['size_name'] }}</td> --}}
                                        <td>{{ $data['stage'] }}</td>
                                        <td>
                                            @if (!empty($data['quantities']))
                                                <ul class="list-unstyled mb-0">
                                                    @foreach ($data['quantities'] as $sizeId => $qty)
                                                    @php
                                                        $sizeName = $allSizes->where('id', $sizeId)->first()->name ?? 'Unknown';
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
                                        <td>
                                            @if (!empty($data['waste_quantities']))
                                                <ul class="list-unstyled mb-0">
                                                    @foreach ($data['waste_quantities'] as $sizeId => $wasteQty)
                                                    @php
                                                        $sizeName = $allSizes->where('id', $sizeId)->first()->name ?? 'Unknown';
                                                    @endphp
                                                        <li><strong>{{ $sizeName }}:</strong>
                                                            {{ $wasteQty }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ $data['total_waste_quantity'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center">No old order data found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>


</x-backend.layouts.master>
