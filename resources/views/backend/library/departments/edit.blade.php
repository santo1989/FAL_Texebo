<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Department Information
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Department </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('departments.index') }}">Department</a></li>
            <li class="breadcrumb-item active">Edit Department Information</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>


    <x-backend.layouts.elements.errors />
    <form action="{{ route('departments.update', ['department' => $department->id]) }}" method="post"
        enctype="multipart/form-data">
        <div class="pb-3">
            @csrf
            @method('put')

            @php
                $companies = App\Models\Company::all();
                $companies = $companies->pluck('name', 'id');
            @endphp

            <div class="form-group">
                <label for="company_id">Company</label>
                <select name="company_id" id="company_id" class="form-control">
                    @foreach ($companies as $key => $value)
                        <option value="{{ $key }}" {{ $department->company_id == $key ? 'selected' : '' }}>{{ $value }}
                        </option>
                    @endforeach
                </select>
            </div>
            <br>
            <x-backend.form.input name="name" type="text" label="Name" :value="$department->name" />
            <br>

            <x-backend.form.saveButton>Save</x-backend.form.saveButton>
        </div>
    </form>


</x-backend.layouts.master>
