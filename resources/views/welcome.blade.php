    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Font Awesome -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" rel="stylesheet" />
        <!-- MDB -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.0.1/mdb.min.css" rel="stylesheet" />

    </head>

    <body>
        <!-- The video -->
        <video autoplay muted loop id="myVideo"
            style="position: fixed; right: 0; bottom: 0; min-width: 100%; min-height: 100%;">
            <source src="{{ asset('images/assets/NorthernTosrifaGroup.mp4') }}" type="video/mp4">
        </video>
        {{-- style="background-image: linear-gradient(#40c47c,#40c47c,#40c47c); background-size: cover; background-repeat: repeat; height:100vh;" --}}
        <div class="container-fluid">


            <div style="margin: 0 auto; position: fixed; background: rgba(0, 0, 0, 0.5); color: #f1f1f1; left:33vw; right:33vw; top:20vh; width: 33vw;"
                id="glassPanel">
                <div class="p-5 text-white text-center" id="logo">
                    <div class="text-center py-5">
                        <img src="{{ asset('images/assets/logo.png') }}" alt="" heigt=600vh; width=200vw;
                            class="img rounded text-center text-white">
                    </div>

                    <div class="flex justify-content-center text-center py-1">
                        @if (Route::has('login'))
                            <div class="hidden fixed top-0 right-0 px-6 py-4 sm:block">
                                @auth
                                    <a href="{{ url('home') }}" class="btn btn-outline-light btn-lg">Dashboard</a>
                                @else
                                    {{-- <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg">Log in</a> --}}
                                    <a type="button" class="btn btn-outline-light btn-lg" data-bs-toggle="modal"
                                        data-bs-target="#loginModal" id="loginPanel">
                                        Log in
                                    </a>


                                    @if (Route::has('register'))
                                        {{-- <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg">Create New
                                            Account</a> --}}
                                        <a type="button" class="btn btn-outline-light btn-lg" data-bs-toggle="modal"
                                            data-bs-target="#registerModal">
                                            Create New Account
                                        </a>
                                    @endif
                                @endauth
                            </div>
                        @endif
                    </div>

                </div>
            </div>


        </div>
        <div class="footer fixed-bottom mt-5 p-4 text-white text-center text-light pt-1" id="footer">
            {{ now()->year }} -NTG MIS
            DEPARTMENT<br>
            Designed and developed by – Internal Software Support and Development (ISSD) Team,<br>
            <span id="privacymessage" style="font-weight: normal; font-size: 10pt; font-family: Verdana, Arial;">This is
                an web based
                application site. If you have any pop up blocking application you can deactivate
                for this site. If you are facing any technical problem to view this site, feel free
                to mail to our </span>
            <a href="mailto:mis@ntg.com.bd" target="_blank"
                style="font-weight: bold; font-size: 10pt; font-family: Verdana, Arial; text-decoration: none; text-align:center; color:cornsilk;">Tech
                Team. </a>

        </div>

        <!-- login Modal start-->
        <div class="modal fade text-light" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel"
            data-bs-backdrop="static" aria-hidden="true">
            <div class="modal-dialog modal-lg text-light"
                style="margin: 0 auto; position: fixed; left:33%; right:33%; top:10%; width: 33%; opacity: 1; background-color: transparent; background-color: rgba(0,0,0,.5)">
                <div class="modal-content" style="background-color: transparent; border: 2px solid #40c47c;">
                    <div class="modal-header text-light">
                        <h5 class="modal-title text-center text-light" id="loginModalLabel">Log in</h5>
                        {{-- <button type="button" class="btn-close btn-outline-light" data-bs-dismiss="modal"
                            aria-label="Close"></button> --}}
                        <button type="button" class="btn btn-light btn-close" data-bs-dismiss="modal"
                            aria-label="Close" style="background-color: white; border-color: white; color: black;"
                            onmouseover="this.classList.add('btn-danger')"
                            onmouseout="this.classList.remove('btn-danger')"></button>

                    </div>
                    <div class="modal-body text-center text-light" style="background-color: transparent;">
                        <!-- Your x-guest-layout code here -->
                        <div class="card p-3 m-3" style="background-color: transparent;">
                            <x-slot name="logo">
                                <a href="/">
                                    <img src="{{ asset('images/assets/logo.png') }}" alt="" heigt=600px;
                                        width=200px; class="img rounded text-center text-white" />
                                </a>
                            </x-slot>

                            <!-- Session Status -->
                            <x-auth-session-status class="mb-4" :status="session('status')" />

                            <!-- Validation Errors -->
                            <x-auth-validation-errors class="mb-4" :errors="$errors" />

                            <form method="POST" action="{{ route('login') }}">
                                @csrf

                                <!-- Email Address -->
                                <div class="mb-3 text-light">
                                    <label for="exampleFormControlInput1" class="form-label text-light">Email
                                        address</label>
                                    <input type="email" class="form-control" id="exampleFormControlInput1"
                                        placeholder="name@ntg.com.bd" name="email">
                                </div>

                                <!-- Password -->
                                <div class="mt-4 text-light">
                                    <label for="inputPassword5" class="form-label text-light">Password</label>
                                    <input type="password" id="inputPassword5" class="form-control"
                                        aria-describedby="passwordHelpBlock" name="password">
                                </div>

                                <!-- Remember Me -->
                                <div class="block mt-4 text-light">
                                    <label for="remember_me" class="inline-flex items-center">
                                        <input id="remember_me" type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            name="remember">
                                        <span class="ml-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-end mt-4 text-light">
                                    @if (Route::has('password.request'))
                                        <a class="underline text-sm text-gray-600 hover:text-gray-900"
                                            href="{{ route('password.request') }}">
                                            {{ __('Forgot your password?') }}
                                        </a>
                                    @endif

                                    <button type="submit" class="btn btn-outline-light btn-lg">Log in</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>




        <!-- login Modal end-->

        <!-- Register Modal start-->

        <div class="modal fade" id="registerModal" data-bs-backdrop="static" tabindex="-1"
            aria-labelledby="registerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content" style="background-color: rgba(0,0,0,0.5)">
                    <div class="modal-header" style="background: rgba(0, 0, 0, 0.5); color: #f1f1f1;">
                        <h5 class="modal-title text-center" id="registerModalLabel"> Create New Account</h5>
                        <button type="button" class="btn btn-light btn-close" data-bs-dismiss="modal"
                            aria-label="Close" style="background-color: white; border-color: white; color: black;"
                            onmouseover="this.classList.add('btn-danger')"
                            onmouseout="this.classList.remove('btn-danger')"></button>





                    </div>
                    <div class="modal-body" style="background: rgba(0, 0, 0, 0.5); color: #f1f1f1;">
                        <!-- Your x-guest-layout code here -->

                        <div class="container py-3 justify-content-center"
                            style="background: rgba(0, 0, 0, 0.5); color: #f1f1f1;">
                            <div class="row justify-content-between">
                                <div class="col-md-12">
                                    <div>
                                        <div class=" p-4 p-md-5">
                                            {{-- <h3 class="mb-4 pb-2 pb-md-0 mb-md-5">Registration Form</h3> --}}

                                            <!-- Validation Errors -->
                                            <x-auth-validation-errors class="mb-4" :errors="$errors" />

                                            <form method="POST" action="{{ route('register') }}"
                                                enctype="multipart/form-data">
                                                @csrf

                                                <div class="row justify-content-between mt-3">
                                                    <div class="col-md-6">
                                                        {{-- Open Space Start --}}
                                                        <div style="height:115px;">
                                                            <h3 class="mb-4 pb-2 pb-md-0 mb-md-5">Registration Form
                                                            </h3>

                                                        </div>
                                                        {{-- Open Space End --}}
                                                        {{-- Division start  --}}
                                                        @php
                                                            $divisions = App\Models\Division::all();
                                                        @endphp
                                                        <div class="mt-3">

                                                            <x-label for="division_id" :value="__('Division')"
                                                                class="ml-2 " />

                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <select name="division_id" id="division_id"
                                                                        class="form-control" required>
                                                                        <option value=""
                                                                            style="background-color: rgba(0, 0, 0, 0.5)">
                                                                            Select Division</option>
                                                                        @foreach ($divisions as $division)
                                                                            <option value="{{ $division->id }}">
                                                                                {{ $division->name }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>

                                                            </div>

                                                        </div>

                                                        {{-- Division end --}}

                                                    </div>
                                                    <div class="col-md-6">
                                                        {{-- image preview start --}}
                                                        <div class="mt-3 ">
                                                            <img class="img-thumbnail mx-auto d-block" id="output"
                                                                height="100px" width="100px" />
                                                            <script>
                                                                var loadFile = function(event) {
                                                                    var output = document.getElementById('output');
                                                                    output.src = URL.createObjectURL(event.target.files[0]);
                                                                    output.onload = function() {
                                                                        URL.revokeObjectURL(output.src) // free memory
                                                                    }
                                                                };
                                                            </script>

                                                        </div>
                                                        {{-- image preview end --}}
                                                        {{-- image upload --}}
                                                        <div class="mt-3 mr-4 ml-2">
                                                            <x-label for="imageUpload" :value="__('Picture')" />

                                                            <x-input id="image" class="form-control"
                                                                type="file" name="picture" :value="old('picture')"
                                                                required autofocus accept="image/*"
                                                                onchange="loadFile(event)" />

                                                        </div>
                                                        {{-- image upload end --}}

                                                    </div>
                                                </div>

                                                <div class="row justify-content-between">
                                                    <div class="col-md-6">
                                                        {{-- employee id start --}}

                                                        <div class="mt-3 ">
                                                            <x-label for="emp_id" :value="__('Employee ID')" />

                                                            <x-input id="emp_id" class="form-control"
                                                                type="text" name="emp_id" :value="old('emp_id')"
                                                                required autofocus />

                                                        </div>
                                                        {{-- employee id end  --}}
                                                        {{-- name start --}}
                                                        <div class="mt-3">
                                                            <x-label for="name" :value="__('Name')" />

                                                            <x-input id="name" class="form-control"
                                                                type="text" name="name" :value="old('name')"
                                                                required autofocus />

                                                        </div>
                                                        {{-- name end --}}

                                                        <!-- Email Address -->
                                                        <div class="mt-3">
                                                            <x-label for="email" :value="__('Email')" />

                                                            <x-input id="email" class="form-control"
                                                                type="email" name="email" :value="old('email')"
                                                                required />
                                                        </div>

                                                        <!-- Password -->
                                                        <div class="mt-3">
                                                            <x-label for="password" :value="__('Password')" />

                                                            <x-input id="password" class="form-control"
                                                                type="password" name="password" required
                                                                autocomplete="new-password" />
                                                        </div>




                                                    </div>

                                                    <div class="col-md-6">

                                                        {{-- Company start  --}}

                                                        <div class="mt-3">

                                                            <x-label for="company_id" :value="__('Company')"
                                                                class="ml-2 " />

                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <select name="company_id" id="company_id"
                                                                        class="form-control" required>
                                                                        <option value=""
                                                                            style="background-color: rgba(0, 0, 0, 0.5)">
                                                                            Select Division first
                                                                        </option>

                                                                    </select>
                                                                </div>

                                                            </div>
                                                        </div>

                                                        {{-- Company end --}}

                                                        {{-- department start  --}}

                                                        <div class="mt-3">

                                                            <x-label for="department_id" :value="__('Department')"
                                                                class="ml-2 " />
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <select name="department_id" id="department_id"
                                                                        class="form-control" required>
                                                                        <option value=""
                                                                            style="background-color: rgba(0, 0, 0, 0.5)">
                                                                            Select Company first
                                                                        </option>

                                                                    </select>

                                                                </div>

                                                            </div>

                                                        </div>

                                                        {{-- department end --}}

                                                        {{-- designation start  --}}

                                                        <div class="mt-3">
                                                            <div class="row">
                                                                <x-label for="designation_id" :value="__('Designation')"
                                                                    class="ml-2 " />
                                                                <div class="col-12">
                                                                    <select name="designation_id" id="designation_id"
                                                                        class="form-control" required>
                                                                        <option value=""
                                                                            style="background-color: rgba(0, 0, 0, 0.5)">
                                                                            Select Division first
                                                                        </option>
                                                                    </select>
                                                                </div>

                                                            </div>

                                                        </div>

                                                        {{-- designation end  --}}

                                                        {{-- <!-- Confirm Password --> --}}
                                                        <div class="mt-3">
                                                            <div class="row">
                                                                <x-label for="password_confirmation" :value="__('Confirm Password')"
                                                                    class="ml-2 " />
                                                                <div class="col-12">
                                                                    <x-input id="password_confirmation"
                                                                        class="form-control" type="password"
                                                                        name="password_confirmation" required />
                                                                </div>

                                                            </div>

                                                        </div>
                                                        {{-- Confirm Password end --}}
                                                    </div>

                                                </div>
                                                <div class="row justify-content-between">
                                                    <div class="col-md-6">

                                                        {{-- mobile --}}
                                                        <div class="mt-3">
                                                            <x-label for="mobile" :value="__('Mobile')" />

                                                            <x-input id="mobile" class="form-control"
                                                                type="text" name="mobile" :value="old('mobile')"
                                                                required autofocus />

                                                        </div>
                                                        {{-- mobile end --}}
                                                    </div>
                                                    <div class="col-md-6">

                                                        {{-- Dob start --}}

                                                        <div class="mt-3">
                                                            <x-label for="dob" :value="__('Date of Birth')"
                                                                class="ml-2 " />

                                                            <x-input id="dob" class="form-control"
                                                                type="date" name="dob" :value="old('dob')"
                                                                required autofocus />

                                                        </div>

                                                        {{-- Dob end --}}

                                                    </div>
                                                </div>

                                                <div class="row justify-content-between">

                                                    <div class="col-md-6">

                                                        {{-- Joining Date start --}}

                                                        <div class="mt-3">
                                                            <x-label for="joining_date" :value="__('Joining Date')" />

                                                            <x-input id="joining_date" class="form-control"
                                                                type="date" name="joining_date" :value="old('joining_date')"
                                                                required autofocus />

                                                        </div>

                                                        {{-- Joining Date end --}}

                                                    </div>

                                                    <div class="col-md-6">

                                                        <div style="margin-top:50px;">

                                                            <button type="submit"
                                                                class="btn btn-outline-light ml-2 mx-auto d-block">Submit</button>
                                                        </div>
                                                    </div>
                                                </div>

                                            </form>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>
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
                                                const designationSelect = $('#designation_id');

                                                companySelect.empty();
                                                companySelect.append(
                                                    '<option value="" style="background-color: rgba(0, 0, 0, 0.5)">Select Company</option>'
                                                    );
                                                $.each(data.company, function(index, company) {
                                                    companySelect.append(
                                                        `<option value="${company.id}">${company.name}</option>`
                                                    );
                                                });

                                                designationSelect.empty();
                                                designationSelect.append(
                                                    '<option value="" style="background-color: rgba(0, 0, 0, 0.5)">Select Designation</option>'
                                                    );
                                                $.each(data.designations, function(index, designation) {
                                                    designationSelect.append(
                                                        `<option value="${designation.id}">${designation.name}</option>`
                                                    );
                                                });
                                            },
                                            error: function(xhr, status, error) {
                                                console.error(error);
                                            }
                                        });
                                    } else {
                                        alert('Select a division first for company and designation name.');
                                    }
                                });
                            });

                            $(document).ready(function() {
                                $('#company_id').on('change', function() {
                                    var company_id = $(this).val();

                                    if (company_id) {
                                        $.ajax({
                                            url: '/get-department/' + company_id,
                                            type: 'GET',
                                            dataType: 'json',
                                            success: function(data) {
                                                console.log(data);
                                                const companySelect = $('#department_id');

                                                companySelect.empty();
                                                companySelect.append(
                                                    '<option value="" style="background-color: rgba(0, 0, 0, 0.5)">Select Department</option>'
                                                    );
                                                $.each(data.departments, function(index, departments) {
                                                    companySelect.append(
                                                        `<option value="${departments.id}">${departments.name}</option>`
                                                    );
                                                });
                                            },
                                            error: function(xhr, status, error) {
                                                console.error(error);
                                            }
                                        });
                                    } else {
                                        alert('Select a Company first for Department name.');
                                    }
                                });
                            });
                        </script>



                    </div>
                </div>
            </div>
        </div>

        <!-- Register Modal end-->
    </body>
    <script>
        let privacymessage = document.getElementById("privacymessage");

        let myVideo = document.getElementById("myVideo");
        let logo = document.getElementById("logo");
        let footer = document.getElementById("footer");
        myVideo.onplay = function() {
            setTimeout(function() {
                myVideo.play();
            }, 1000);
        };
        if (window.innerWidth < 768) {
            privacymessage.style.display = "none";
            footer.style.display = "none";
        }

        $(document).ready(function() {
            let loginPanel = $("#loginPanel");
            let registerPanel = $("#registerPanel");
            let loginModal = $("#loginModal");
            let registerModal = $("#registerModal");
            let glassPanel = $("#glassPanel");

            loginPanel.click(function() {
                loginModal.modal("show");
                registerModal.modal("hide");
                glassPanel.hide();
            });

            registerPanel.click(function() {
                loginModal.modal("hide");
                registerModal.modal("show");
                glassPanel.hide();
            });

            // When login/register modal is closed
            loginModal.on("hidden.bs.modal", function() {
                glassPanel.show();
            });
            registerModal.on("hidden.bs.modal", function() {
                glassPanel.show();
            });
        });


        // let loginPanel = document.quarySelector("#loginPanel");
        // let registerPanel = document.quarySelector("#registerPanel");
        // let loginModal = document.getElementById("loginModal");
        // let registerModal = document.getElementById("registerModal");
        // let glassPanel = document.getElementById("glassPanel");

        // loginPanel.addEventListener("click", function() {
        //     loginModal.style.display = "block";
        //     registerPanel.style.display = "none";
        //     glassPanel.style.display = "none";

        // });

        // registerPanel.addEventListener("click", function() {
        //     loginPanel.style.display = "none";
        //     registerModal.style.display = "block";
        //     glassPanel.style.display = "none";
        // });
        //         $("#loginPanel").on("click", function() {
        //     $("#loginModal").modal("show");
        //     $("#registerPanel").hide();
        //     $("#glassPanel").hide();
        // });

        // $("#registerPanel").on("click", function() {
        //     $("#loginPanel").hide();
        //     $("#registerModal").modal("show");
        //     $("#glassPanel").hide();
        // });
    </script>

    </html>
