<x-backend.layouts.master>
    <x-slot name="pageTitle">
        Create Style
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> Style </x-slot>
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('styles.index') }}">Style</a></li>
            <li class="breadcrumb-item active">Create Style</li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>


    <x-backend.layouts.elements.errors />
    <form action="{{ route('styles.store') }}" method="post" enctype="multipart/form-data">
        <div>
            @csrf
            <!-- Use a wrapper to contain all dynamic inputs -->
            <div id="style-input-wrapper">
                <x-backend.form.input name="name[]" type="text" label="Style Name" />
            </div>
            <br>
            <button type="button" class="btn btn-secondary p-2 add-more">Add More</button>
            <button type="button" class="btn btn-danger p-2 remove-more">Remove Last</button>
            <x-backend.form.saveButton>Save</x-backend.form.saveButton>



        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const wrapper = document.getElementById('style-input-wrapper');

            // Template for new input field
            function createInput(index) {
                const div = document.createElement('div');
                div.classList.add('form-group', 'mt-2');

                div.innerHTML = `
                <label for="style_name_${index}">Style Name</label>
                <input type="text" name="name[]" id="style_name_${index}" class="form-control" placeholder="Enter style name">
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

    <x-backend.layouts.elements.errors />


</x-backend.layouts.master>
