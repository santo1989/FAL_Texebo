<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Output Finishing Data Details
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Output Finishing Data Details </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('output_finishing_data.index') }}">Output Finishing</a></li>
            <li class="breadcrumb-item active">Details</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Output Finishing Details</h3>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Date</dt>
                        <dd class="col-sm-9">{{ \Carbon\Carbon::parse($outputFinishingDatum->date)->format('d-M-Y') }}</dd>

                        <dt class="col-sm-3">Buyer</dt>
                        <dd class="col-sm-9">{{ $outputFinishingDatum->productCombination->buyer->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Style</dt>
                        <dd class="col-sm-9">{{ $outputFinishingDatum->productCombination->style->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Color</dt>
                        <dd class="col-sm-9">{{ $outputFinishingDatum->productCombination->color->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Total Output Quantity</dt>
                        <dd class="col-sm-9">{{ $outputFinishingDatum->total_output_quantity }}</dd>

                        <dt class="col-sm-12 mt-3">Output Quantities by Size:</dt>
                        <dd class="col-sm-12">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        @foreach(array_keys($outputFinishingDatum->output_quantities) as $sizeName)
                                            <th>{{ ucfirst($sizeName) }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        @foreach($outputFinishingDatum->output_quantities as $sizeName => $quantity)
                                            <td>{{ $quantity }}</td>
                                        @endforeach
                                    </tr>
                                </tbody>
                            </table>
                        </dd>

                        <dt class="col-sm-3">Created At</dt>
                        <dd class="col-sm-9">{{ $outputFinishingDatum->created_at->format('d-M-Y H:i:s A') }}</dd>

                        <dt class="col-sm-3">Updated At</dt>
                        <dd class="col-sm-9">{{ $outputFinishingDatum->updated_at->format('d-M-Y H:i:s A') }}</dd>
                    </dl>
                </div>
                <div class="card-footer">
                    <a href="{{ route('output_finishing_data.index') }}" class="btn btn-secondary">Back to List</a>
                    <a href="{{ route('output_finishing_data.edit', $outputFinishingDatum->id) }}" class="btn btn-warning">Edit</a>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>