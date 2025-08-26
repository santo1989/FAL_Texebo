<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Size Inforomation
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

                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            {{-- size Table goes here --}}
                            <table class="table table-bordered">
                                <tr>
                                    <th>Name</th>
                                    <td>{{ $size->name }}</td>
                                </tr>

                            </table>

                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->

                    <button class="btn btn-outline-secondary my-1 mx-1 inline btn-sm"
                        onclick="window.location='{{ route('sizes.edit', ['size' => $size->id]) }}'">
                        <i class="bi bi-pencil"></i> Edit
                    </button>

                    <button class="btn btn-outline-secondary my-1 mx-1 inline btn-sm"
                        onclick="window.location='{{ route('sizes.index') }}'">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid -->
    </section>
</x-backend.layouts.master>
