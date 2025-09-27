<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-light" id="sidenavAccordion" style="color:#40c47c;">
        <div class="sb-sidenav-menu">
            <!-- Common Navigation for All Authenticated Users -->
            <div class="nav">
                <div class="sb-sidenav-menu-heading">Main</div>

                <!-- Always accessible links -->
                <a class="nav-link" href="{{ route('home') }}">
                    <div class="sb-nav-link-icon"><i class="fas fa-home"></i></div>
                    Home
                </a>
                <a class="nav-link" href="{{ route('users.show', ['user' => auth()->user()->id]) }}">
                    <div class="sb-nav-link-icon"><i class="far fa-address-card"></i></div>
                    Profile
                </a>
            </div>

            <!-- Admin Specific Navigation -->
            @can('Admin')
                <div class="nav">
                    <div class="sb-sidenav-menu-heading">Administration</div>

                    <!-- System Management Section -->
                    <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
                        data-bs-target="#collapseSystemManagement" aria-expanded="false"
                        aria-controls="collapseSystemManagement">
                        <div class="sb-nav-link-icon"><i class="fas fa-cogs"></i></div>
                        System Management
                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                    </a>
                    <div class="collapse" id="collapseSystemManagement" data-bs-parent="#sidenavAccordion">
                        <nav class="sb-sidenav-menu-nested nav">
                            <a class="nav-link" href="{{ route('divisions.index') }}">
                                <i class="fas fa-sitemap me-2"></i>Division Management
                            </a>
                            <a class="nav-link" href="{{ route('companies.index') }}">
                                <i class="fas fa-building me-2"></i>Company Management
                            </a>
                            <a class="nav-link" href="{{ route('departments.index') }}">
                                <i class="fas fa-network-wired me-2"></i>Department Management
                            </a>
                            <a class="nav-link" href="{{ route('designations.index') }}">
                                <i class="fas fa-user-tag me-2"></i>Designation Management
                            </a>
                            <a class="nav-link" href="{{ route('buyers.index') }}">
                                <i class="fas fa-users me-2"></i>Buyer Management
                            </a>
                        </nav>
                    </div>

                    <!-- User Management Section -->
                    <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
                        data-bs-target="#collapseUserManagement" aria-expanded="false"
                        aria-controls="collapseUserManagement">
                        <div class="sb-nav-link-icon"><i class="fas fa-users-cog"></i></div>
                        User Management
                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                    </a>
                    <div class="collapse" id="collapseUserManagement" data-bs-parent="#sidenavAccordion">
                        <nav class="sb-sidenav-menu-nested nav">
                            <a class="nav-link" href="{{ route('roles.index') }}">
                                <i class="fas fa-user-shield me-2"></i>Role Management
                            </a>
                            <a class="nav-link" href="{{ route('users.index') }}">
                                <i class="fas fa-user-friends me-2"></i>User Management
                            </a>
                            <a class="nav-link" href="{{ route('online_user') }}">
                                <i class="fas fa-user-check me-2"></i>Online Users
                            </a>
                        </nav>
                    </div>
                </div>
            @endcan

            <!-- Master Data Section - Accessible by Admin and Supervisor -->
            @canany(['Admin', 'Supervisor'])
                <div class="nav">
                    <div class="sb-sidenav-menu-heading">Master Data</div>

                    <!-- Product Master Data -->
                    <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
                        data-bs-target="#collapseProductMaster" aria-expanded="false" aria-controls="collapseProductMaster">
                        <div class="sb-nav-link-icon"><i class="fas fa-cube"></i></div>
                        Product Management
                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                    </a>
                    <div class="collapse" id="collapseProductMaster" data-bs-parent="#sidenavAccordion">
                        <nav class="sb-sidenav-menu-nested nav">
                            @canany(['Admin', 'Supervisor'])
                                <a class="nav-link" href="{{ route('old_data_index') }}">
                                    <i class="fas fa-database me-2"></i>Old Data Entry
                                </a>
                                <a class="nav-link" href="{{ route('styles.index') }}">
                                    <i class="fas fa-tshirt me-2"></i>Styles Management
                                </a>
                                <a class="nav-link" href="{{ route('colors.index') }}">
                                    <i class="fas fa-palette me-2"></i>Color Management
                                </a>
                                <a class="nav-link" href="{{ route('sizes.index') }}">
                                    <i class="fas fa-ruler-combined me-2"></i>Size Management
                                </a>
                                <a class="nav-link" href="{{ route('product-combinations.index') }}">
                                    <i class="fas fa-cubes me-2"></i>Product Combinations
                                </a>
                            @endcanany
                        </nav>
                    </div>
                </div>
            @endcanany

            <!-- Production Process Section -->
            <div class="nav">
                <div class="sb-sidenav-menu-heading">Production Process</div>

                <!-- Order Management -->
                @canany(['Admin', 'Supervisor', 'OrderDataEntry'])
                    <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
                        data-bs-target="#collapseOrderManagement" aria-expanded="false"
                        aria-controls="collapseOrderManagement">
                        <div class="sb-nav-link-icon"><i class="fas fa-clipboard-list"></i></div>
                        Order Management
                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                    </a>
                    <div class="collapse" id="collapseOrderManagement" data-bs-parent="#sidenavAccordion">
                        <nav class="sb-sidenav-menu-nested nav">
                            <a class="nav-link" href="{{ route('order_data.index') }}">
                                <i class="fas fa-file-purchase-order me-2"></i>PO Information
                            </a>
                        </nav>
                    </div>
                @endcanany

                <!-- Cutting Process -->
                @canany(['Admin', 'Cutting', 'Supervisor'])
                    <a class="nav-link" href="{{ route('cutting_data.index') }}">
                        <div class="sb-nav-link-icon"><i class="fas fa-cut"></i></div>
                        Cutting Data
                    </a>
                @endcanany

                <!-- Printing Process -->
                @canany(['Admin', 'Print Send', 'Print Receive', 'Supervisor'])
                    <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
                        data-bs-target="#collapsePrinting" aria-expanded="false" aria-controls="collapsePrinting">
                        <div class="sb-nav-link-icon"><i class="fas fa-print"></i></div>
                        Printing Process
                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                    </a>
                    <div class="collapse" id="collapsePrinting" data-bs-parent="#sidenavAccordion">
                        <nav class="sb-sidenav-menu-nested nav">
                            @canany(['Admin', 'Print Send', 'Supervisor'])
                                <a class="nav-link" href="{{ route('sublimation_print_send_data.index') }}">
                                    <i class="fas fa-paper-plane me-2"></i>Sublimation Print Send
                                </a>
                            @endcanany
                            @canany(['Admin', 'Print Receive', 'Supervisor'])
                                <a class="nav-link" href="{{ route('sublimation_print_receive_data.index') }}">
                                    <i class="fas fa-truck-loading me-2"></i>Sublimation Print Receive
                                </a>
                            @endcanany
                            @canany(['Admin', 'Print Send', 'Supervisor'])
                                <a class="nav-link" href="{{ route('print_send_data.index') }}">
                                    <i class="fas fa-share-square me-2"></i>Print/Emb Send
                                </a>
                            @endcanany


                            @canany(['Admin', 'Print Receive', 'Supervisor'])
                                <a class="nav-link" href="{{ route('print_receive_data.index') }}">
                                    <i class="fas fa-inbox me-2"></i>Print/Emb Receive
                                </a>
                            @endcanany
                        </nav>
                    </div>
                @endcanany

                <!-- Sewing Process -->
                @canany(['Admin', 'Input', 'Output', 'Supervisor'])
                    <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
                        data-bs-target="#collapseSewing" aria-expanded="false" aria-controls="collapseSewing">
                        <div class="sb-nav-link-icon"><i class="fas fa-tools"></i></div>
                        Sewing Process
                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                    </a>
                    <div class="collapse" id="collapseSewing" data-bs-parent="#sidenavAccordion">
                        <nav class="sb-sidenav-menu-nested nav">
                            @canany(['Admin', 'Input', 'Supervisor'])
                                <a class="nav-link" href="{{ route('line_input_data.index') }}">
                                    <i class="fas fa-keyboard me-2"></i>Sewing Input
                                </a>
                            @endcanany

                            @canany(['Admin', 'Output', 'Supervisor'])
                                <a class="nav-link" href="{{ route('output_finishing_data.index') }}">
                                    <i class="fas fa-check-circle me-2"></i>Sewing Output
                                </a>
                            @endcanany
                        </nav>
                    </div>
                @endcanany

                <!-- Finishing Process -->
                @canany(['Admin', 'Packing', 'Shipment', 'Supervisor'])
                    <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
                        data-bs-target="#collapseFinishing" aria-expanded="false" aria-controls="collapseFinishing">
                        <div class="sb-nav-link-icon"><i class="fas fa-box"></i></div>
                        Finishing Process
                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                    </a>
                    <div class="collapse" id="collapseFinishing" data-bs-parent="#sidenavAccordion">
                        <nav class="sb-sidenav-menu-nested nav">
                            @canany(['Admin', 'Packing', 'Supervisor'])
                                <a class="nav-link" href="{{ route('finish_packing_data.index') }}">
                                    <i class="fas fa-box me-2"></i>Packing Data
                                </a>
                            @endcanany

                            @canany(['Admin', 'Shipment', 'Supervisor'])
                                <a class="nav-link" href="{{ route('shipment_data.index') }}">
                                    <i class="fas fa-shipping-fast me-2"></i>Shipment Data
                                </a>
                            @endcanany
                        </nav>
                    </div>
                @endcanany
            </div>

            <!-- Reports Section -->
            @canany(['Admin', 'Supervisor', 'General', 'Cutting', 'Print Send', 'Print Receive', 'Input', 'Output',
                'Packing', 'Shipment'])
                <div class="nav">
                    <div class="sb-sidenav-menu-heading">Reports & Analytics</div>

                    <!-- Consolidated Reports -->
                    <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
                        data-bs-target="#collapseReports" aria-expanded="false" aria-controls="collapseReports">
                        <div class="sb-nav-link-icon"><i class="fas fa-chart-bar"></i></div>
                        Production Reports
                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                    </a>
                    <div class="collapse" id="collapseReports" data-bs-parent="#sidenavAccordion">
                        <nav class="sb-sidenav-menu-nested nav">

                            <!-- Cutting Reports -->
                            @canany(['Admin', 'Cutting', 'Supervisor', 'General'])
                                <a class="nav-link" href="{{ route('cutting_data_report') }}">
                                    <i class="fas fa-cut me-2"></i>Cutting Report
                                </a>
                                <a class="nav-link" href="{{ route('cutting_requisition') }}">
                                    <i class="fas fa-file-invoice me-2"></i>Cutting Requisition
                                </a>
                            @endcanany

                            <!-- Printing Reports -->
                            @canany(['Admin', 'Print Send', 'Print Receive', 'Supervisor', 'General'])
                                <a class="nav-link" href="{{ route('sublimation_print_send_data.report.total') }}">
                                    <i class="fas fa-print me-2"></i>Total Sublimation Send
                                </a>
                                <a class="nav-link" href="{{ route('print_send_data.report.total') }}">
                                    <i class="fas fa-share-square me-2"></i>Total Print/Emb Send
                                </a>
                                <a class="nav-link"
                                    href="{{ route('sublimation_print_receive_data.report.total_receive') }}">
                                    <i class="fas fa-inbox me-2"></i>Total Sublimation Receive
                                </a>
                            @endcanany

                            <!-- Sewing Reports -->
                            @canany(['Admin', 'Input', 'Output', 'Supervisor', 'General'])
                                <a class="nav-link" href="{{ route('line_input_data.report.total_input') }}">
                                    <i class="fas fa-keyboard me-2"></i>Total Sewing Input
                                </a>
                                <a class="nav-link" href="{{ route('output_finishing_data.report.total_balance') }}">
                                    <i class="fas fa-check-circle me-2"></i>Sewing Output Balance
                                </a>
                            @endcanany

                            <!-- Final Reports -->
                            @canany(['Admin', 'Packing', 'Shipment', 'Supervisor', 'General'])
                                <a class="nav-link" href="{{ route('finish_packing_data.report.total_packing') }}">
                                    <i class="fas fa-box me-2"></i>Total Packing
                                </a>
                                <a class="nav-link" href="{{ route('shipment_data.report.total_shipment') }}">
                                    <i class="fas fa-ship me-2"></i>Total Shipment
                                </a>
                                <a class="nav-link" href="{{ route('shipment_data.report.final_balance') }}">
                                    <i class="fas fa-balance-scale me-2"></i>Final Balance
                                </a>
                                <a class="nav-link" href="{{ route('shipment_data.report.waste') }}">
                                    <i class="fas fa-recycle me-2"></i>Waste Report
                                </a>
                            @endcanany
                        </nav>
                    </div>
                </div>
            @endcanany
        </div>

        <!-- User Footer -->
        <div class="sb-sidenav-footer">
            <div class="small">Logged in as:</div>
            {{ auth()->user()->role->name ?? 'N/A' }}
        </div>
    </nav>
