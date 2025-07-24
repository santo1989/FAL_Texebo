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
                        <div class="card-header">
                            <a href="{{ route('home') }}" class="btn btn-lg btn-outline-danger">
                                <i class="fas fa-arrow-left"></i> Close
                            </a>
                            <a href="{{ route('product-combinations.create') }}" class="btn btn-lg btn-outline-primary">
                                <i class="fas fa-plus"></i> Create
                            </a>

                            <form class="d-flex float-right" action="{{ route('product-combinations.index') }}"
                                method="GET">
                                <input class="form-control me-2" type="search" name="search"
                                    placeholder="Search by name" value="{{ request('search') }}">
                                <button class="btn btn-outline-success" type="submit">Search</button>
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
