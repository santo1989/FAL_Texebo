<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Print/Embroidery Send Details
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Print/Emb Send Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('print_send_data.index') }}">Print/Emb Send</a></li>
            <li class="breadcrumb-item active">Details</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Print/Embroidery Send Details</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Date</th>
                                    <td>{{ $printSendDatum->date }}</td>
                                </tr>
                                <tr>
                                    <th>Buyer</th>
                                    <td>{{ $printSendDatum->productCombination->buyer->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Style</th>
                                    <td>{{ $printSendDatum->productCombination->style->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Color</th>
                                    <td>{{ $printSendDatum->productCombination->color->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Sizes & Quantities</th>
                                    <td>
                                        <ul>
                                            @foreach ($printSendDatum->send_quantities as $size => $quantity)
                                                <li><strong>{{ strtoupper($size) }}:</strong> {{ $quantity }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Total Send Quantity</th>
                                    <td>{{ $printSendDatum->total_send_quantity }}</td>
                                </tr>
                            </table>
                            <div class="mt-3">
                                <a href="{{ route('print_send_data.edit', $printSendDatum->id) }}" class="btn btn-primary">Edit</a>
                                <a href="{{ route('print_send_data.index') }}" class="btn btn-secondary">Back to List</a>
                                <form action="{{ route('print_send_data.destroy', $printSendDatum->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this record?');">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>