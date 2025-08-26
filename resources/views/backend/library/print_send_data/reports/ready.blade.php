<!-- resources/views/backend/library/print_send_data/reports/ready.blade.php -->
<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Ready to Input Report
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Print/Emb Send Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Ready to Input Report</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title text-center">Ready to Input for Finishing Report</h3>
                            <a href="{{ route('print_send_data.index') }}" class="btn btn-lg btn-outline-danger float-right">
                                <i class="fas fa-arrow-left"></i> Close
                            </a>
                        </div>
                        <div class="card-body" style="overflow-x: auto;">
                            <table class="table table-bordered table-hover text-center">
                                <thead>
                                    <tr>
                                        <th>Style</th>
                                        <th>Color</th>
                                        <th>PO Number(s)</th>
                                        <th>Type</th>
                                        <th>Total Cut</th>
                                        <th>Total Sent</th>
                                        <th>Total Received</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($readyData as $data)
                                        <tr>
                                            <td>{{ $data['style'] }}</td>
                                            <td>{{ $data['color'] }}</td>
                                            <td>{{ $data['po_number'] ?? 'N/A' }}</td> {{-- Display PO Number --}}
                                            <td>{{ $data['type'] }}</td>
                                            <td>{{ $data['total_cut'] }}</td>
                                            <td>{{ $data['total_sent'] }}</td>
                                            <td>{{ $data['total_received'] ?? 0 }}</td> {{-- Display Total Received --}}
                                            <td>{{ $data['status'] ?? 'N/A' }}</td> {{-- Display Status --}}
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No data found for ready to input.</td>
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