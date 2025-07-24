<!-- resources/views/backend/library/print_send_data/edit.blade.php -->
<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Print/Embroidery Send Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Print/Emb Send Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('print_send_data.index') }}">Print/Emb Send</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <x-backend.layouts.elements.errors />
    <form action="{{ route('print_send_data.update', $printSendDatum->id) }}" method="post">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" name="date" id="date" class="form-control" 
                        value="{{ old('date', $printSendDatum->date) }}" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Product Combination</label>
                    <input type="text" class="form-control" 
                        value="{{ $printSendDatum->productCombination->buyer->name }} - 
                               {{ $printSendDatum->productCombination->style->name }} - 
                               {{ $printSendDatum->productCombination->color->name }}" readonly>
                    <input type="hidden" name="product_combination_id" value="{{ $printSendDatum->product_combination_id }}">
                </div>
            </div>
        </div>

        <div class="alert alert-info">
            <strong>Total Available: </strong> <span id="available-quantity">{{ $available + $printSendDatum->total_send_quantity }}</span>
            (Including current record: {{ $printSendDatum->total_send_quantity }})
        </div>

        <div class="row mb-3">
            @foreach ($sizes as $size)
                <div class="col-md-3 mb-2">
                    <div class="card">
                        <div class="card-body p-2">
                            <strong>{{ strtoupper($size['name']) }}</strong><br>
                            Cut: {{ $size['cut'] }} | Sent: {{ $size['sent'] }}<br>
                            Available: {{ $size['available'] }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card mt-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Send Quantities by Size</h5>
                <div>
                    <strong>Total Quantity: </strong> <span id="total-quantity">{{ $printSendDatum->total_send_quantity }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach ($sizes as $size)
                        <div class="col-md-3 mb-3">
                            <div class="form-group">
                                <label for="quantity_{{ $size['id'] }}">{{ $size['name'] }}</label>
                                <input type="number" 
                                    name="quantities[{{ $size['id'] }}]" 
                                    id="quantity_{{ $size['id'] }}" 
                                    class="form-control quantity-input"
                                    value="{{ old("quantities.{$size['id']}", $size['current_quantity']) }}"
                                    min="0"
                                    max="{{ $size['available'] + $size['current_quantity'] }}"
                                    placeholder="Enter quantity">
                                <small class="text-muted">
                                    Max: {{ $size['available'] + $size['current_quantity'] }}
                                </small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('print_send_data.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete</button>
        </div>
    </form>

    <form id="delete-form" action="{{ route('print_send_data.destroy', $printSendDatum->id) }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quantityInputs = document.querySelectorAll('.quantity-input');
            const totalQuantitySpan = document.getElementById('total-quantity');
            const availableQuantity = {{ $available + $printSendDatum->total_send_quantity }};
            
            function calculateTotalQuantity() {
                let total = 0;
                quantityInputs.forEach(input => {
                    total += parseInt(input.value) || 0;
                });
                totalQuantitySpan.textContent = total;
                
                if (total > availableQuantity) {
                    totalQuantitySpan.parentElement.classList.add('text-danger');
                } else {
                    totalQuantitySpan.parentElement.classList.remove('text-danger');
                }
            }
            
            quantityInputs.forEach(input => {
                input.addEventListener('input', calculateTotalQuantity);
            });
        });

        function confirmDelete() {
            if (confirm('Are you sure you want to delete this record?')) {
                document.getElementById('delete-form').submit();
            }
        }
    </script>
</x-backend.layouts.master>