<x-backend.layouts.master>
    <x-slot name="pageTitle">
        View Sublimation Print/Send Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Sublimation Print/Send Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('sublimation_print_send_data.index') }}">Sublimation Print/Send Data</a></li>
            <li class="breadcrumb-item active">View</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Sublimation Print/Send Details</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <strong>Date:</strong> {{ $sublimationPrintSendDatum->date }}
                </div>
                <div class="col-md-4">
                    <strong>PO Number:</strong> {{ $sublimationPrintSendDatum->po_number }}
                </div>
                <div class="col-md-4">
                    <strong>Old Order:</strong> {{ $sublimationPrintSendDatum->old_order }}
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-4">
                    <strong>Buyer:</strong> {{ $sublimationPrintSendDatum->productCombination->buyer->name ?? 'N/A' }}
                </div>
                <div class="col-md-4">
                    <strong>Style:</strong> {{ $sublimationPrintSendDatum->productCombination->style->name ?? 'N/A' }}
                </div>
                <div class="col-md-4">
                    <strong>Color:</strong> {{ $sublimationPrintSendDatum->productCombination->color->name ?? 'N/A' }}
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <h4>Size-wise Quantities</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Size</th>
                                <th>Send Quantity</th>
                                <th>Waste Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($allSizes as $size)
                                @php
                                    $sizeName = $size->name;
                                    $sizeId = $size->id;
                                    // Ensure we're working with arrays, not objects
                                    $sendQuantities = (array) $sublimationPrintSendDatum->sublimation_print_send_quantities;
                                    $wasteQuantities = (array) $sublimationPrintSendDatum->sublimation_print_send_waste_quantities;
                                    $sendQty = $sendQuantities[$sizeId] ?? 0;
                                    $wasteQty = $wasteQuantities[$sizeId] ?? 0;
                                @endphp
                                <tr>
                                    <td>{{ $sizeName }}</td>
                                    <td>{{ $sendQty }}</td>
                                    <td>{{ $wasteQty }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td><strong>Total</strong></td>
                                <td><strong>{{ $sublimationPrintSendDatum->total_sublimation_print_send_quantity }}</strong></td>
                                <td><strong>{{ $sublimationPrintSendDatum->total_sublimation_print_send_waste_quantity }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <a href="{{ route('sublimation_print_send_data.index') }}" class="btn btn-secondary">Back</a>
            @canany(['Admin', 'Supervisor'])
            <a href="{{ route('sublimation_print_send_data.edit', $sublimationPrintSendDatum->id) }}" class="btn btn-primary">Edit</a>
            @endcanany
        </div>
    </div>
</x-backend.layouts.master>