<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Product Combination
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Product Combination </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('product-combinations.index') }}">Product Combinations</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <x-backend.layouts.elements.errors />
    <form action="{{ route('product-combinations.update', $productCombination->id) }}" method="post">
        @csrf
        @method('put')
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="buyer_id">Buyer</label>
                    <select name="buyer_id" id="buyer_id" class="form-control" required>
                        <option value="">Select Buyer</option>
                        @foreach ($buyers as $buyer)
                            <option value="{{ $buyer->id }}" {{ $productCombination->buyer_id == $buyer->id ? 'selected' : '' }}>
                                {{ $buyer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="style_id">Style</label>
                    <select name="style_id" id="style_id" class="form-control" required>
                        <option value="">Select Style</option>
                        @foreach ($styles as $style)
                            <option value="{{ $style->id }}" {{ $productCombination->style_id == $style->id ? 'selected' : '' }}>
                                {{ $style->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="color_id">Color</label>
                    <select name="color_id" id="color_id" class="form-control" required>
                        <option value="">Select Color</option>
                        @foreach ($colors as $color)
                            <option value="{{ $color->id }}" {{ $productCombination->color_id == $color->id ? 'selected' : '' }}>
                                {{ $color->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
          <div class="col-md-6 card mt-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Size Selection</h5>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach ($sizes as $size)
                <div class="col-md-2 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" 
                               name="size_ids[]" 
                               id="size_{{ $size->id }}" 
                               value="{{ $size->id }}"
                               {{ in_array($size->id, (array) $productCombination->size_ids) ? 'checked' : '' }}>
                        <label class="form-check-label" for="size_{{ $size->id }}">
                            {{ $size->name }}
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

        </div>
        <!--back button-->
        <div class="form-group">
            <a href="{{ route('product-combinations.index') }}" class="btn btn-secondary mt-3">Back</a>
            <button type="submit" class="btn btn-primary mt-3">Update</button>
        </div>
    </form>
</x-backend.layouts.master>