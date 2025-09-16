<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Sublimation Print/Embroidery Receive Data Details
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Sublimation Print/Embroidery Receive Data Details </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('sublimation_print_receive_data.index') }}">Print/Emb
                    Receive</a>
            </li>
            <li class="breadcrumb-item active">Details</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Details for {{ $sublimationPrintReceiveDatum->productCombination->style->name }}
                                - {{ $sublimationPrintReceiveDatum->productCombination->color->name }}</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-3">Date</dt>
                                <dd class="col-sm-9">{{ $sublimationPrintReceiveDatum->date }}</dd>

                                <dt class="col-sm-3">PO Number</dt>
                                <dd class="col-sm-9">{{ $sublimationPrintReceiveDatum->po_number }}</dd>

                                <dt class="col-sm-3">Old Order</dt>
                                <dd class="col-sm-9">{{ $sublimationPrintReceiveDatum->old_order }}</dd>

                                <dt class="col-sm-3">Buyer</dt>
                                <dd class="col-sm-9">{{ $sublimationPrintReceiveDatum->productCombination->buyer->name ?? 'N/A' }}
                                </dd>

                                <dt class="col-sm-3">Style</dt>
                                <dd class="col-sm-9">{{ $sublimationPrintReceiveDatum->productCombination->style->name ?? 'N/A' }}
                                </dd>

                                <dt class="col-sm-3">Color</dt>
                                <dd class="col-sm-9">{{ $sublimationPrintReceiveDatum->productCombination->color->name ?? 'N/A' }}
                                </dd>

                                <dt class="col-sm-3">Received Quantities</dt>
                                <dd class="col-sm-9">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Size</th>
                                                <th>Receive Quantity</th>
                                                <th>Waste Quantity</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($allSizes as $size)
                                                <tr>
                                                    <td>{{ $size->name }}</td>
                                                    <td>{{ $sublimationPrintReceiveDatum->sublimation_print_receive_quantities[$size->id] ?? 0 }}</td>
                                                    <td>{{ $sublimationPrintReceiveDatum->sublimation_print_receive_waste_quantities[$size->id] ?? 0 }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </dd>

                                <dt class="col-sm-3">Total Received Quantity</dt>
                                <dd class="col-sm-9">{{ $sublimationPrintReceiveDatum->total_sublimation_print_receive_quantity }}</dd>

                                <dt class="col-sm-3">Total Waste Quantity</dt>
                                <dd class="col-sm-9">{{ $sublimationPrintReceiveDatum->total_sublimation_print_receive_waste_quantity }}</dd>

                                <dt class="col-sm-3">Created At</dt>
                                <dd class="col-sm-9">{{ $sublimationPrintReceiveDatum->created_at }}</dd>

                                <dt class="col-sm-3">Updated At</dt>
                                <dd class="col-sm-9">{{ $sublimationPrintReceiveDatum->updated_at }}</dd>
                            </dl>
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('sublimation_print_receive_data.index') }}" class="btn btn-primary">Back
                                to
                                List</a>
                            @canany(['Admin', 'Supervisor'])
                            <a href="{{ route('sublimation_print_receive_data.edit', $sublimationPrintReceiveDatum->id) }}"
                                class="btn btn-warning">Edit</a>
                            @endcanany
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>