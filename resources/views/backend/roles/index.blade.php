<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Role List
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Role </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Role</a></li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <section class="content">
        <div class="container-fluid">

            @if (session('message'))
                <div class="alert alert-success">
                    <span class="close" data-dismiss="alert">&times;</span>
                    <strong>{{ session('message') }}.</strong>
                </div>
            @endif

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <a href=" {{ route('home') }} " class="btn btn-sm btn-outline-secondary"><i
                                    class="fas fa-arrow-left"></i>
                                Close</a>
                            <a class="btn btn-outline-info btn-sm" href={{ route('roles.create') }}> <i
                                    class="bi bi-plus-circle"></i>
                                Create</a>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            {{-- role Table goes here --}}

                            <table id="datatablesSimple" class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Sl#</th>
                                        <th>Name</th>
                                        <th>Actions</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    @php $sl=0 @endphp
                                    @foreach ($roles as $role)
                                        <tr>
                                            <td>{{ ++$sl }}</td>
                                            <td>{{ $role->name }}</td>
                                            <td>
                                                {{-- <a class="btn btn-primary"
                                                    href={{ route('roles.edit', ['role' => $role->id]) }}>Edit</a>
                                                <form action={{ route('roles.destroy', $role->id) }} method="POST"
                                                    class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-danger" type="submit">Delete</button>
                                                </form> --}}

                                                <x-backend.form.anchor :href="route('roles.edit', ['role' => $role->id])" type="edit" />

                                                {{-- <form style="display:inline"
                                                    action="{{ route('roles.destroy', ['role' => $role->id]) }}"
                                                    method="post">
                                                    @csrf
                                                    @method('delete')

                                                    <button onclick="return confirm('Are you sure want to delete ?')"
                                                        class="btn btn-sm btn-outline-danger" type="submit"><i
                                                            class="bi bi-trash"></i> Delete</button>
                                                </form> --}}


                                                <button class="btn btn-outline-danger my-1 mx-1 inline btn-sm"
                                                    onclick="confirmDelete('{{ route('roles.destroy', ['role' => $role->id]) }}')">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>

                                            </td>
                                        </tr>
                                    @endforeach

                                </tbody>
                            </table>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->


                    <!-- /.card -->
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid -->
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
                    // Submit the form if the user confirms
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
