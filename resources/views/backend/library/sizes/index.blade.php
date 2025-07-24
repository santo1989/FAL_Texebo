<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Size List
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Size </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('sizes.index') }}">Size</a></li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">
            @if ($sizes->isEmpty())
                <div class="row">
                    <div class="col-md-12 col-lg-12 col-sm-12">
                        <h1 class="text-danger"> <strong>Currently No Information Available!</strong> </h1>
                    </div>
                </div>
            @else
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <a href="{{ route('home') }}" class="btn btn-lg btn-outline-danger">
                                    <i class="fas fa-arrow-left"></i> Close
                                </a>
                                <x-backend.form.anchor :href="route('sizes.create')" type="create" />
                                
                                <!-- Search Form -->
                                <form class="d-flex float-right" action="{{ route('sizes.index') }}" method="GET">
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
                                            <th>Name</th>
                                            <th>Active</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($sizes as $size)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $size->name }}</td>
                                                <td>
                                                    <form action="{{ route('sizes.active', ['size' => $size->id]) }}" method="POST">
                                                        @csrf
                                                        <button class="btn btn-sm {{ $size->is_active ? 'btn-success' : 'btn-danger' }}">
                                                            {{ $size->is_active ? 'Active' : 'Inactive' }}
                                                        </button>
                                                    </form>
                                                </td>
                                                <td>
                                                    <x-backend.form.anchor :href="route('sizes.edit', ['size' => $size->id])" type="edit" />
                                                    <x-backend.form.anchor :href="route('sizes.show', ['size' => $size->id])" type="show" />
                                                    <button class="btn btn-outline-danger btn-sm" 
                                                        onclick="confirmDelete('{{ route('sizes.destroy', $size->id) }}')">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                
                                <!-- Pagination -->
                                <div class="d-flex justify-content-center mt-3">
                                    {{ $sizes->appends(request()->query())->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
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