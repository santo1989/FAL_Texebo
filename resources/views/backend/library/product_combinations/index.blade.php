<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Product Combinations
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Product Combinations </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Product Combinations</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        {{-- <div class="card-header">
                            <a href="{{ route('home') }}" class="btn btn-lg btn-outline-danger">
                                <i class="fas fa-arrow-left"></i> Close
                            </a>
                            <a href="{{ route('product-combinations.create') }}" class="btn btn-lg btn-outline-primary">
                                <i class="fas fa-plus"></i> Create
                            </a>

                            <form class="d-flex float-right" action="{{ route('product-combinations.index') }}"
                                method="GET">
                                <div class="row">

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="style_id">Style</label>
                                            <select name="style_id[]" id="style_id" class="form-control" multiple>
                                                <option value="">Select Style</option>
                                                @foreach ($allStyles as $style)
                                                    <option value="{{ $style->id }}"
                                                        {{ in_array($style->id, (array) request('style_id')) ? 'selected' : '' }}>
                                                        {{ $style->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="color_id">Color</label>
                                            <select name="color_id[]" id="color_id" class="form-control" multiple>
                                                <option value="">Select Color</option>
                                                @foreach ($allColors as $color)
                                                    <option value="{{ $color->id }}"
                                                        {{ in_array($color->id, (array) request('color_id')) ? 'selected' : '' }}>
                                                        {{ $color->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6 d-flex align-items-end">
                                        <input class="form-control me-2" type="search" name="search"
                                            placeholder="Search by name" value="{{ request('search') }}">
                                        <button class="btn btn-outline-success" type="submit">Search</button>
                                        <a href="{{ route('product-combinations.index') }}"
                                            class="btn btn-outline-secondary">Reset</a>
                                    </div>

                                </div>

                            </form>
                        </div> --}}
                        <div class="card-header d-flex align-items-center flex-wrap justify-content-between">
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('home') }}" class="btn btn-lg btn-outline-danger">
            <i class="fas fa-arrow-left"></i> Close
        </a>
        <a href="{{ route('product-combinations.create') }}" class="btn btn-lg btn-outline-primary">
            <i class="fas fa-plus"></i> Create
        </a>
    </div>

    <form class="d-flex flex-wrap gap-2 mt-2 mt-md-0" action="{{ route('product-combinations.index') }}" method="GET">
        <div class="row g-2">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="style_id">Style</label>
                    <select name="style_id[]" id="style_id" class="form-control" multiple>
                        <option value="">Select Style</option>
                        @foreach ($allStyles as $style)
                            <option value="{{ $style->id }}"
                                {{ in_array($style->id, (array) request('style_id')) ? 'selected' : '' }}>
                                {{ $style->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label for="color_id">Color</label>
                    <select name="color_id[]" id="color_id" class="form-control" multiple>
                        <option value="">Select Color</option>
                        @foreach ($allColors as $color)
                            <option value="{{ $color->id }}"
                                {{ in_array($color->id, (array) request('color_id')) ? 'selected' : '' }}>
                                {{ $color->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-6 d-flex align-items-end">
                <input class="form-control me-2" type="search" name="search"
                    placeholder="Search by name" value="{{ request('search') }}">
                <button class="btn btn-outline-success" type="submit">Search</button>
                <a href="{{ route('product-combinations.index') }}"
                    class="btn btn-outline-secondary">Reset</a>
            </div>
        </div>
    </form>
</div>
                        <div class="card-body">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Sl#</th>
                                        <th>Buyer</th>
                                        <th>Style</th>
                                        <th>Color</th>
                                        <th>Size</th>
                                        <th>Active</th>
                                        <th>Sublimation Print</th>
                                        <th>Print /Embroidered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($combinations as $combination)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $combination->buyer->name }}</td>
                                            <td>{{ $combination->style->name }}</td>
                                            <td>{{ $combination->color->name }}</td>
                                            <td>

                                                @foreach ($combination->size_ids as $sizeId)
                                                    <span class="badge bg-info">
                                                        {{ \App\Models\Size::find($sizeId)?->name }}
                                                    </span>
                                                @endforeach
                                            </td>

                                            <td>
                                                <form
                                                    action="{{ route('product-combinations.active', $combination->id) }}"
                                                    method="POST">
                                                    @csrf
                                                    <button
                                                        class="btn btn-sm {{ $combination->is_active ? 'btn-success' : 'btn-danger' }}">
                                                        {{ $combination->is_active ? 'Active' : 'Inactive' }}
                                                    </button>
                                                </form>
                                            </td>
                                            <!--sublimation print button-->
                                            <td>
                                                <form
                                                    action="{{ route('product-combinations.sublimation_print', $combination->id) }}"
                                                    method="POST">
                                                    @csrf
                                                    <button
                                                        class="btn btn-sm {{ $combination->sublimation_print ? 'btn-success' : 'btn-danger' }}">
                                                        {{ $combination->sublimation_print ? 'Enabled' : 'Disabled' }}
                                                    </button>
                                                </form>
                                            </td>
                                            <!--print embroidery button-->
                                            <td>
                                                <form
                                                    action="{{ route('product-combinations.print_embroidery', $combination->id) }}"
                                                    method="POST">
                                                    @csrf
                                                    <button
                                                        class="btn btn-sm {{ $combination->print_embroidery ? 'btn-success' : 'btn-danger' }}">
                                                        {{ $combination->print_embroidery ? 'Enabled' : 'Disabled' }}
                                                    </button>
                                                </form>
                                            </td>

                                            <td>
                                                <a href="{{ route('product-combinations.edit', $combination->id) }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <a href="{{ route('product-combinations.show', $combination->id) }}"
                                                    class="btn btn-sm btn-outline-info">
                                                    <i class="bi bi-info-circle"></i> Show
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger"
                                                    onclick="confirmDelete('{{ route('product-combinations.destroy', $combination->id) }}')">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No combinations found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-center">
                                {{ $combinations->appends(request()->query())->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(url) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    let form = document.createElement('form');
                    form.method = 'POST';
                    form.action = url;
                    form.innerHTML = `@csrf @method('delete')`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</x-backend.layouts.master>
