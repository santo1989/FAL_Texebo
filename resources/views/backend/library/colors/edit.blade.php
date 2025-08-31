<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Color Information
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Color </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('colors.index') }}">Color</a></li>
            <li class="breadcrumb-item active">Edit Color Information</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>


    <x-backend.layouts.elements.errors />
    <form action="{{ route('colors.update', ['color' => $color->id]) }}" method="post"
        enctype="multipart/form-data">
        <div class="pb-3">
            @csrf
            @method('put')


            <x-backend.form.input name="name" type="text" label="Name" :value="$color->name" required/>
            <br>

            <x-backend.form.saveButton>Save</x-backend.form.saveButton>

            <button class="btn btn-outline-secondary my-1 mx-1 inline btn-sm"
                        onclick="window.location='{{ route('colors.index') }}'">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
        </div>
    </form>
</x-backend.layouts.master>