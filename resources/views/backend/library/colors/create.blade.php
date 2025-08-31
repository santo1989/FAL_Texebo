<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Create Color
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Color </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('colors.index') }}">Color</a></li>
            <li class="breadcrumb-item active">Create Color</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>


    <x-backend.layouts.elements.errors />
    <form action="{{ route('colors.store') }}" method="post" enctype="multipart/form-data">
        <div>
            @csrf
            <!-- Use a wrapper to contain all dynamic inputs -->
            <div id="color-input-wrapper">
                <x-backend.form.input name="name[]" type="text" label="Color Name" required />
            </div>
            <br>

            <button type="button" class="btn btn-secondary p-2 add-more">Add More</button>
            <button type="button" class="btn btn-danger p-2 remove-more">Remove Last</button>
            <x-backend.form.saveButton>Save</x-backend.form.saveButton>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const wrapper = document.getElementById('color-input-wrapper');

            // Template for new input field
            function createInput(index) {
                const div = document.createElement('div');
                div.classList.add('form-group', 'mt-2');

                div.innerHTML = `
                <label for="color_name_${index}">Color Name</label>
                <input type="text" name="name[]" id="color_name_${index}" class="form-control" placeholder="Enter color name" required>
            `;
                return div;
            }

            let count = 1;

            document.querySelector('.add-more').addEventListener('click', function() {
                wrapper.appendChild(createInput(count++));
            });

            document.querySelector('.remove-more').addEventListener('click', function() {
                if (wrapper.children.length > 1) {
                    wrapper.removeChild(wrapper.lastElementChild);
                    count--;
                }
            });
        });
    </script>
</x-backend.layouts.master>
