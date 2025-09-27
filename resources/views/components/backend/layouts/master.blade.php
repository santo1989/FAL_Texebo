<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Productions and Order Tracking Software from NTG, MIS Department" />
    <meta name="author" content="Engr. Md. Hasibul Islam Santo, MIS, NTG" />
    <title>{{ $pageTitle ?? 'FAL' }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />

    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.css" rel="stylesheet" />
    <link href="{{ asset('ui/backend/css/styles.css') }}" rel="stylesheet" />
      <!-- JavaScript Dependencies -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.js"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('ui/backend/js/scripts.js') }}"></script>
    <script src="{{ asset('ui/backend/js/datatables-simple-demo.js') }}"></script>

    <!-- Global Application Script -->
    <script>
        // Cookie Management Utility
        class CookieManager {
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

            static deleteCookie(name) {
                document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
            }
        }

        // Sidebar State Management
        class SidebarStateManager {
            constructor() {
                this.cookieName = 'rmg_sidebar_state';
                this.state = this.loadState();
                this.initializedCollapses = new Set();
            }

            loadState() {
                try {
                    const savedState = CookieManager.getCookie(this.cookieName);
                    return savedState ? JSON.parse(savedState) : {};
                } catch (error) {
                    console.error('Error loading sidebar state:', error);
                    return {};
                }
            }

            saveState() {
                try {
                    CookieManager.setCookie(this.cookieName, JSON.stringify(this.state), 365);
                } catch (error) {
                    console.error('Error saving sidebar state:', error);
                }
            }

            initializeCollapse(collapseElement) {
                const collapseId = collapseElement.id;
                if (this.initializedCollapses.has(collapseId)) return;

                this.initializedCollapses.add(collapseId);
                const bsCollapse = new bootstrap.Collapse(collapseElement, {
                    toggle: false
                });

                // Set initial state from cookie
                const savedState = this.state[collapseId] || 'hidden';
                if (savedState === 'shown') {
                    bsCollapse.show();
                } else {
                    bsCollapse.hide();
                }

                // Event listeners
                collapseElement.addEventListener('show.bs.collapse', () => {
                    this.state[collapseId] = 'shown';
                    this.saveState();
                });

                collapseElement.addEventListener('hide.bs.collapse', () => {
                    this.state[collapseId] = 'hidden';
                    this.saveState();
                });

                // Sync trigger attributes
                const trigger = document.querySelector(`[data-bs-target="#${collapseId}"]`);
                if (trigger) {
                    collapseElement.addEventListener('show.bs.collapse', () => {
                        trigger.classList.remove('collapsed');
                        trigger.setAttribute('aria-expanded', 'true');
                    });

                    collapseElement.addEventListener('hide.bs.collapse', () => {
                        trigger.classList.add('collapsed');
                        trigger.setAttribute('aria-expanded', 'false');
                    });
                }
            }

            initializeAllCollapses() {
                document.querySelectorAll('#sidenavAccordion .collapse').forEach(collapse => {
                    this.initializeCollapse(collapse);
                });
            }
        }

        // Theme Management System
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
                this.applyTheme();
                this.bindEventListeners();
                this.enhanceDropdowns();
            }

            static loadTheme() {
                try {
                    const themeCookie = CookieManager.getCookie('rmg_user_theme');
                    this.currentTheme = themeCookie ? JSON.parse(themeCookie) : {
                        ...this.defaultTheme
                    };
                    if (!themeCookie) this.saveTheme();
                } catch (error) {
                    console.error('Error loading theme:', error);
                    this.currentTheme = {
                        ...this.defaultTheme
                    };
                }
            }

            static saveTheme() {
                CookieManager.setCookie('rmg_user_theme', JSON.stringify(this.currentTheme), 365);
            }

            static applyTheme() {
                // Update CSS variables
                document.documentElement.style.setProperty('--primary-color', this.currentTheme.primary);
                document.documentElement.style.setProperty('--sidebar-color', this.currentTheme.sidebar);
                document.documentElement.style.setProperty('--topbar-color', this.currentTheme.topbar);

                // Update dynamic theme style
                const dynamicTheme = document.getElementById('dynamic-theme');
                if (dynamicTheme) {
                    dynamicTheme.innerHTML = `
                        :root {
                            --primary-color: ${this.currentTheme.primary};
                            --sidebar-color: ${this.currentTheme.sidebar};
                            --topbar-color: ${this.currentTheme.topbar};
                        }
                        #mainTopnav {
                            background: linear-gradient(${this.currentTheme.topbar}, ${this.currentTheme.topbar}, ${this.currentTheme.topbar}) !important;
                        }
                        #sidenavAccordion {
                            color: ${this.currentTheme.sidebar} !important;
                        }
                    `;
                }

                this.updateMetaThemeColor();
                this.updateColorPickers();
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
                    if (picker) picker.value = this.currentTheme[themeKey];
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
                // Enhanced dropdown handling
                document.querySelectorAll('.dropdown, .dropdown-submenu').forEach(dropdown => {
                    // Touch support
                    dropdown.addEventListener('touchstart', function(e) {
                        this.classList.toggle('show');
                        e.preventDefault();
                        e.stopPropagation();
                    });

                    // Desktop hover
                    if (window.innerWidth > 768) {
                        dropdown.addEventListener('mouseenter', () => dropdown.classList.add('show'));
                        dropdown.addEventListener('mouseleave', () => {
                            setTimeout(() => {
                                if (!dropdown.matches(':hover')) dropdown.classList.remove(
                                    'show');
                            }, 300);
                        });
                    }
                });

                // Close dropdowns on outside click
                document.addEventListener('click', (e) => {
                    if (!e.target.closest('.dropdown') && !e.target.closest('.dropdown-submenu')) {
                        document.querySelectorAll('.dropdown.show, .dropdown-submenu.show').forEach(open => {
                            open.classList.remove('show');
                        });
                    }
                });

                // Mobile submenu handling
                document.querySelectorAll('.dropdown-submenu > a').forEach(link => {
                    link.addEventListener('click', function(e) {
                        if (window.innerWidth <= 768) {
                            e.preventDefault();
                            this.nextElementSibling.classList.toggle('show');
                        }
                    });
                });
            }

            static bindEventListeners() {
                // Color pickers
                ['topbarColorPicker', 'sidebarColorPicker', 'primaryColorPicker'].forEach(id => {
                    document.getElementById(id)?.addEventListener('change', (e) => {
                        this.updateColor(id.replace('ColorPicker', ''), e.target.value);
                    });
                });

                // Theme presets
                document.querySelectorAll('.theme-preset').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const preset = e.target.closest('.theme-preset').dataset.preset;
                        this.applyPreset(preset);
                    });
                });

                // Prevent dropdown close on theme picker interaction
                document.querySelectorAll('.theme-picker input, .theme-picker button').forEach(element => {
                    element.addEventListener('click', (e) => e.stopPropagation());
                });
            }

            static showToast(message, type = 'info') {
                document.querySelectorAll('.theme-toast').forEach(toast => toast.remove());

                const toast = document.createElement('div');
                toast.className = `theme-toast alert alert-${type} alert-dismissible fade show position-fixed`;
                toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 250px;';
                toast.innerHTML =
                    `<strong>Theme Updated:</strong> ${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
                document.body.appendChild(toast);

                setTimeout(() => toast.remove(), 3000);
            }
        }

        // Main Application Initialization
        class AppInitializer {
            static init() {
                // Initialize theme system
                ThemeManager.init();

                // Initialize sidebar state
                if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                    const sidebarManager = new SidebarStateManager();
                    sidebarManager.initializeAllCollapses();

                    // Expose for debugging
                    if (process.env.NODE_ENV === 'development') {
                        window.sidebarManager = sidebarManager;
                    }
                }

                // Mobile menu handling
                this.initMobileMenu();

                // Window resize handling
                this.initResizeHandler();
            }

            static initMobileMenu() {
                const navbarToggler = document.querySelector('.navbar-toggler');
                const mainNavbar = document.getElementById('mainNavbar');

                if (navbarToggler && mainNavbar) {
                    navbarToggler.addEventListener('click', function() {
                        const isExpanded = mainNavbar.classList.contains('show');
                        this.setAttribute('aria-expanded', !isExpanded);
                    });
                }
            }

            static initResizeHandler() {
                window.addEventListener('resize', () => {
                    const mainNavbar = document.getElementById('mainNavbar');
                    if (window.innerWidth > 768 && mainNavbar) {
                        mainNavbar.classList.remove('show');
                    }
                });
            }
        }

        // Initialize application when DOM is ready
        document.addEventListener('DOMContentLoaded', () => AppInitializer.init());

        // Turbolinks support (if used)
        document.addEventListener('turbolinks:load', () => {
            if (window.sidebarManager) {
                window.sidebarManager.initializeAllCollapses();
            }
        });
    </script>

    <!-- Page Specific Styles and Scripts -->
    @stack('styles')
    @stack('scripts')

    <!-- Global Styles -->
    <style>
        :root {
            --primary-bg: #e6eefe;
            --card-bg: #f5f7ff;
            --accent-color: #2563eb;
            --primary-color: #40c47c;
            --sidebar-color: #2c3e50;
            --topbar-color: #34495e;
        }

        body {
            background-color: var(--primary-bg);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            margin: 0;
        }

        .container-fluid {
            padding: 0.5rem;
        }

        .main-card {
            background-color: var(--card-bg);
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 0.5rem;
            padding: 1rem;
            transition: all 0.3s ease;
        }

        /* Theme System */
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

        /* Multi-level Dropdown System */
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

        /* Enhanced UI Components */
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.2s ease-in-out;
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

        /* Smooth Transitions */
        #mainTopnav,
        #layoutSidenav_nav,
        .card {
            transition: all 0.3s ease-in-out;
        }

        /* Mobile Responsiveness */
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

            .container-fluid {
                padding: 1rem;
            }

            .main-card {
                margin: 1rem;
                padding: 1.5rem;
            }
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

        /* Bootstrap Overrides */
        .border-left-primary {
            border-left: 4px solid #4e73df !important;
        }

        .border-left-success {
            border-left: 4px solid #1cc88a !important;
        }

        .border-left-info {
            border-left: 4px solid #36b9cc !important;
        }

        .border-left-warning {
            border-left: 4px solid #f6c23e !important;
        }

        .border-left-danger {
            border-left: 4px solid #e74a3b !important;
        }

        .border-left-secondary {
            border-left: 4px solid #858796 !important;
        }

        /* Custom Utilities */
        .btn-hover {
            transition: all 0.3s ease;
            border-radius: 10px;
            padding: 12px 10px;
        }

        .btn-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
    </style>

    <!-- Dynamic Theme Variables -->
    @php
        $userTheme = json_decode($_COOKIE['rmg_user_theme'] ?? '{}', true);
        $primaryColor = $userTheme['primary'] ?? '#40c47c';
        $sidebarColor = $userTheme['sidebar'] ?? '#2c3e50';
        $topbarColor = $userTheme['topbar'] ?? '#34495e';
    @endphp

    <style id="dynamic-theme">
        :root {
            --primary-color: {{ $primaryColor }};
            --sidebar-color: {{ $sidebarColor }};
            --topbar-color: {{ $topbarColor }};
        }

        #mainTopnav {
            background: linear-gradient({{ $topbarColor }}, {{ $topbarColor }}, {{ $topbarColor }}) !important;
        }

        #sidenavAccordion {
            color: {{ $sidebarColor }} !important;
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <x-backend.layouts.partials.top_bar />

    <div id="layoutSidenav">
        <x-backend.layouts.partials.sidebar />

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    {{ $breadCrumb ?? ' ' }}
                    {{ $slot ?? ' ' }}
                </div>
            </main>
        </div>
    </div>
</body>

</html>
