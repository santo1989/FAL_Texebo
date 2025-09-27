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

    // Get user's custom color theme or use default
$userTheme = json_decode($_COOKIE['rmg_user_theme'] ?? '{}', true);
$primaryColor = $userTheme['primary'] ?? '#40c47c';
$sidebarColor = $userTheme['sidebar'] ?? '#2c3e50';
$topbarColor = $userTheme['topbar'] ?? '#34495e';
@endphp

<nav class="sb-topnav navbar navbar-expand navbar-light bg-light text-white" id="mainTopnav"
    style="background: linear-gradient({{ $topbarColor }}, {{ $topbarColor }}, {{ $topbarColor }}); color: white;">

    <!-- Navbar Brand-->
    <img src="{{ asset('images/assets/ntg_logo.png') }}" alt="NTG Logo" class="img ps-3" width="100px" height="50px">
    <a class="navbar-brand ps-3 pl-3" href="{{ route('home') }}">FAL</a>

    <!-- Sidebar Toggle-->
    <button class="mr-3 btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Navigation Menu -->
    <div class="navbar-collapse collapse" id="mainNavbar">
        <ul class="navbar-nav me-auto">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('dashboard') }}">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
            </li>

            <!-- Master Data Dropdown -->
            @canany(['Admin', 'Supervisor'])
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="masterDataDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-database me-1"></i>Master Data
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="masterDataDropdown">
                        <li><a class="dropdown-item" href="{{ route('divisions.index') }}">Divisions</a></li>
                        <li><a class="dropdown-item" href="{{ route('companies.index') }}">Companies</a></li>
                        <li><a class="dropdown-item" href="{{ route('departments.index') }}">Departments</a></li>
                        <li><a class="dropdown-item" href="{{ route('designations.index') }}">Designations</a></li>
                        <li><a class="dropdown-item" href="{{ route('buyers.index') }}">Buyers</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="{{ route('styles.index') }}">Styles</a></li>
                        <li><a class="dropdown-item" href="{{ route('colors.index') }}">Colors</a></li>
                        <li><a class="dropdown-item" href="{{ route('sizes.index') }}">Sizes</a></li>
                    </ul>
                </li>
            @endcanany

            <!-- Production Dropdown -->
            @canany(['Admin', 'Supervisor', 'OrderDataEntry', 'Cutting', 'Print Send', 'Print Receive', 'Input',
                'Output', 'Packing', 'Shipment'])
                <li class="nav-item dropdown" id="productionDropdownContainer">
                    <a class="nav-link dropdown-toggle" href="#" id="productionDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-industry me-1"></i>Production
                    </a>
                    <ul class="dropdown-menu multi-level" aria-labelledby="productionDropdown">
                        @canany(['Admin', 'Supervisor', 'OrderDataEntry'])
                            <li><a class="dropdown-item" href="{{ route('order_data.index') }}">Order Data</a></li>
                        @endcanany

                        @canany(['Admin', 'Cutting', 'Supervisor'])
                            <li><a class="dropdown-item" href="{{ route('cutting_data.index') }}">Cutting</a></li>
                        @endcanany

                        <!-- Printing Submenu -->
                        @canany(['Admin', 'Print Send', 'Print Receive', 'Supervisor'])
                            <li class="dropdown-submenu">
                                <a class="dropdown-item dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                    <i class="fas fa-print me-1"></i>Printing
                                    <span class="dropdown-arrow">›</span>
                                </a>
                                <ul class="dropdown-menu">
                                    @canany(['Admin', 'Print Send', 'Supervisor'])
                                        <li><a class="dropdown-item"
                                                href="{{ route('sublimation_print_send_data.index') }}">Sublimation Send</a></li>
                                    @endcanany
                                    @canany(['Admin', 'Print Receive', 'Supervisor'])
                                        <li><a class="dropdown-item"
                                                href="{{ route('sublimation_print_receive_data.index') }}">Sublimation Receive</a>
                                        </li>
                                    @endcanany

                                    @canany(['Admin', 'Print Send', 'Supervisor'])
                                        <li><a class="dropdown-item" href="{{ route('print_send_data.index') }}">Print/Emb Send</a>
                                        </li>
                                    @endcanany
                                    @canany(['Admin', 'Print Receive', 'Supervisor'])
                                        <li><a class="dropdown-item" href="{{ route('print_receive_data.index') }}">Print/Emb
                                                Receive</a></li>
                                    @endcanany
                                </ul>
                            </li>
                        @endcanany

                        <!-- Sewing Submenu -->
                        @canany(['Admin', 'Input', 'Output', 'Supervisor'])
                            <li class="dropdown-submenu">
                                <a class="dropdown-item dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                    <i class="fas fa-tools me-1"></i>Sewing
                                    <span class="dropdown-arrow">›</span>
                                </a>
                                <ul class="dropdown-menu">
                                    @canany(['Admin', 'Input', 'Supervisor'])
                                        <li><a class="dropdown-item" href="{{ route('line_input_data.index') }}">Input Data</a>
                                        </li>
                                    @endcanany
                                    @canany(['Admin', 'Output', 'Supervisor'])
                                        <li><a class="dropdown-item" href="{{ route('output_finishing_data.index') }}">Output
                                                Data</a></li>
                                    @endcanany
                                </ul>
                            </li>
                        @endcanany

                        <!-- Finishing Submenu -->
                        @canany(['Admin', 'Packing', 'Shipment', 'Supervisor'])
                            <li class="dropdown-submenu">
                                <a class="dropdown-item dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                    <i class="fas fa-box me-1"></i>Finishing
                                    <span class="dropdown-arrow">›</span>
                                </a>
                                <ul class="dropdown-menu">
                                    @canany(['Admin', 'Packing', 'Supervisor'])
                                        <li><a class="dropdown-item" href="{{ route('finish_packing_data.index') }}">Packing</a>
                                        </li>
                                    @endcanany
                                    @canany(['Admin', 'Shipment', 'Supervisor'])
                                        <li><a class="dropdown-item" href="{{ route('shipment_data.index') }}">Shipment</a></li>
                                    @endcanany
                                </ul>
                            </li>
                        @endcanany
                    </ul>
                </li>
            @endcanany

            <!-- Reports Dropdown -->
            @canany(['Admin', 'Supervisor', 'General'])
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-chart-bar me-1"></i>Reports
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="reportsDropdown">
                        <li><a class="dropdown-item" href="{{ route('cutting_data_report') }}">Cutting Reports</a></li>
                        <li><a class="dropdown-item"
                                href="{{ route('sublimation_print_send_data.report.total') }}">Printing Reports</a></li>
                        <li><a class="dropdown-item" href="{{ route('line_input_data.report.total_input') }}">Sewing
                                Reports</a></li>
                        <li><a class="dropdown-item"
                                href="{{ route('finish_packing_data.report.total_packing') }}">Packing Reports</a></li>
                        <li><a class="dropdown-item" href="{{ route('shipment_data.report.total_shipment') }}">Shipment
                                Reports</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="{{ route('shipment_data.report.final_balance') }}">Final
                                Balance</a></li>
                        <li><a class="dropdown-item" href="{{ route('shipment_data.report.waste') }}">Waste Report</a>
                        </li>
                    </ul>
                </li>
            @endcanany

            <!-- Administration Dropdown (Admin Only) -->
            @can('Admin')
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cogs me-1"></i>Admin
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                        <li><a class="dropdown-item" href="{{ route('users.index') }}">User Management</a></li>
                        <li><a class="dropdown-item" href="{{ route('roles.index') }}">Role Management</a></li>
                        <li><a class="dropdown-item" href="{{ route('online_user') }}">Online Users</a></li>
                    </ul>
                </li>
            @endcan
        </ul>
    </div>

    <!-- Navbar Search & Right Side Items -->
    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
        <div class="input-group">
            <marquee behavior="scroll" direction="left" scrollamount="3">{{ $wishMessage }} - Welcome to FAL
                Production ERP System</marquee>
        </div>
    </form>

    <!-- Right Side Icons -->
    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
        <!-- Theme Customization -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" id="themeDropdown" href="#" role="button"
                data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-palette"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end theme-picker" aria-labelledby="themeDropdown">
                <li class="dropdown-header">Theme Customization</li>
                <li>
                    <div class="px-3 py-2">
                        <label class="form-label small">Top Bar Color</label>
                        <input type="color" class="form-control form-control-color" id="topbarColorPicker"
                            value="{{ $topbarColor }}" title="Choose top bar color">
                    </div>
                </li>
                <li>
                    <div class="px-3 py-2">
                        <label class="form-label small">Sidebar Color</label>
                        <input type="color" class="form-control form-control-color" id="sidebarColorPicker"
                            value="{{ $sidebarColor }}" title="Choose sidebar color">
                    </div>
                </li>
                <li>
                    <div class="px-3 py-2">
                        <label class="form-label small">Primary Color</label>
                        <input type="color" class="form-control form-control-color" id="primaryColorPicker"
                            value="{{ $primaryColor }}" title="Choose primary color">
                    </div>
                </li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li class="dropdown-header">Theme Presets</li>
                <li>
                    <button class="dropdown-item theme-preset" data-preset="green">
                        <i class="fas fa-leaf me-2" style="color: #27ae60;"></i>Green Theme
                    </button>
                </li>
                <li>
                    <button class="dropdown-item theme-preset" data-preset="blue">
                        <i class="fas fa-tint me-2" style="color: #3498db;"></i>Blue Theme
                    </button>
                </li>
                <li>
                    <button class="dropdown-item theme-preset" data-preset="purple">
                        <i class="fas fa-gem me-2" style="color: #9b59b6;"></i>Purple Theme
                    </button>
                </li>
                <li>
                    <button class="dropdown-item theme-preset" data-preset="orange">
                        <i class="fas fa-fire me-2" style="color: #e67e22;"></i>Orange Theme
                    </button>
                </li>
                <li>
                    <button class="dropdown-item theme-preset" data-preset="dark">
                        <i class="fas fa-moon me-2" style="color: #34495e;"></i>Dark Theme
                    </button>
                </li>
                <li>
                    <button class="dropdown-item theme-preset" data-preset="teal">
                        <i class="fas fa-dragon me-2" style="color: #1abc9c;"></i>Teal Theme
                    </button>
                </li>
                <li>
                    <button class="dropdown-item theme-preset" data-preset="coral">
                        <i class="fas fa-fish me-2" style="color: #e74c3c;"></i>Coral Theme
                    </button>
                </li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li>
                    <button class="dropdown-item" onclick="ThemeManager.resetTheme()">
                        <i class="fas fa-undo me-2"></i>Reset to Default
                    </button>
                </li>
            </ul>
        </li>

        <!-- Notifications -->
        <li class="nav-item dropdown">
            @php
                $notifications = App\Models\Notification::where('user_id', auth()->user()->id)
                    ->orWhere('reciver_id', auth()->user()->id)
                    ->where('status', 'unread')
                    ->limit(10)
                    ->orderBy('id', 'desc')
                    ->get();
                $notifications_count = App\Models\Notification::where('user_id', auth()->user()->id)
                    ->orWhere('reciver_id', auth()->user()->id)
                    ->where('status', 'unread')
                    ->count();
            @endphp
            <a class="nav-link dropdown-toggle" id="notificationsDropdown" href="#" role="button"
                data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-bell"></i>
                @if ($notifications_count > 0)
                    <span class="badge bg-danger rounded-pill notification-badge">
                        {{ $notifications_count }}
                    </span>
                @endif
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                <li class="dropdown-header">Notifications ({{ $notifications_count }} unread)</li>
                @forelse ($notifications as $notification)
                    <li>
                        <a class="dropdown-item d-flex align-items-center"
                            href="{{ route('notification.read', $notification->id) }}">
                            <div class="flex-shrink-0 me-2">
                                <img src="{{ asset('images/users/' . ($notification->user->picture ?? 'default.png')) }}"
                                    class="rounded-circle" width="40" height="40" alt="User">
                            </div>
                            <div class="flex-grow-1">
                                <div class="small text-muted">{{ $notification->created_at->diffForHumans() }}</div>
                                <div class="fw-bold">{{ $notification->user->name ?? 'System' }}</div>
                                <div class="small">{{ Str::limit($notification->message, 50) }}</div>
                            </div>
                        </a>
                    </li>
                @empty
                    <li><a class="dropdown-item text-center text-muted" href="#">No new notifications</a></li>
                @endforelse
                @if ($notifications_count > 0)
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-center" href="{{ route('notifications.index') }}">View All
                            Notifications</a></li>
                @endif
            </ul>
        </li>

        <!-- User Profile -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" id="userDropdown" href="#" role="button"
                data-bs-toggle="dropdown" aria-expanded="false">
                <img src="{{ asset('images/users/' . auth()->user()->picture) }}" class="rounded-circle"
                    width="30" height="30" alt="{{ auth()->user()->name }}">
                <span class="d-none d-md-inline ms-1">{{ auth()->user()->name }}</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="{{ route('users.show', ['user' => auth()->user()->id]) }}">
                        <i class="fas fa-user me-2"></i>Profile
                    </a></li>
                <li><a class="dropdown-item" href="{{ route('users.edit', ['user' => auth()->user()->id]) }}">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a></li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </button>
                    </form>
                </li>
            </ul>
        </li>
    </ul>

    <!-- Mobile Toggle Button -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
        aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
        <i class="fas fa-bars"></i>
    </button>