</div>
{{-- 
<!-- JavaScript for sidebar state persistence with cookies -->
<script>
    // Cookie utility functions
    const CookieManager = {
        setCookie(name, value, days) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/;SameSite=Lax`;
        },

        getCookie(name) {
            const nameEQ = name + "=";
            const ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        },

        deleteCookie(name) {
            document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        }
    };

    // Sidebar state management
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
                CookieManager.setCookie(this.cookieName, JSON.stringify(this.state), 365); // Save for 1 year
            } catch (error) {
                console.error('Error saving sidebar state:', error);
            }
        }

        getCollapseState(collapseId) {
            return this.state[collapseId] || 'hidden';
        }

        setCollapseState(collapseId, state) {
            this.state[collapseId] = state;
            this.saveState();
        }

        initializeCollapse(collapseElement) {
            const collapseId = collapseElement.id;

            // Avoid double initialization
            if (this.initializedCollapses.has(collapseId)) {
                return;
            }

            this.initializedCollapses.add(collapseId);
            const bsCollapse = new bootstrap.Collapse(collapseElement, {
                toggle: false
            });

            // Set initial state from cookie
            const savedState = this.getCollapseState(collapseId);
            if (savedState === 'shown') {
                bsCollapse.show();
            } else {
                bsCollapse.hide();
            }

            // Add event listeners
            collapseElement.addEventListener('show.bs.collapse', () => {
                this.setCollapseState(collapseId, 'shown');
            });

            collapseElement.addEventListener('hide.bs.collapse', () => {
                this.setCollapseState(collapseId, 'hidden');
            });

            // Also handle the trigger link's aria-expanded attribute
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
            const collapseElements = document.querySelectorAll('#sidenavAccordion .collapse');
            collapseElements.forEach(collapse => {
                this.initializeCollapse(collapse);
            });
        }

        // Method to manually reset all states (for debugging)
        resetAllStates() {
            this.state = {};
            this.saveState();
            location.reload();
        }

        // Method to expand all sections (for power users)
        expandAll() {
            const collapseElements = document.querySelectorAll('#sidenavAccordion .collapse');
            collapseElements.forEach(collapse => {
                const bsCollapse = bootstrap.Collapse.getInstance(collapse);
                if (bsCollapse) {
                    bsCollapse.show();
                }
            });
        }

        // Method to collapse all sections
        collapseAll() {
            const collapseElements = document.querySelectorAll('#sidenavAccordion .collapse');
            collapseElements.forEach(collapse => {
                const bsCollapse = bootstrap.Collapse.getInstance(collapse);
                if (bsCollapse) {
                    bsCollapse.hide();
                }
            });
        }
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Check if Bootstrap Collapse is available
        if (typeof bootstrap === 'undefined' || !bootstrap.Collapse) {
            console.error('Bootstrap Collapse component not found');
            return;
        }

        const sidebarManager = new SidebarStateManager();
        sidebarManager.initializeAllCollapses();

        // Add debug methods to window for testing (remove in production)
        if (typeof window !== 'undefined' && process.env.NODE_ENV === 'development') {
            window.sidebarManager = sidebarManager;
        }
    });

    // Fallback for Turbolinks or similar navigation
    document.addEventListener('turbolinks:load', function() {
        if (window.sidebarManager) {
            window.sidebarManager.initializeAllCollapses();
        }
    });
</script> --}}
