<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Cutting Data Details
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Cutting Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('cutting_data.index') }}">Cutting Data</a></li>
            <li class="breadcrumb-item active">Show</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Cutting Data Details</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Date</th>
                                    <td>{{ $cuttingDatum->date->format('Y-m-d') }}</td> {{-- Format the date --}}
                                </tr>
                                <tr>
                                    <th>PO</th>
                                    <td>{{ $cuttingDatum->po_number ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Style</th>
                                    <td>{{ $cuttingDatum->productCombination->style->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Color</th>
                                    <td>{{ $cuttingDatum->productCombination->color->name ?? 'N/A' }}</td>
                                </tr>
                            </table>
                            <div class="bg-info text-center">
                                <h3> Details </h3>
                            </div>
                            <table class="table table-bordered table-hover bg-primary">

                                <tr>
                                    @foreach ($allSizes as $size)
                                        <th colspan="2" class="text-center">
                                            {{ strtoupper($size->name) }}
                                        </th>
                                    @endforeach
                                    <th colspan="2">Total Quantities</th>
                                </tr>
                                <tr>

                                    @foreach ($allSizes as $size)
                                        <th>Cut Qty</th>
                                        <th>Waste Qty</th>
                                    @endforeach
                                    <th>Cut</th>
                                    <th>Waste</th>
                                </tr>
                                </tr>
                                @foreach ($allSizes as $size)
                                    <td>{{ $cuttingDatum->cut_quantities[$size->id] ?? 0 }}</td>
                                    <td>{{ $cuttingDatum->cut_waste_quantities[$size->id] ?? 0 }}</td>
                                @endforeach
                                <td>{{ $cuttingDatum->total_cut_quantity }}</td>
                                <td>{{ $cuttingDatum->total_cut_waste_quantity }}</td>


                                </tr>
                            </table>


                            <a href="{{ route('cutting_data.index') }}" class="btn btn-secondary mt-3">Back to List</a>
                            <a href="{{ route('cutting_data.edit', ['cutting_datum' => $cuttingDatum->id]) }}"
                                class="btn btn-primary mt-3">Edit</a>
                            {{-- Delete Button (consider adding a confirmation dialog) --}}
                            <form action="{{ route('cutting_data.destroy', ['cutting_datum' => $cuttingDatum->id]) }}"
                                method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger mt-3"
                                    onclick="return confirm('Are you sure you want to delete this cutting data?');">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>
