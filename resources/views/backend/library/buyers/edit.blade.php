<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Edit Buyer Information
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Buyer </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('buyers.index') }}">Buyer</a></li>
            <li class="breadcrumb-item active">Edit Buyer Information</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>


    <x-backend.layouts.elements.errors />
    <form action="{{ route('buyers.update', ['buyer' => $buyer->id]) }}" method="post" enctype="multipart/form-data">
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
                    @foreach ($divisions as $divisions)
                        <option value="{{ $divisions->id }}"
                            {{ $buyer->division_id == $divisions->id ? 'selected' : '' }}>
                            {{ $divisions->name }}</option>
                    @endforeach
                </select>
            </div>
            <br>

            @php
                $companies = App\Models\Company::all();
            @endphp
            <div class="form-group">
                <label for="company_id">Company Name</label>
                <select name="company_id" id="company_id" class="form-control">
                    <option value="">Select Company</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" {{ $buyer->company_id == $company->id ? 'selected' : '' }}>
                            {{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <br>
            <x-backend.form.input name="name" type="text" label="Name" :value="$buyer->name" />
            <br>

            <x-backend.form.saveButton>Save</x-backend.form.saveButton>
        </div>
    </form>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#division_id').on('change', function() {
                var divisionId = $(this).val();

                if (divisionId) {
                    $.ajax({
                        url: '/get-company-designation/' + divisionId,
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            console.log(data);
                            const companySelect = $('#company_id');

                            companySelect.empty();
                            companySelect.append('<option value="">Select Company</option>');
                            $.each(data.company, function(index, company) {
                                companySelect.append(
                                    `<option value="${company.id}">${company.name}</option>`
                                );
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error(error);
                        }
                    });
                } else {
                    alert('Select a division first for company list');
                }
            });
        });
    </script>


</x-backend.layouts.master>