</nav>
{{-- 
<style>
    .theme-picker {
        min-width: 280px;
    }

    .form-control-color {
        height: 40px;
        width: 100%;
    }

    .notification-badge {
        position: absolute;
        top: 0;
        right: 0;
        font-size: 0.7em;
        transform: translate(25%, -25%);
    }

    /* Multi-level dropdown styles */
    .dropdown-submenu {
        position: relative;
    }

    .dropdown-submenu>.dropdown-menu {
        top: 0;
        left: 100%;
        margin-top: -6px;
        margin-left: -1px;
        border-radius: 0 6px 6px 6px;
        display: none;
    }

    .dropdown-submenu:hover>.dropdown-menu {
        display: block;
    }

    .dropdown-submenu>a::after {
        display: block;
        content: " ";
        float: right;
        width: 0;
        height: 0;
        border-color: transparent;
        border-style: solid;
        border-width: 5px 0 5px 5px;
        border-left-color: #ccc;
        margin-top: 5px;
        margin-right: -10px;
    }

    .dropdown-arrow {
        float: right;
        margin-left: 10px;
        font-weight: bold;
    }

    /* Mobile responsiveness */
    @media (max-width: 768px) {
        .dropdown-submenu>.dropdown-menu {
            position: static;
            float: none;
            margin-left: 15px;
        }

        .navbar-nav .dropdown-menu {
            position: static;
            float: none;
        }

        .marquee {
            font-size: 0.9em;
        }
    }

    /* Smooth transitions */
    #mainTopnav,
    #layoutSidenav_nav {
        transition: all 0.3s ease-in-out;
    }

    .dropdown-menu {
        transition: all 0.2s ease-in-out;
    }

    /* Prevent hover on touch devices */
    @media (hover: hover) and (pointer: fine) {
        .dropdown:hover>.dropdown-menu {
            display: block;
        }

        .dropdown-submenu:hover>.dropdown-menu {
            display: block;
        }
    }

    /* Enhanced dropdown styling */
    .dropdown-menu {
        border: none;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
    }

    .dropdown-item {
        transition: all 0.2s ease;
    }

    .dropdown-item:hover {
        background-color: var(--primary-color, #40c47c);
        color: white;
        transform: translateX(5px);
    }

    .theme-preset {
        border-left: 3px solid transparent;
        transition: all 0.3s ease;
    }

    .theme-preset:hover {
        border-left-color: currentColor;
        padding-left: 20px;
    }
</style>

<script>
    // Enhanced Theme Management with 6+ Presets
    class ThemeManager {
        static defaultTheme = {
            primary: '#40c47c',
            sidebar: '#2c3e50',
            topbar: '#34495e'
        };

        static presets = {
            green: {
                primary: '#27ae60',
                sidebar: '#2ecc71',
                topbar: '#27ae60',
                name: 'Green Theme'
            },
            blue: {
                primary: '#3498db',
                sidebar: '#2980b9',
                topbar: '#3498db',
                name: 'Blue Theme'
            },
            purple: {
                primary: '#9b59b6',
                sidebar: '#8e44ad',
                topbar: '#9b59b6',
                name: 'Purple Theme'
            },
            orange: {
                primary: '#e67e22',
                sidebar: '#d35400',
                topbar: '#e67e22',
                name: 'Orange Theme'
            },
            dark: {
                primary: '#34495e',
                sidebar: '#2c3e50',
                topbar: '#34495e',
                name: 'Dark Theme'
            },
            teal: {
                primary: '#1abc9c',
                sidebar: '#16a085',
                topbar: '#1abc9c',
                name: 'Teal Theme'
            },
            coral: {
                primary: '#e74c3c',
                sidebar: '#c0392b',
                topbar: '#e74c3c',
                name: 'Coral Theme'
            }
        };

        static init() {
            this.loadTheme();
            this.bindEventListeners();
            this.applyTheme();
            this.enhanceDropdowns();
        }

        static loadTheme() {
            try {
                const themeCookie = this.getCookie('rmg_user_theme');
                if (themeCookie) {
                    this.currentTheme = JSON.parse(themeCookie);
                } else {
                    this.currentTheme = {
                        ...this.defaultTheme
                    };
                    this.saveTheme();
                }
            } catch (error) {
                console.error('Error loading theme:', error);
                this.currentTheme = {
                    ...this.defaultTheme
                };
            }
        }

        static saveTheme() {
            try {
                this.setCookie('rmg_user_theme', JSON.stringify(this.currentTheme), 365);
            } catch (error) {
                console.error('Error saving theme:', error);
            }
        }

        static applyTheme() {
            // Apply to top bar
            const topnav = document.getElementById('mainTopnav');
            if (topnav) {
                topnav.style.background =
                    `linear-gradient(${this.currentTheme.topbar}, ${this.currentTheme.topbar}, ${this.currentTheme.topbar})`;
            }

            // Apply to sidebar
            const sidebar = document.getElementById('sidenavAccordion');
            if (sidebar) {
                sidebar.style.color = this.currentTheme.sidebar;
            }

            // Update color pickers
            this.updateColorPickers();

            // Apply CSS variables for broader theme support
            document.documentElement.style.setProperty('--primary-color', this.currentTheme.primary);
            document.documentElement.style.setProperty('--sidebar-color', this.currentTheme.sidebar);
            document.documentElement.style.setProperty('--topbar-color', this.currentTheme.topbar);

            // Update meta theme color for mobile browsers
            this.updateMetaThemeColor();
        }

        static updateMetaThemeColor() {
            let metaThemeColor = document.querySelector('meta[name="theme-color"]');
            if (!metaThemeColor) {
                metaThemeColor = document.createElement('meta');
                metaThemeColor.name = 'theme-color';
                document.head.appendChild(metaThemeColor);
            }
            metaThemeColor.content = this.currentTheme.primary;
        }

        static updateColorPickers() {
            const pickers = {
                topbarColorPicker: 'topbar',
                sidebarColorPicker: 'sidebar',
                primaryColorPicker: 'primary'
            };

            Object.entries(pickers).forEach(([pickerId, themeKey]) => {
                const picker = document.getElementById(pickerId);
                if (picker) {
                    picker.value = this.currentTheme[themeKey];
                }
            });
        }

        static updateColor(themeKey, colorValue) {
            this.currentTheme[themeKey] = colorValue;
            this.saveTheme();
            this.applyTheme();
            this.showToast(`${themeKey} color updated`, 'success');
        }

        static resetTheme() {
            this.currentTheme = {
                ...this.defaultTheme
            };
            this.saveTheme();
            this.applyTheme();
            this.showToast('Theme reset to default', 'success');
        }

        static applyPreset(presetName) {
            if (this.presets[presetName]) {
                this.currentTheme = {
                    ...this.presets[presetName]
                };
                this.saveTheme();
                this.applyTheme();
                this.showToast(`${this.presets[presetName].name} applied`, 'success');
            }
        }

        static enhanceDropdowns() {
            // Enhanced dropdown handling for multi-level menus
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize all dropdowns
                const dropdowns = document.querySelectorAll('.dropdown, .dropdown-submenu');

                dropdowns.forEach(dropdown => {
                    // Touch device support
                    dropdown.addEventListener('touchstart', function(e) {
                        if (this.classList.contains('show')) {
                            this.classList.remove('show');
                        } else {
                            // Close other open dropdowns
                            document.querySelectorAll('.show').forEach(open => {
                                if (open !== this) open.classList.remove('show');
                            });
                            this.classList.add('show');
                        }
                        e.preventDefault();
                        e.stopPropagation();
                    });

                    // Mouse events for desktop
                    dropdown.addEventListener('mouseenter', function() {
                        if (window.innerWidth > 768) {
                            this.classList.add('show');
                        }
                    });

                    dropdown.addEventListener('mouseleave', function() {
                        if (window.innerWidth > 768) {
                            setTimeout(() => {
                                if (!this.matches(':hover')) {
                                    this.classList.remove('show');
                                }
                            }, 300);
                        }
                    });
                });

                // Close dropdowns when clicking outside
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.dropdown') && !e.target.closest('.dropdown-submenu')) {
                        document.querySelectorAll('.dropdown.show, .dropdown-submenu.show').forEach(
                            open => {
                                open.classList.remove('show');
                            });
                    }
                });

                // Handle submenu arrow clicks
                document.querySelectorAll('.dropdown-submenu > a').forEach(link => {
                    link.addEventListener('click', function(e) {
                        if (window.innerWidth <= 768) {
                            e.preventDefault();
                            const submenu = this.nextElementSibling;
                            submenu.classList.toggle('show');
                        }
                    });
                });
            });
        }

        static bindEventListeners() {
            // Color picker event listeners
            document.getElementById('topbarColorPicker')?.addEventListener('change', (e) => {
                this.updateColor('topbar', e.target.value);
            });

            document.getElementById('sidebarColorPicker')?.addEventListener('change', (e) => {
                this.updateColor('sidebar', e.target.value);
            });

            document.getElementById('primaryColorPicker')?.addEventListener('change', (e) => {
                this.updateColor('primary', e.target.value);
            });

            // Theme preset buttons
            document.querySelectorAll('.theme-preset').forEach(button => {
                button.addEventListener('click', (e) => {
                    const preset = e.target.closest('.theme-preset').dataset.preset;
                    this.applyPreset(preset);
                });
            });

            // Prevent dropdown close when interacting with theme picker
            document.querySelectorAll('.theme-picker input, .theme-picker button').forEach(element => {
                element.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
            });

            // Enhanced mobile menu handling
            const navbarToggler = document.querySelector('.navbar-toggler');
            const mainNavbar = document.getElementById('mainNavbar');

            if (navbarToggler && mainNavbar) {
                navbarToggler.addEventListener('click', function() {
                    const isExpanded = mainNavbar.classList.contains('show');
                    this.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
                });
            }
        }

        static setCookie(name, value, days) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie =
                `${name}=${encodeURIComponent(value)};expires=${expires.toUTCString()};path=/;SameSite=Lax;Secure`;
        }

        static getCookie(name) {
            const nameEQ = name + "=";
            const ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
            }
            return null;
        }

        static showToast(message, type = 'info') {
            // Remove existing toasts
            document.querySelectorAll('.theme-toast').forEach(toast => toast.remove());

            const toast = document.createElement('div');
            toast.className = `theme-toast alert alert-${type} alert-dismissible fade show position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 250px;';
            toast.innerHTML = `
            <strong>Theme Updated:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
            document.body.appendChild(toast);

            // Auto remove after 3 seconds
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 3000);
        }
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        ThemeManager.init();
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        const mainNavbar = document.getElementById('mainNavbar');
        if (window.innerWidth > 768 && mainNavbar) {
            mainNavbar.classList.remove('show');
        }
    });
</script> --}}
