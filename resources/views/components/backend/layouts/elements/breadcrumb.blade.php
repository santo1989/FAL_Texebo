<h4 class="mt-2 text-center">{{ $pageHeader }}</h4>
<ol class="breadcrumb mb-2">

     {{ $slot }} 

</ol>
{{-- <div class="row justify-content-between">
    <div class="col-9 text-center">
        <h4 class="mt-1 text-center">{{ $pageHeader }}</h4>
    </div>
    <div class="col-3">
        <div class="">
            <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="{{ asset('images/users/' . auth()->user()->picture) }}" class="rounded-circle"
                             width="50px" height="50px" alt="{{ auth()->user()->name }}">
                        {{ auth()->user()->name ?? '' }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <a class="dropdown-item" onclick="event.preventDefault(); this.closest('form').submit();">
                                    Logout
                                </a>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div> --}}
