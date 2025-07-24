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
                                    <th>Buyer</th>
                                    <td>{{ $cuttingDatum->productCombination->buyer->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Style</th>
                                    <td>{{ $cuttingDatum->productCombination->style->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Color</th>
                                    <td>{{ $cuttingDatum->productCombination->color->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Sizes & Quantities</th>
                                    <td>
                                        <ul>
                                            {{-- Iterate directly over the cut_quantities array --}}
                                            @forelse ($cuttingDatum->cut_quantities as $sizeName => $quantity)
                                                <li><strong>{{ strtoupper($sizeName) }}:</strong> {{ $quantity }}</li>
                                            @empty
                                                <li>No size quantities recorded.</li>
                                            @endforelse
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Total Cut Quantity</th>
                                    <td>{{ $cuttingDatum->total_cut_quantity }}</td>
                                </tr>
                            </table>
                            <a href="{{ route('cutting_data.edit', ['cutting_datum' => $cuttingDatum->id]) }}" class="btn btn-primary mt-3">Edit</a>

                            <a href="{{ route('cutting_data.index') }}" class="btn btn-secondary mt-3">Back to List</a>

                            {{-- Delete Button (consider adding a confirmation dialog) --}}
                            <form action="{{ route('cutting_data.destroy', ['cutting_datum' => $cuttingDatum->id]) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger mt-3" onclick="return confirm('Are you sure you want to delete this cutting data?');">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>