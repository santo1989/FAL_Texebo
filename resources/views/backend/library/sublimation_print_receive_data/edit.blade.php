<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Sublimation Print/Embroidery Receive Data
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Edit Sublimation Print/Embroidery Receive Data </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('sublimation_print_receive_data.index') }}">Print/Emb
                    Receive</a>
            </li>
            <li class="breadcrumb-item active">Edit</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Edit Sublimation Print/Embroidery Receive Data</h3>
                        </div>
                        <form action="{{ route('sublimation_print_receive_data.update', $printReceiveDatum->id) }}"
                            method="POST">
                            @csrf
                            @method('patch')
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="date">Date</label>
                                    <input type="date" name="date" class="form-control" id="date"
                                        value="{{ old('date', $printReceiveDatum->date) }}" required>
                                    @error('date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Product Combination</label>
                                    <input type="text" class="form-control"
                                        value="{{ $printReceiveDatum->productCombination->style->name }} - {{ $printReceiveDatum->productCombination->color->name }}"
                                        readonly>
                                </div>

                                <div class="form-group">
                                    <label>Receive Quantities by Size</label>
                                    <p class="text-info">Total Sent for this combination: {{ $totalSent }}</p>
                                    <p class="text-info">Total Received So Far (excluding current entry):
                                        {{ $totalReceivedSoFar }}</p>
                                    <p class="text-info">Available to Receive (Total): {{ $availableToReceiveTotal }}
                                    </p>
                                    <div class="row">
                                        @foreach ($sizes as $size)
                                            <div class="col-md-3 mb-3">
                                                <label for="quantity_{{ $size['id'] }}">{{ $size['name'] }} (Sent:
                                                    {{ $size['sent'] }}, Received: {{ $size['received_so_far'] }},
                                                    Available: {{ $size['available_to_receive'] }})</label>
                                                <input type="number" name="quantities[{{ $size['id'] }}]"
                                                    id="quantity_{{ $size['id'] }}" class="form-control"
                                                    value="{{ old('quantities.' . $size['id'], $size['current_quantity']) }}"
                                                    min="0">
                                                @error('quantities.' . $size['id'])
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('total')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Update</button>
                                <a href="{{ route('sublimation_print_receive_data.index') }}"
                                    class="btn btn-danger">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>
