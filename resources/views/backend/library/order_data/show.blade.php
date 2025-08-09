<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Order Data Details
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Order Data Details </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('order_data.index') }}">Order</a></li>
            <li class="breadcrumb-item active">Details</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Order Details</h3>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Date</dt>
                        <dd class="col-sm-9">{{ \Carbon\Carbon::parse($orderDatum->date)->format('d-M-Y') }}</dd>

                        <dt class="col-sm-3">PO Number</dt>
                        <dd class="col-sm-9">{{ $orderDatum->po_number }}</dd>

                        <dt class="col-sm-3">Buyer</dt>
                        <dd class="col-sm-9">{{ $orderDatum->productCombination->buyer->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Style</dt>
                        <dd class="col-sm-9">{{ $orderDatum->style->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Color</dt>
                        <dd class="col-sm-9">{{ $orderDatum->color->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">PO Status</dt>
                        <dd class="col-sm-9">{{ ucfirst($orderDatum->po_status) }}</dd>

                        <dt class="col-sm-3">Total Order Quantity</dt>
                        <dd class="col-sm-9">{{ $orderDatum->total_order_quantity }}</dd>

                        <dt class="col-sm-12 mt-3">Order Quantities by Size:</dt>
                        <dd class="col-sm-12">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        @foreach(array_keys($orderDatum->order_quantities) as $size)
                                            <th>
                                                @php
                                                    $sizeModel = \App\Models\Size::find($size);
                                                @endphp
                                                {{ $sizeModel->name ?? 'Unknown' }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        @foreach($orderDatum->order_quantities as $sizeName => $quantity)
                                            <td>{{ $quantity }}</td>
                                        @endforeach
                                    </tr>
                                </tbody>
                            </table>
                        </dd>

                        <dt class="col-sm-3">Created At</dt>
                        <dd class="col-sm-9">{{ $orderDatum->created_at->format('d-M-Y H:i:s A') }}</dd>

                        <dt class="col-sm-3">Updated At</dt>
                        <dd class="col-sm-9">{{ $orderDatum->updated_at->format('d-M-Y H:i:s A') }}</dd>
                    </dl>
                </div>
                <div class="card-footer">
                    <a href="{{ route('order_data.index') }}" class="btn btn-secondary">Back to List</a>
                    <a href="{{ route('order_data.edit', $orderDatum->id) }}" class="btn btn-warning">Edit</a>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>