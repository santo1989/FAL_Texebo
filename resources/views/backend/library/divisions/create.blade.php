<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Create Division
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Division </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('divisions.index') }}">Division</a></li>
            <li class="breadcrumb-item active">Create Division</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>


    <x-backend.layouts.elements.errors />
    <form action="{{ route('divisions.store') }}" method="post" enctype="multipart/form-data">
        <div>
            @csrf
            <br>
            <x-backend.form.input name="name" type="text" label="Division Name" />
            <br>
            <x-backend.form.saveButton>Save</x-backend.form.saveButton>



        </div>
    </form>

</x-backend.layouts.master>
