<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Color Information
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Color </x-slot>

            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('colors.index') }}">Color</a></li>
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
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Name</th>
                                    <td>{{ $color->name }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <button class="btn btn-outline-secondary my-1 mx-1 inline btn-sm"
                        onclick="window.location='{{ route('colors.edit', ['color' => $color->id]) }}'">
                        <i class="bi bi-pencil"></i> Edit
                    </button>

                    <button class="btn btn-outline-secondary my-1 mx-1 inline btn-sm"
                        onclick="window.location='{{ route('colors.index') }}'">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                </div>
            </div>
        </div>
    </section>
</x-backend.layouts.master>