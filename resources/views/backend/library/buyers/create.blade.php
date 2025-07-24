<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Create Buyer
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> buyer </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('buyers.index') }}">buyer</a></li>
            <li class="breadcrumb-item active">Create buyer</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>


    <x-backend.layouts.elements.errors />
    <form action="{{ route('buyers.store') }}" method="post" enctype="multipart/form-data">
        <div>
            @csrf
            @php
                $divisions = App\Models\Division::all();
            @endphp
            <div class="form-group">
                <label for="division_id">Division Name</label>
                <select name="division_id" id="division_id" class="form-control">
                    <option value="">Select Division</option>
                    @foreach ($divisions as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            @php
                $companies = App\Models\Company::all();
            @endphp
            <div class="form-group">
                <label for="company_id">Company Name</label>
                <select name="company_id" id="company_id" class="form-control">
                    <option value="">Select Company</option>

                </select>
            </div>
            <br>
            <div class="row">
                <div class="buyer-name-input form-group col-md-4 pb-2">
                    <x-backend.form.input name="name[]" type="text" label="Buyer Name" />
                    <button type="button" class="remove-buyer-btn btn-sm btn-outline-danger ">Remove</button>
                    <button type="button" id="add-buyer-btn" class="btn btn-sm btn-outline-info">Add
                        Buyer</button>
                </div>
                <div id="buyer-names-container" class="form-group col-md-4 pb-2">

                </div>

            </div>
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

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get the container and the add button
            const container = document.getElementById('buyer-names-container');
            const addButton = document.getElementById('add-buyer-btn');

            // Add event listener to the add button
            addButton.addEventListener('click', function() {
                // Create a new input field
                const newInput = document.createElement('div');
                newInput.className = 'buyer-name-input';
                newInput.innerHTML = `
                <x-backend.form.input name="name[]" type="text" label="Buyer Name" />
                            <button type="button" class="remove-buyer-btn btn-sm btn-outline-danger ">Remove</button>
            `;

                // Append the new input to the container
                container.appendChild(newInput);

                // Add event listener to the remove button
                const removeButton = newInput.querySelector('.remove-buyer-btn');
                removeButton.addEventListener('click', function() {
                    container.removeChild(newInput);
                });
            });
        });
    </script>

</x-backend.layouts.master>
