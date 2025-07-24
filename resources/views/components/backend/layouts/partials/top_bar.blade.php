 @php
     use Carbon\Carbon;
     date_default_timezone_set('Asia/Dhaka');
     $current_time = Carbon::now();
     $time_of_day = '';
     if ($current_time->hour >= 5 && $current_time->hour < 12) {
         $time_of_day = 'Morning';
     } elseif ($current_time->hour >= 12 && $current_time->hour < 18) {
         $time_of_day = 'Afternoon';
     } else {
         $time_of_day = 'Evening';
     }
     $wishMessage = "Good $time_of_day";
     
 @endphp
 <nav class="sb-topnav navbar navbar-expand navbar-light bg-light text-white"
     style="background-image: linear-gradient(#40c47c,#40c47c,#40c47c); text-color:white;">
     <!-- Navbar Brand-->
     <img src="{{ asset('images/assets/ntg_logo.png') }}" alt="user image" class="img ps-3" width="100px" height="50px">
     <a class="navbar-brand ps-3 pl-3" href="{{ route('home') }}"></a>
     <!-- Sidebar Toggle-->
     <button class="mr-3 btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i
             class="fas fa-bars"></i></button>
     <!-- Navbar Search-->
     <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
         <div class="input-group">

             <marquee behavior="0.05" direction="">{{ $wishMessage }} </marquee>
         </div>
     </form>
     {{-- notification bell icon with dropdown board for notifications --}}
     <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
         <li class="nav-item dropdown">
             @php
                 $notifications = App\Models\Notification::where('user_id', auth()->user()->id)
                     ->orwhere('reciver_id', auth()->user()->id)
                     ->where('status', 'unread')
                     ->limit(10)
                     ->orderBy('id', 'desc')
                     ->get();
                 $notifications_count = App\Models\Notification::where('user_id', auth()->user()->id)
                     ->orwhere('reciver_id', auth()->user()->id)
                     ->where('status', 'unread')
                     ->count();
             @endphp
             <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button"
                 data-bs-toggle="dropdown" aria-expanded="false">
                 <i class="fa fa-bell"></i>
                 <span class="badge rounded-pill">
                     {{ $notifications_count ?? '' }}
                 </span>
             </a>
             <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">

                 @forelse ($notifications as $notification)
                     <li>
                         <a class="dropdown-item" href="{{ route('notification.read', $notification->id) }}">
                             <div class="row">
                                 <div class="col-md-12">
                                     {{-- <p class="text-dark"><img src="{{ asset('images/users/' . $notification->user->picture) }}" class="rounded-circle"
                                        width="50px" height="50px" alt="{{ $notification->user->name }}">{{ $notification->user->name }}</p> --}}
                                     <p class="text-dark">{{ $notification->message }}</p>
                                     {{-- <p class="text-dark">{{ $notification->created_at->diffForHumans() }}</p> --}}
                                 </div>
                             </div>
                         </a>

                     </li>
                 @empty
                     <li>
                         <a class="dropdown-item" href="#">
                             <div class="row">
                                 <div class="col-md-12">
                                     <p class="text-dark">No Notification</p>
                                 </div>
                             </div>
                         </a>
                     </li>
                 @endforelse
             </ul>
         </li>
         <!-- Navbar-->

         <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
             <li class="nav-item dropdown">
                 <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button"
                     data-bs-toggle="dropdown" aria-expanded="false"><img
                         src="{{ asset('images/users/' . auth()->user()->picture) }}" class="rounded-circle"
                         width="50px" height="50px" alt="{{ auth()->user()->name }}">
                     {{ auth()->user()->name ?? '' }}
                 </a>
                 <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                     {{-- <li><a class="dropdown-item" href="#!">Settings</a></li>
                <li><a class="dropdown-item" href="#!">Activity Log</a></li> --}}
                     {{-- <li>
                    <hr class="dropdown-divider" />
                </li> --}}
                     <li>
                         <form method="POST" action="{{ route('logout') }}">
                             @csrf
                             <a class="dropdown-item"
                                 onclick="event.preventDefault();
                                        this.closest('form').submit();">Logout</a>

                         </form>
                     </li>
                 </ul>
             </li>
         </ul>
 </nav>

 <script>
     // // check if browser supports Push JS
     // if ('PushManager' in window) {
     //     // register a service worker
     //     navigator.serviceWorker.register('/path/to/service-worker.js')
     //         .then(function(registration) {
     //             console.log('Service worker registered:', registration);
     //             // get permission to show notifications
     //             Notification.requestPermission().then(function(permission) {
     //                 console.log('Notification permission:', permission);
     //                 // if permission is granted, send a notification
     //                 if (permission === 'granted') {
     //                     // loop through each notification and send it
     //                     @foreach ($notifications as $notification)
     //                         registration.showNotification('{{ $notification->message }}', {
     //                             body: '{{ $notification->user->name }}',
     //                             icon: '{{ asset('images/users/' . $notification->user->picture) }}',
     //                             data: {
     //                                 url: '{{ route('notification.read', $notification->id) }}'
     //                             }
     //                         });
     //                     @endforeach
     //                 }
     //             });
     //         })
     //         .catch(function(error) {
     //             console.error('Service worker registration failed:', error);
     //         });
     // }
 </script>
