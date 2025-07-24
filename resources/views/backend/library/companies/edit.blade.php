<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Company Information
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Company </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('companies.index') }}">Company</a></li>
            <li class="breadcrumb-item active">Edit Company Information</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>


    <x-backend.layouts.elements.errors />
    <form action="{{ route('companies.update', ['company' => $company->id]) }}" method="post"
        enctype="multipart/form-data">
        <div class="pb-3">
            @csrf
            @method('put')
            @php
                $divisions = App\Models\Division::all();
            @endphp

            <div class="form-group">
                <label for="division_id">Division Name</label>
                <select name="division_id" id="division_id" class="form-control">
                    <option value="">Select Division</option>
                    @foreach ($divisions as $division)
                        <option value="{{ $division->id }}"
                            {{ $company->division_id == $division->id ? 'selected' : '' }}>
                            {{ $division->name }}</option>
                    @endforeach
                </select>
            </div>
            <br>
            <x-backend.form.input name="name" type="text" label="Name" :value="$company->name" />
            <br>

            <x-backend.form.saveButton>Save</x-backend.form.saveButton>
        </div>
    </form>


</x-backend.layouts.master>
