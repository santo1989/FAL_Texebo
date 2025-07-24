<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Designation Information
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Designation </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('designations.index') }}">Designation</a></li>
            <li class="breadcrumb-item active">Edit Designation Information</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>


    <x-backend.layouts.elements.errors />
    <form action="{{ route('designations.update', ['designation' => $designation->id]) }}" method="post"
        enctype="multipart/form-data">
        <div class="pb-3">
            @csrf
            @method('put')

            <div class="form-group">
                <label for="division_id">Division Name</label>
                <select name="division_id" id="division_id" class="form-control">
                    <option value="">Select Division</option>
                    @foreach ($divisions as $division)
                        <option value="{{ $division->id }}" {{ $designation->division_id == $division->id ? 'selected' : '' }}>
                            {{ $division->name }}</option>
                    @endforeach
                </select>
            </div>
            <br>
            <x-backend.form.input name="name" type="text" label="Name" :value="$designation->name" />
            <br>

            <x-backend.form.saveButton>Save</x-backend.form.saveButton>
        </div>
    </form>


</x-backend.layouts.master>
