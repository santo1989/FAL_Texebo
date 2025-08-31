<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Create Product Combination
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Product Combination </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('product-combinations.index') }}">Product Combinations</a></li>
            <li class="breadcrumb-item active">Create</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <x-backend.layouts.elements.errors />
    <form action="{{ route('product-combinations.store') }}" method="post">
        @csrf
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="buyer_id">Buyer</label>
                    <select name="buyer_id" id="buyer_id" class="form-control" required>
                        {{-- <option value="">Select Buyer</option> --}}
                        @foreach ($buyers as $buyer)
                            <option value="{{ $buyer->id }}">{{ $buyer->name }}</option>
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
                            <option value="{{ $style->id }}">{{ $style->name }}</option>
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
                            <option value="{{ $color->id }}">{{ $color->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
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
                                       checked>
                                <label class="form-check-label" for="size_{{ $size->id }}">
                                    {{ $size->name }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <!--cancel button-->
        <button type="button" class="btn btn-secondary mt-3" onclick="window.history.back();">Cancel</button>
        <button type="submit" class="btn btn-primary mt-3">Create Combinations</button>
    </form>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-select all sizes when page loads
            const checkboxes = document.querySelectorAll('input[name="size_ids[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
        });
    </script>
</x-backend.layouts.master>