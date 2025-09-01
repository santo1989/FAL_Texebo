<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Shipment Data Details
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Shipment Data Details </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('shipment_data.index') }}">Shipment</a></li>
            <li class="breadcrumb-item active">Details</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Shipment Details</h3>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Date</dt>
                        <dd class="col-sm-9">{{ \Carbon\Carbon::parse($shipmentDatum->date)->format('d-M-Y') }}</dd>

                        <dt class="col-sm-3">PO Number</dt>
                        <dd class="col-sm-9">{{ $shipmentDatum->po_number }}</dd>

                        <dt class="col-sm-3">Buyer</dt>
                        <dd class="col-sm-9">{{ $shipmentDatum->productCombination->buyer->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Style</dt>
                        <dd class="col-sm-9">{{ $shipmentDatum->productCombination->style->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Color</dt>
                        <dd class="col-sm-9">{{ $shipmentDatum->productCombination->color->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Total Shipped Quantity</dt>
                        <dd class="col-sm-9">{{ $shipmentDatum->total_shipment_quantity }}</dd>

                        @if($shipmentDatum->total_shipment_waste_quantity)
                        <dt class="col-sm-3">Total Waste Quantity</dt>
                        <dd class="col-sm-9">{{ $shipmentDatum->total_shipment_waste_quantity }}</dd>
                        @endif

                        <dt class="col-sm-12 mt-3">Shipment Quantities by Size:</dt>
                        <dd class="col-sm-12">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Size</th>
                                        <th>Quantity</th>
                                        @if($shipmentDatum->shipment_waste_quantities && count($shipmentDatum->shipment_waste_quantities) > 0)
                                        <th>Waste Quantity</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($allSizes as $size)
                                        @if(isset($shipmentDatum->shipment_quantities[$size->id]) || isset($shipmentDatum->shipment_waste_quantities[$size->id]))
                                        <tr>
                                            <td>{{ $size->name }}</td>
                                            <td>{{ $shipmentDatum->shipment_quantities[$size->id] ?? 0 }}</td>
                                            @if($shipmentDatum->shipment_waste_quantities && count($shipmentDatum->shipment_waste_quantities) > 0)
                                            <td>{{ $shipmentDatum->shipment_waste_quantities[$size->id] ?? 0 }}</td>
                                            @endif
                                        </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </dd>

                        <dt class="col-sm-3">Created At</dt>
                        <dd class="col-sm-9">{{ $shipmentDatum->created_at->format('d-M-Y H:i:s A') }}</dd>

                        <dt class="col-sm-3">Updated At</dt>
                        <dd class="col-sm-9">{{ $shipmentDatum->updated_at->format('d-M-Y H:i:s A') }}</dd>
                    </dl>
                </div>
                <div class="card-footer">
                    <a href="{{ route('shipment_data.index') }}" class="btn btn-secondary">Back to List</a>
                    <a href="{{ route('shipment_data.edit', $shipmentDatum->id) }}" class="btn btn-warning">Edit</a>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>




{{-- <x-backend.layouts.master>
    <x-slot name="pageTitle">
        Shipment Data Details
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Shipment Data Details </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('shipment_data.index') }}">Shipment</a></li>
            <li class="breadcrumb-item active">Details</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Shipment Details</h3>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Date</dt>
                        <dd class="col-sm-9">{{ \Carbon\Carbon::parse($shipmentDatum->date)->format('d-M-Y') }}</dd>

                        <dt class="col-sm-3">Buyer</dt>
                        <dd class="col-sm-9">{{ $shipmentDatum->productCombination->buyer->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Style</dt>
                        <dd class="col-sm-9">{{ $shipmentDatum->productCombination->style->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Color</dt>
                        <dd class="col-sm-9">{{ $shipmentDatum->productCombination->color->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Total Shipped Quantity</dt>
                        <dd class="col-sm-9">{{ $shipmentDatum->total_shipment_quantity }}</dd>

                        <dt class="col-sm-12 mt-3">Shipment Quantities by Size:</dt>
                        <dd class="col-sm-12">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        @foreach(array_keys($shipmentDatum->shipment_quantities) as $sizeName)
                                            <th>{{ ucfirst($sizeName) }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        @foreach($shipmentDatum->shipment_quantities as $sizeName => $quantity)
                                            <td>{{ $quantity }}</td>
                                        @endforeach
                                    </tr>
                                </tbody>
                            </table>
                        </dd>

                        <dt class="col-sm-3">Created At</dt>
                        <dd class="col-sm-9">{{ $shipmentDatum->created_at->format('d-M-Y H:i:s A') }}</dd>

                        <dt class="col-sm-3">Updated At</dt>
                        <dd class="col-sm-9">{{ $shipmentDatum->updated_at->format('d-M-Y H:i:s A') }}</dd>
                    </dl>
                </div>
                <div class="card-footer">
                    <a href="{{ route('shipment_data.index') }}" class="btn btn-secondary">Back to List</a>
                    <a href="{{ route('shipment_data.edit', $shipmentDatum->id) }}" class="btn btn-warning">Edit</a>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master> --}}