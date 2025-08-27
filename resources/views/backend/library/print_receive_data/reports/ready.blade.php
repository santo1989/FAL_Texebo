<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Ready To Input Report
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Ready To Input Report </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('print_receive_data.index') }}">Print/Emb Send</a></li>
            <li class="breadcrumb-item active">Ready To Input Report</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Quantities Ready For Next Production Stage</h3>
                            <a href="{{ route('print_receive_data.index') }}" class="btn btn-primary float-right">Back
                                to List</a>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Sl#</th>
                                        <th>Style</th>
                                        <th>Color</th>
                                        <th>Type</th>
                                        <th>Total Cut Quantity</th>
                                        <th>Total Sent to Print/Emb</th>
                                        <th>Total Received (Good)</th>
                                        <th>Total Received (Waste)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($readyData as $data)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $data['style'] }}</td>
                                            <td>{{ $data['color'] }}</td>
                                            <td>{{ $data['type'] }}</td>
                                            <td>{{ $data['total_cut'] }}</td>
                                            <td>{{ $data['total_sent'] }}</td>
                                            <td>{{ $data['total_received_good'] }}</td>
                                            <td>{{ $data['total_received_waste'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No data found for items ready for input.
                                            </td>
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