<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Create Designation
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Designation </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('designations.index') }}">Designation</a></li>
            <li class="breadcrumb-item active">Create Designation</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>


    <x-backend.layouts.elements.errors />
    <form action="{{ route('designations.store') }}" method="post" enctype="multipart/form-data">
        <div class="pb-3">
            @csrf
        <div class="form-group">
                <label for="division_id">Division Name</label>
                <select name="division_id" id="division_id" class="form-control">
                    <option value="">Select Division</option>
                    @foreach ($divisions as $division)
                        <option value="{{ $division->id }}">{{ $division->name }}</option>
                    @endforeach
                </select>
            </div>
            <br>
            <x-backend.form.input name="name" type="text" label="Designation Name" />

            <x-backend.form.saveButton>Save</x-backend.form.saveButton>
        </div>
    </form>

</x-backend.layouts.master>
