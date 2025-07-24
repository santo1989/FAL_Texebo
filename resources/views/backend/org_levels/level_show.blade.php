<x-backend.layouts.master>

 <div class="container">


        <div class="text-center pt-3 pb-3">
            <div class="badge badge-info ">
                <h6>Employees under {{ Auth::user()->name }}</h6>
            </div>



        </div>
        @if (session('message'))
            <div class="alert alert-success">
                <span class="close" data-dismiss="alert">&times;</span>
                <strong>{{ session('message') }}.</strong>
            </div>
        @endif
        <div class="text-center">
            <div class="card mb-4 text-center">
                <div class="card-header pt-1 pb-1">
                    @isset($parent)
                        <div class="row justify-content-center pt-1 pb-1">
                            <div class="col-md-2 col-xl-2">
                                <div class="card">
                                    <img src="{{ asset('images/users/' . Auth::user()->picture) }}"
                                        class="card-img-top rounded-circle" height="200px" width="100px"
                                        alt="{{ Auth::user()->name }}">
                                    <div class="card-body">
                                        <h6 class="card-title">{{ $parent_details->name }}</h6>
                                        <h6 class="card-title">ID: {{ $parent_details->emp_id }}</h6>
                                        @php
                                            $designation = App\Models\Designation::where('id', $parent_details->designation_id)
                                                ->first();
                                            $departmrnt = App\Models\Department::where('id', $parent_details->department_id)->first();
                                        @endphp
                                        <h6 class="card-subtitle mb-2 text-muted ">{{ $designation->name ?? 'No Data' }}, {{ $departmrnt->name }}</h6>

                                    </div>
                                </div>
                            </div>
                        </div>
                    @endisset
                </div>
                <div class="card-body">
                    <div class="badge badge-secondary pt-1 pb-1">
                        {{-- <h6>Level 1</h6> --}}
                    </div>
                    @isset($child_details)
                        <div class="row justify-content-between pt-1 pb-1">


                            @forelse ($child_details as $child_details)
                                <div class="col-md-2 col-xl-2 col-sm-12 pt-1 pb-1">



                                    <div class="card">
                                        <img src="{{ asset('images/users/' . $child_details->picture) }}"
                                            class="card-img-top rounded-circle" height="200px" width="100px"
                                            alt="{{ $child_details->name }}">
                                        <div class="card-body">
                                            <h6 class="card-title">{{ $child_details->name }}</h6>
                                            <h6 class="card-title">ID: {{ $child_details->emp_id }}</h6>
                                        @php
                                            $designation = App\Models\Designation::where('id', $child_details->designation_id)
                                                ->first();
                                            $departmrnt = App\Models\Department::where('id', $child_details->department_id)->first();
                                        @endphp
                                        <h6 class="card-subtitle mb-2 text-muted ">{{ $designation->name ?? 'No Data' }}, {{ $departmrnt->name }}</h6>


                                            {{-- <form action="{{ route('user_child', $child_details->id) }}" method="GET">
                                                @csrf
                                                <input type="hidden" name="level_count" value="{{ 1 }}">
                                                <button class="btn btn-primary btn-sm"  onclick="window.location='{{ route('admin_user_child', $child_details->id) }}' "
                                                >View</button>
                                            </form> --}}
                                            {{-- <button class="btn btn-primary btn-sm"
                                                onclick="window.location='{{ route('user_child', $child_details->id) }}' ">View</button> --}}
                                        </div>
                                    </div>
                                </div>
                            @empty

                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">No Child</h6>
                                        <h6 class="card-subtitle mb-2 text-muted ">No Child</h6>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    @endisset



                </div>
            </div>
        </div>
    </div>





</x-backend.layouts.master>
