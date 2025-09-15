{{-- <x-backend.layouts.master>

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

    <div class="container">
        <div class="row p-1">
            <div class="col-12 pb-1">
                <div class="card">
                    <div class="text-left p-1 card-header">
                        Module Name
                    </div>

                    <div class="card-body">

                        @can('Admin')
                            <div class="row justify-content-center">
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('home') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-home"></i></div>
                                        Home
                                    </a>
                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('users.show', ['user' => auth()->user()->id]) }}">
                                        <div class="sb-nav-link-icon"><i class="far fa-address-card"></i></div>
                                        Profile
                                    </a>
                                </div>
                                <div class="col-3 pt-1 pb-1">

                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('divisions.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                        Division Management
                                    </a>
                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('companies.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                        Company Management
                                    </a>
                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('departments.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                        Department Management
                                    </a>
                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('designations.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                        Designation Management
                                    </a>
                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('buyers.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                        Buyer Management
                                    </a>
                                </div>
                                
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('roles.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-user-shield"></i></div>
                                        Role
                                    </a>
                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('users.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-user-friends"></i></div>
                                        Users
                                    </a>
                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('online_user') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Online User List
                                    </a>

                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('styles.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Styles Entry
                                    </a>

                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('colors.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Color Entry
                                    </a>

                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('sizes.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        sizes Entry
                                    </a>

                                </div>

                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('product-combinations.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Product Combinations
                                    </a>

                                </div>


                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('order_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Order Data ( PO Information )
                                    </a>

                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('cutting_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Cutting Data
                                    </a>

                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('sublimation_print_send_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Sublimation Print Sends
                                    </a>

                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('sublimation_print_receive_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Sublimation Print Receives
                                    </a>

                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('print_send_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Print/Emb Send Data
                                    </a>

                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('print_receive_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Print/Emb Receive Data
                                    </a>

                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('line_input_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-sticky-note"></i></div>
                                        Sewing Sewing Input Data
                                    </a>

                                </div>

                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('output_finishing_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-sticky-note"></i></div>
                                        Output Finish Data
                                    </a>

                                </div>

                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('finish_packing_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-sticky-note"></i></div>
                                        Packing Data
                                    </a>

                                </div>

                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('shipment_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-sticky-note"></i></div>
                                        Shipment Data
                                    </a>

                                </div>
                                <div class="col-3 pt-1 pb-1">
                                   <a href="{{ route('shipment_data.report.final_balance') }}"
                                            class="btn btn-outline-info btn-block">
                                            <i class="fas fa-balance-scale"></i> Final Balance Report
                                        </a>
                                </div>

                            </div>
                        @endcan


                        @can('General')
                            <div class="row justify-content-center">
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('styles.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Styles Entry
                                    </a>

                                </div>

                            </div>
                        @endcan
                        @can('QC')
                            <div class="row justify-content-center">
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('styles.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Styles Entry
                                    </a>

                                </div>

                            </div>
                        @endcan
                        @can('Supervisor')
                            <div class="row justify-content-center">
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('styles.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Styles Entry
                                    </a>

                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('colors.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Color Entry
                                    </a>

                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('sizes.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        sizes Entry
                                    </a>

                                </div>

                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('product-combinations.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Product Combinations
                                    </a>

                                </div>


                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('order_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Order Data
                                    </a>

                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('cutting_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Cutting Data
                                    </a>

                                </div>
                                    <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('sublimation_print_send_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Sublimation Print Sends
                                    </a>

                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('sublimation_print_receive_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Sublimation Print Receives
                                    </a>

                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('print_send_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Print/Emb Send Data
                                    </a>

                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('print_receive_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Print/Emb Receive Data
                                    </a>

                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('line_input_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-sticky-note"></i></div>
                                        Sewing Line Input Data
                                    </a>

                                </div>

                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('output_finishing_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-sticky-note"></i></div>
                                        Output Finish Data
                                    </a>

                                </div>

                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('finish_packing_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-sticky-note"></i></div>
                                        Packing Data
                                    </a>

                                </div>

                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('shipment_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-sticky-note"></i></div>
                                        Shipment Data
                                    </a>

                                </div>

                            </div>
                        @endcan
                    </div>
                </div>
            </div>
            <div class="col-12 pt-1 pb-1">
                <div class="card">
                    <div class="text-center p-2 card-header bg-info text-white">
                        Reports
                    </div>
                    <div class="card-body">

                        <div class="row p-3">
                            <!-- Cutting Reports -->
                            <div class="col-md-3 mb-3">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <i class="fas fa-cut"></i> Cutting Reports
                                    </div>
                                    <div class="card-body">
                                        <a href="{{ route('cutting_data_report') }}"
                                            class="btn btn-outline-info btn-block mb-2">
                                            <i class="fas fa-chart-bar"></i> Cutting Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                             <!-- Sublimation Print/Send Reports -->
                            <div class="col-md-3 mb-3">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <i class="fas fa-paper-plane"></i> Sublimation Print/Send Reports
                                    </div>
                                    <div class="card-body">
                                        <a href="{{ route('sublimation_print_send_data.report.total') }}"
                                            class="btn btn-outline-primary btn-block mb-2">
                                            Total Print/Emb Send
                                        </a>
                                        <a href="{{ route('sublimation_print_send_data.report.wip') }}"
                                            class="btn btn-outline-primary btn-block mb-2">
                                            WIP (Waiting)
                                        </a>
                                        <a href="{{ route('sublimation_print_send_data.report.ready') }}"
                                            class="btn btn-outline-primary btn-block">
                                            Ready to Input
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Print/Receive Reports -->
                            <div class="col-md-3 mb-3">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <i class="fas fa-truck-loading"></i> Sublimation Print/Receive Reports
                                    </div>
                                    <div class="card-body">
                                        <a href="{{ route('sublimation_print_receive_data.report.total_receive') }}"
                                            class="btn btn-outline-primary btn-block mb-2">
                                            Total Print/Emb Receive
                                        </a>
                                        <a href="{{ route('sublimation_print_receive_data.report.balance_quantity') }}"
                                            class="btn btn-outline-primary btn-block">
                                            Print/Emb Balance
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Print/Send Reports -->
                            <div class="col-md-3 mb-3">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <i class="fas fa-paper-plane"></i> Print/Send Reports
                                    </div>
                                    <div class="card-body">
                                        <a href="{{ route('print_send_data.report.total') }}"
                                            class="btn btn-outline-primary btn-block mb-2">
                                            Total Print/Emb Send
                                        </a>
                                        <a href="{{ route('print_send_data.report.wip') }}"
                                            class="btn btn-outline-primary btn-block mb-2">
                                            WIP (Waiting)
                                        </a>
                                        <a href="{{ route('print_send_data.report.ready') }}"
                                            class="btn btn-outline-primary btn-block">
                                            Ready to Input
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Print/Receive Reports -->
                            <div class="col-md-3 mb-3">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <i class="fas fa-truck-loading"></i> Print/Receive Reports
                                    </div>
                                    <div class="card-body">
                                        <a href="{{ route('print_receive_data.report.total_receive') }}"
                                            class="btn btn-outline-primary btn-block mb-2">
                                            Total Print/Emb Receive
                                        </a>
                                        <a href="{{ route('print_receive_data.report.balance_quantity') }}"
                                            class="btn btn-outline-primary btn-block">
                                            Print/Emb Balance
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Sewing Input Reports -->
                            <div class="col-md-3 mb-3">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <i class="fas fa-keyboard"></i> Sewing Input
                                    </div>
                                    <div class="card-body">
                                        <a href="{{ route('line_input_data.report.total_input') }}"
                                            class="btn btn-outline-primary btn-block mb-2">
                                            Total Input
                                        </a>
                                        <a href="{{ route('line_input_data.report.input_balance') }}"
                                            class="btn btn-outline-primary btn-block">
                                            Input Balance
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- Output Finishing Reports -->
                            <div class="col-md-3 mb-3">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <i class="fas fa-file-alt"></i> Output Finishing
                                    </div>
                                    <div class="card-body">
                                        <a href="{{ route('output_finishing_data.report.total_balance') }}"
                                            class="btn btn-outline-info btn-block mb-2">
                                            <i class="fas fa-balance-scale"></i> Total Balance Report
                                        </a>
                                        <a href="{{ route('sewing_wip') }}"
                                            class="btn btn-outline-info btn-block mb-2">
                                            <i class="fas fa-cogs"></i> Sewing WIP Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Finish Packing Reports -->
                            <div class="col-md-3 mb-3">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <i class="fas fa-box"></i> Finish Packing
                                    </div>
                                    <div class="card-body">
                                        <a href="{{ route('finish_packing_data.report.total_packing') }}"
                                            class="btn btn-outline-info btn-block mb-2">
                                            <i class="fas fa-chart-bar"></i> Total Packing
                                        </a>
                                        <a href="{{ route('finish_packing_data.report.sewing_wip') }}"
                                            class="btn btn-outline-info btn-block mb-2">
                                            <i class="fas fa-cogs"></i> Sewing WIP
                                        </a>
                                        <a href="{{ route('finish_packing_data.report.balance') }}"
                                            class="btn btn-outline-info btn-block">
                                            <i class="fas fa-balance-scale"></i> Balance
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Shipment Reports -->
                            <div class="col-md-3 mb-3">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <i class="fas fa-shipping-fast"></i> Shipment
                                    </div>
                                    <div class="card-body">
                                        <a href="{{ route('shipment_data.report.total_shipment') }}"
                                            class="btn btn-outline-info btn-block mb-2">
                                            <i class="fas fa-ship"></i> Total Shipment
                                        </a>
                                        <a href="{{ route('shipment_data.report.ready_goods') }}"
                                            class="btn btn-outline-info btn-block">
                                            <i class="fas fa-warehouse"></i> Ready Goods
                                        </a>

                                        <a href="{{ route('shipment_data.report.final_balance') }}"
                                            class="btn btn-outline-info btn-block">
                                            <i class="fas fa-balance-scale"></i> Final Balance Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row p-3">
                            <!-- Output Finishing Reports -->

                        </div>

                    </div>
                </div>
            </div>
        </div>
</x-backend.layouts.master> --}}

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

    <div class="container">
        <div class="row p-1 justify-content-center">
            <div class="col-12 pb-1">
                <div class="card">
                    <div class="text-center p-2 card-header bg-info text-white">
                        Module Name
                    </div>

                    <div class="card-body">
                        <div class="row justify-content-center">
                            {{-- Always accessible links (e.g., Home, Profile) --}}
                            <div class="col-3 pt-1 pb-1">
                                <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                    href="{{ route('home') }}">
                                    <div class="sb-nav-link-icon"><i class="fas fa-home"></i></div>
                                    Home
                                </a>
                            </div>
                            <div class="col-3 pt-1 pb-1">
                                <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                    href="{{ route('users.show', ['user' => auth()->user()->id]) }}">
                                    <div class="sb-nav-link-icon"><i class="far fa-address-card"></i></div>
                                    Profile
                                </a>
                            </div>

                            {{-- Admin Specific Modules --}}
                            @can('Admin')
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('divisions.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                        Division Management
                                    </a>
                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('companies.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                        Company Management
                                    </a>
                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('departments.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                        Department Management
                                    </a>
                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('designations.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                        Designation Management
                                    </a>
                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('buyers.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                        Buyer Management
                                    </a>
                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('roles.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-user-shield"></i></div>
                                        Role
                                    </a>
                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('users.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-user-friends"></i></div>
                                        Users
                                    </a>
                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('online_user') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                        Online User List
                                    </a>
                                </div>
                            @endcan

                            {{-- Modules accessible by Admin or General/Supervisor --}}
                            @canany(['Admin', 'General', 'Supervisor'])
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('old_data_index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-tshirt"></i></div>
                                        Old Data Entry
                                    </a>
                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('styles.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-tshirt"></i></div>
                                        Styles Entry
                                    </a>
                                </div>
                            @endcanany

                            @canany(['Admin', 'Supervisor'])
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('colors.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-palette"></i></div>
                                        Color Entry
                                    </a>
                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('sizes.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-ruler-horizontal"></i></div>
                                        Sizes Entry
                                    </a>
                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('product-combinations.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-cubes"></i></div>
                                        Product Combinations
                                    </a>
                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('order_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-clipboard-list"></i></div>
                                        Order Data (PO Information)
                                    </a>
                                </div>
                            @endcanany

                            {{-- Cutting specific module --}}
                            @canany(['Admin', 'Cutting', 'Supervisor'])
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('cutting_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-cut"></i></div>
                                        Cutting Data
                                    </a>
                                </div>
                            @endcanany

                            {{-- Sublimation Print Send specific module --}}
                            @canany(['Admin', 'Print Send', 'Supervisor'])
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('sublimation_print_send_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-print"></i></div>
                                        Sublimation Print Sends
                                    </a>
                                </div>
                            @endcanany

                            {{-- Sublimation Print Receive specific module --}}
                            @canany(['Admin', 'Print Receive', 'Supervisor'])
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('sublimation_print_receive_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-dolly-flatbed"></i></div>
                                        Sublimation Print Receives
                                    </a>
                                </div>
                            @endcanany

                            {{-- Print Send specific module --}}
                            @canany(['Admin', 'Print Send', 'Supervisor'])
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('print_send_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-paper-plane"></i></div>
                                        Print/Emb Send Data
                                    </a>
                                </div>
                            @endcanany

                            {{-- Print Receive specific module --}}
                            @canany(['Admin', 'Print Receive', 'Supervisor'])
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('print_receive_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-box-open"></i></div>
                                        Print/Emb Receive Data
                                    </a>
                                </div>
                            @endcanany

                            {{-- Sewing Input specific module --}}
                            @canany(['Admin', 'Input', 'Supervisor'])
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('line_input_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-sticky-note"></i></div>
                                        Sewing Line Input Data
                                    </a>
                                </div>
                            @endcanany

                            {{-- Output Finishing specific module --}}
                            @canany(['Admin', 'Output', 'Supervisor'])
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('output_finishing_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-check-circle"></i></div>
                                        Output Finish Data
                                    </a>
                                </div>
                            @endcanany

                            {{-- Packing specific module --}}
                            @canany(['Admin', 'Packing', 'Supervisor'])
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('finish_packing_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-box"></i></div>
                                        Packing Data
                                    </a>
                                </div>
                            @endcanany

                            {{-- Shipment specific module --}}
                            @canany(['Admin', 'Shipment', 'Supervisor'])
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{ route('shipment_data.index') }}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-truck"></i></div>
                                        Shipment Data
                                    </a>
                                </div>
                                <div class="col-3 pt-1 pb-1">
                                    <a href="{{ route('shipment_data.report.final_balance') }}"
                                        class="btn btn-outline-info btn-block">
                                        <div class="sb-nav-link-icon"><i class="fas fa-balance-scale"></i></div>
                                        Final Balance Report
                                    </a>
                                </div>
                            @endcanany

                            {{-- HR Specific --}}
                            @can('HR')
                                {{-- Add HR specific links here if any --}}
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{-- route('hr.something') --}}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-users-cog"></i></div>
                                        HR Module (Example)
                                    </a>
                                </div>
                            @endcan

                            {{-- QC Specific --}}
                            @can('QC')
                                <div class="col-3 pt-1 pb-1">
                                    <a class="btn btn-sm btn-outline-primary" style="width: 10rem;"
                                        href="{{-- route('qc.inspections') --}}">
                                        <div class="sb-nav-link-icon"><i class="fas fa-clipboard-check"></i></div>
                                        QC Inspections (Example)
                                    </a>
                                </div>
                            @endcan

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 pt-1 pb-1">
                <div class="card">
                    <div class="text-center p-2 card-header bg-info text-white">
                        Reports
                    </div>
                    <div class="card-body">
                        <div class="row p-3">
                            {{-- Cutting Reports --}}
                            @canany(['Admin', 'Cutting', 'Supervisor'])
                                <div class="col-md-3 mb-3">
                                    <div class="card border-info">
                                        <div class="card-header bg-info text-white">
                                            <i class="fas fa-cut"></i> Cutting Reports
                                        </div>
                                        <div class="card-body">
                                            <a href="{{ route('cutting_data_report') }}"
                                                class="btn btn-outline-info btn-block mb-2">
                                                <i class="fas fa-chart-bar"></i> Cutting Report
                                            </a>
                                            <a href="{{ route('cutting_requisition') }}"
                                                class="btn btn-outline-info btn-block mb-2">
                                                <i class="fas fa-file-invoice"></i> Cutting Requisition Report
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endcanany

                            {{-- Sublimation Print/Send Reports --}}
                            @canany(['Admin', 'Print Send', 'Supervisor'])
                                <div class="col-md-3 mb-3">
                                    <div class="card border-primary">
                                        <div class="card-header bg-primary text-white">
                                            <i class="fas fa-paper-plane"></i> Sublimation Print/Send Reports
                                        </div>
                                        <div class="card-body">
                                            <a href="{{ route('sublimation_print_send_data.report.total') }}"
                                                class="btn btn-outline-primary btn-block mb-2">
                                                Total Print/Emb Send
                                            </a>
                                            <a href="{{ route('sublimation_print_send_data.report.wip') }}"
                                                class="btn btn-outline-primary btn-block mb-2">
                                                WIP (Waiting)
                                            </a>
                                            <a href="{{ route('sublimation_print_send_data.report.ready') }}"
                                                class="btn btn-outline-primary btn-block">
                                                Ready to Input
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endcanany

                            {{-- Sublimation Print/Receive Reports --}}
                            @canany(['Admin', 'Print Receive', 'Supervisor'])
                                <div class="col-md-3 mb-3">
                                    <div class="card border-primary">
                                        <div class="card-header bg-primary text-white">
                                            <i class="fas fa-truck-loading"></i> Sublimation Print/Receive Reports
                                        </div>
                                        <div class="card-body">
                                            <a href="{{ route('sublimation_print_receive_data.report.total_receive') }}"
                                                class="btn btn-outline-primary btn-block mb-2">
                                                Total Print/Emb Receive
                                            </a>
                                            <a href="{{ route('sublimation_print_receive_data.report.balance_quantity') }}"
                                                class="btn btn-outline-primary btn-block">
                                                Print/Emb Balance
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endcanany

                            {{-- Print/Send Reports --}}
                            @canany(['Admin', 'Print Send', 'Supervisor'])
                                <div class="col-md-3 mb-3">
                                    <div class="card border-primary">
                                        <div class="card-header bg-primary text-white">
                                            <i class="fas fa-paper-plane"></i> Print/Send Reports
                                        </div>
                                        <div class="card-body">
                                            <a href="{{ route('print_send_data.report.total') }}"
                                                class="btn btn-outline-primary btn-block mb-2">
                                                Total Print/Emb Send
                                            </a>
                                            <a href="{{ route('print_send_data.report.wip') }}"
                                                class="btn btn-outline-primary btn-block mb-2">
                                                WIP (Waiting)
                                            </a>
                                            <a href="{{ route('print_send_data.report.ready') }}"
                                                class="btn btn-outline-primary btn-block">
                                                Ready to Input
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endcanany

                            {{-- Print/Receive Reports --}}
                            @canany(['Admin', 'Print Receive', 'Supervisor'])
                                <div class="col-md-3 mb-3">
                                    <div class="card border-primary">
                                        <div class="card-header bg-primary text-white">
                                            <i class="fas fa-truck-loading"></i> Print/Receive Reports
                                        </div>
                                        <div class="card-body">
                                            <a href="{{ route('print_receive_data.report.total_receive') }}"
                                                class="btn btn-outline-primary btn-block mb-2">
                                                Total Print/Emb Receive
                                            </a>
                                            <a href="{{ route('print_receive_data.report.balance_quantity') }}"
                                                class="btn btn-outline-primary btn-block">
                                                Print/Emb Balance
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endcanany

                            {{-- Sewing Input Reports --}}
                            @canany(['Admin', 'Input', 'Supervisor'])
                                <div class="col-md-3 mb-3">
                                    <div class="card border-primary">
                                        <div class="card-header bg-primary text-white">
                                            <i class="fas fa-keyboard"></i> Sewing Input
                                        </div>
                                        <div class="card-body">
                                            <a href="{{ route('line_input_data.report.total_input') }}"
                                                class="btn btn-outline-primary btn-block mb-2">
                                                Total Input
                                            </a>
                                            <a href="{{ route('line_input_data.report.input_balance') }}"
                                                class="btn btn-outline-primary btn-block">
                                                Input Balance
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endcanany

                            {{-- Output Finishing Reports --}}
                            @canany(['Admin', 'Output', 'Supervisor'])
                                <div class="col-md-3 mb-3">
                                    <div class="card border-info">
                                        <div class="card-header bg-info text-white">
                                            <i class="fas fa-file-alt"></i> Output Finishing
                                        </div>
                                        <div class="card-body">
                                            <a href="{{ route('output_finishing_data.report.total_balance') }}"
                                                class="btn btn-outline-info btn-block mb-2">
                                                <i class="fas fa-balance-scale"></i> Total Balance Report
                                            </a>
                                            <a href="{{ route('sewing_wip') }}"
                                                class="btn btn-outline-info btn-block mb-2">
                                                <i class="fas fa-cogs"></i> Sewing WIP Report
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endcanany

                            {{-- Finish Packing Reports --}}
                            @canany(['Admin', 'Packing', 'Supervisor'])
                                <div class="col-md-3 mb-3">
                                    <div class="card border-info">
                                        <div class="card-header bg-info text-white">
                                            <i class="fas fa-box"></i> Finish Packing
                                        </div>
                                        <div class="card-body">
                                            <a href="{{ route('finish_packing_data.report.total_packing') }}"
                                                class="btn btn-outline-info btn-block mb-2">
                                                <i class="fas fa-chart-bar"></i> Total Packing
                                            </a>
                                            <a href="{{ route('finish_packing_data.report.sewing_wip') }}"
                                                class="btn btn-outline-info btn-block mb-2">
                                                <i class="fas fa-cogs"></i> Sewing WIP
                                            </a>
                                            <a href="{{ route('finish_packing_data.report.balance') }}"
                                                class="btn btn-outline-info btn-block">
                                                <i class="fas fa-balance-scale"></i> Balance
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endcanany

                            {{-- Shipment Reports --}}
                            @canany(['Admin', 'Shipment', 'Supervisor'])
                                <div class="col-md-3 mb-3">
                                    <div class="card border-info">
                                        <div class="card-header bg-info text-white">
                                            <i class="fas fa-shipping-fast"></i> Shipment
                                        </div>
                                        <div class="card-body">
                                            <a href="{{ route('shipment_data.report.total_shipment') }}"
                                                class="btn btn-outline-info btn-block mb-2">
                                                <i class="fas fa-ship"></i> Total Shipment
                                            </a>
                                            <a href="{{ route('shipment_data.report.ready_goods') }}"
                                                class="btn btn-outline-info btn-block">
                                                <i class="fas fa-warehouse"></i> Ready Goods
                                            </a>

                                            <a href="{{ route('shipment_data.report.final_balance') }}"
                                                class="btn btn-outline-info btn-block">
                                                <i class="fas fa-balance-scale"></i> Final Balance Report
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endcanany
                        </div>
                    </div>
                </div>
            </div>
        </div>
</x-backend.layouts.master>
