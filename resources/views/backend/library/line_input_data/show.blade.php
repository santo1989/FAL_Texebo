<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Line Input Data Details
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Line Input Data Details </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('line_input_data.index') }}">Line Input</a></li>
            <li class="breadcrumb-item active">Details</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Details for {{ $lineInputDatum->productCombination->style->name }} - {{ $lineInputDatum->productCombination->color->name }}</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-3">Date</dt>
                                <dd class="col-sm-9">{{ $lineInputDatum->date }}</dd>

                                <dt class="col-sm-3">Buyer</dt>
                                <dd class="col-sm-9">{{ $lineInputDatum->productCombination->buyer->name ?? 'N/A' }}</dd>

                                <dt class="col-sm-3">Style</dt>
                                <dd class="col-sm-9">{{ $lineInputDatum->productCombination->style->name ?? 'N/A' }}</dd>

                                <dt class="col-sm-3">Color</dt>
                                <dd class="col-sm-9">{{ $lineInputDatum->productCombination->color->name ?? 'N/A' }}</dd>

                                <dt class="col-sm-3">Input Quantities</dt>
                                <dd class="col-sm-9">
                                    <ul class="list-unstyled">
                                        @foreach ($lineInputDatum->input_quantities as $sizeName => $quantity)
                                            <li><strong>{{ $sizeName }}:</strong> {{ $quantity }}</li>
                                        @endforeach
                                    </ul>
                                </dd>

                                <dt class="col-sm-3">Total Input Quantity</dt>
                                <dd class="col-sm-9">{{ $lineInputDatum->total_input_quantity }}</dd>
                                
                                <dt class="col-sm-3">Created At</dt>
                                <dd class="col-sm-9">{{ $lineInputDatum->created_at }}</dd>

                                <dt class="col-sm-3">Updated At</dt>
                                <dd class="col-sm-9">{{ $lineInputDatum->updated_at }}</dd>
                            </dl>
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('line_input_data.index') }}" class="btn btn-primary">Back to List</a>
                            <a href="{{ route('line_input_data.edit', $lineInputDatum->id) }}" class="btn btn-warning">Edit</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>