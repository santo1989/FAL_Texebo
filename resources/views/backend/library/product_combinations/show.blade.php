<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Product Combination Details
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Product Combination </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('product-combinations.index') }}">Product Combinations</a></li>
            <li class="breadcrumb-item active">Show</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Product Combination Details</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Buyer</th>
                                    <td>{{ $productCombination->buyer->name }}</td>
                                </tr>
                                <tr>
                                    <th>Style</th>
                                    <td>{{ $productCombination->style->name }}</td>
                                </tr>
                                <tr>
                                    <th>Color</th>
                                    <td>{{ $productCombination->color->name }}</td>
                                </tr>
                                <tr>
                                    <th>Active</th>
                                    <td>{{ $productCombination->is_active ? 'Yes' : 'No' }}</td>
                                </tr>
                                <tr>
                                    <th>Size</th>
                                    <td>
                                        @foreach ($productCombination->sizes as $size)
                                            <!--json decode-->
                                            {{ $size->name }}{{ !$loop->last ? ',' : '' }}
                                        @endforeach
                                    </td>
                                </tr>
                                
                            </table>
                            <div class="row">
                                <div class="col-md-12">
                                    <a href="{{ route('product-combinations.index') }}" class="btn btn-secondary">Back</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>