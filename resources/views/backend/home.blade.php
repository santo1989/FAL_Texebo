{{-- @switch(auth()->user()->role_id)
    @case('1')
        @include('layouts.Admin')
    @break

    @case('2')
        @include('layouts.User')
    @break

    @case('3')
        @include('layouts.supervisor')
    @break

    @case('4')
        @include('layouts.hr_manager')
    @break

    @case('2')
        @include('layouts.hr_officer')
    @break

    @default
        <x-backend.layouts.master>


        </x-backend.layouts.master>
@endswitch

<script>
    $(document).ready(function() {
        $('#example').DataTable();
    } );
</script> --}}

@include('layouts.Admin')
{{-- 
<x-backend.layouts.master>

    <x-slot name="pageTitle">
        Admin Dashboard
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader">
                <div class="row">
                    <div class="col-12">Dashboard</div>
                  
                </div>
            </x-slot>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    <div class="container-fluid">
        <div class="row justify-content-center pb-3 text-center">
            <div class="col-md-3" style="text-align: center !important; margin : 0 !important; padding : 0 !important; font-size : 12px !important; font-weight : bold !important ! width:20vw;">
                <div class="card"> 
                        <a href="{{ route('tna_home') }}" class="btn btn-sm btn-outline-success card-link">TNA</a> 
                </div>
            </div>
            <div class="col-md-3" style="text-align: center !important; margin : 0 !important; padding : 0 !important; font-size : 12px !important; font-weight : bold !important ! width:20vw;">
                <div class="card"> 
                        <a href="{{ route('oms_home') }}" class="btn btn-sm btn-outline-success card-link">OMS</a> 
                </div>
            </div>
        </div>
    </div>
 
</x-backend.layouts.master> --}}
