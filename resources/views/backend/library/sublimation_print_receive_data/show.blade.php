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
                            <h3 class="card-title">Details for {{ $printReceiveDatum->productCombination->style->name }}
                                - {{ $printReceiveDatum->productCombination->color->name }}</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-3">Date</dt>
                                <dd class="col-sm-9">{{ $printReceiveDatum->date }}</dd>

                                <dt class="col-sm-3">Buyer</dt>
                                <dd class="col-sm-9">{{ $printReceiveDatum->productCombination->buyer->name ?? 'N/A' }}
                                </dd>

                                <dt class="col-sm-3">Style</dt>
                                <dd class="col-sm-9">{{ $printReceiveDatum->productCombination->style->name ?? 'N/A' }}
                                </dd>

                                <dt class="col-sm-3">Color</dt>
                                <dd class="col-sm-9">{{ $printReceiveDatum->productCombination->color->name ?? 'N/A' }}
                                </dd>

                                <dt class="col-sm-3">Received Quantities</dt>
                                <dd class="col-sm-9">
                                    <ul class="list-unstyled">
                                        @foreach ($printReceiveDatum->receive_quantities as $sizeName => $quantity)
                                            <li><strong>{{ $sizeName }}:</strong> {{ $quantity }}</li>
                                        @endforeach
                                    </ul>
                                </dd>

                                <dt class="col-sm-3">Total Received Quantity</dt>
                                <dd class="col-sm-9">{{ $printReceiveDatum->total_receive_quantity }}</dd>

                                <dt class="col-sm-3">Created At</dt>
                                <dd class="col-sm-9">{{ $printReceiveDatum->created_at }}</dd>

                                <dt class="col-sm-3">Updated At</dt>
                                <dd class="col-sm-9">{{ $printReceiveDatum->updated_at }}</dd>
                            </dl>
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('sublimation_print_receive_data.index') }}" class="btn btn-primary">Back
                                to
                                List</a>
                            <a href="{{ route('sublimation_print_receive_data.edit', $printReceiveDatum->id) }}"
                                class="btn btn-warning">Edit</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>
