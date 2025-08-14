<?php

use App\Http\Controllers\BuyerController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CuttingDataController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderDataController;
use App\Http\Controllers\OutputFinishingDataController;
use App\Http\Controllers\PrintReceiveDataController;
use App\Http\Controllers\PrintSendDataController;
use App\Http\Controllers\ProductCombinationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\LineInputDataController;
use App\Http\Controllers\SublimationPrintReceiveController;
use App\Http\Controllers\SublimationPrintSendController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FinishPackingDataController;
use App\Http\Controllers\StyleController;
use App\Http\Controllers\ShipmentDataController;
use App\Models\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');

// });

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/s', function () {
    return view('search');
});

Route::get('/user-of-supervisor', function () {
    return view('backend.users.superindex');
})->name('superindex');

//New registration ajax route

Route::get('/get-company-designation/{divisionId}', [CompanyController::class, 'getCompanyDesignations'])->name('get_company_designation');


Route::get('/get-department/{company_id}', [CompanyController::class, 'getdepartments'])->name('get_departments');


Route::middleware('auth')->group(function () {
    // Route::get('/check', function () {
    //     return "Hello world";
    // });

    Route::get('/home', function () {
        return view('backend.home');
    })->name('home');


    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');


    //user

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get(
        '/users/{user}/edit',
        [UserController::class, 'edit']
    )->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/online-user', [UserController::class, 'onlineuserlist'])->name('online_user');

    Route::post('/users/{user}/users_active', [UserController::class, 'user_active'])->name('users.active');

    Route::post('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');

    //divisions

    Route::get('/divisions', [DivisionController::class, 'index'])->name('divisions.index');
    Route::get('/divisions/create', [DivisionController::class, 'create'])->name('divisions.create');
    Route::post('/divisions', [DivisionController::class, 'store'])->name('divisions.store');
    Route::get('/divisions/{division}', [DivisionController::class, 'show'])->name('divisions.show');
    Route::get('/divisions/{division}/edit', [DivisionController::class, 'edit'])->name('divisions.edit');
    Route::put('/divisions/{division}', [DivisionController::class, 'update'])->name('divisions.update');
    Route::delete('/divisions/{division}', [DivisionController::class, 'destroy'])->name('divisions.destroy');

    // companies
    Route::resource('companies', CompanyController::class);

    //departments
    Route::resource('departments', DepartmentController::class);

    // designations
    Route::resource('designations', DesignationController::class);

    ///buyers
    Route::get('/buyers', [BuyerController::class, 'index'])->name('buyers.index');
    Route::get('/buyers/create', [BuyerController::class, 'create'])->name('buyers.create');
    Route::post('/buyers', [BuyerController::class, 'store'])->name('buyers.store');
    Route::get('/buyers/{buyer}', [BuyerController::class, 'show'])->name('buyers.show');
    Route::get('/buyers/{buyer}/edit', [BuyerController::class, 'edit'])->name('buyers.edit');
    Route::put('/buyers/{buyer}', [BuyerController::class, 'update'])->name('buyers.update');
    Route::delete('/buyers/{buyer}', [BuyerController::class, 'destroy'])->name('buyers.destroy');
    Route::post('/buyers/{buyer}/buyers_active', [BuyerController::class, 'buyer_active'])->name('buyers.active');
    Route::get('/get_buyer', [BuyerController::class, 'get_buyer'])->name('get_buyer');

    ///styles
    Route::get('/styles', [StyleController::class, 'index'])->name('styles.index');
    Route::get('/styles/create', [StyleController::class, 'create'])->name('styles.create');
    Route::post('/styles', [StyleController::class, 'store'])->name('styles.store');
    Route::get('/styles/{id}', [StyleController::class, 'show'])->name('styles.show');
    Route::get('/styles/{id}/edit', [StyleController::class, 'edit'])->name('styles.edit');
    Route::put('/styles/{id}', [StyleController::class, 'update'])->name('styles.update');
    Route::delete('/styles/{id}', [StyleController::class, 'destroy'])->name('styles.destroy');
    Route::post('/styles/{id}/style_active', [StyleController::class, 'style_active'])->name('styles.active');

    // Colors
    Route::resource('colors', ColorController::class);
    Route::post('colors/{color}/active', [ColorController::class, 'color_active'])->name('colors.active');

    // Sizes
    Route::resource('sizes', SizeController::class);
    Route::post('sizes/{size}/active', [SizeController::class, 'size_active'])->name('sizes.active');

    Route::resource('product-combinations', ProductCombinationController::class);
    Route::post('product-combinations/{productCombination}/active', [ProductCombinationController::class, 'active'])->name('product-combinations.active');

    Route::post('product-combinations/{productCombination}/print_embroidery', [ProductCombinationController::class, 'print_embroidery'])->name('product-combinations.print_embroidery');
    Route::post('product-combinations/{productCombination}/sublimation_print', [ProductCombinationController::class, 'sublimation_print'])->name('product-combinations.sublimation_print');

    // Order Data Routes
    Route::resource('order_data', OrderDataController::class);
    Route::get('order_data/report/total_order', [OrderDataController::class, 'totalOrderReport'])->name('order_data.report.total_order');
    //order_data.update_status
    Route::patch('/order_data/update_status/{id}', [OrderDataController::class, 'updateStatus'])->name('order_data.update_status');


    // Cutting Data Routes
    Route::get('cutting_data_report', [CuttingDataController::class, 'cutting_data_report'])->name('cutting_data_report');
    Route::get('cutting_data/find', [CuttingDataController::class, 'find'])->name('cutting_data.find'); // Custom route first
    Route::resource('cutting_data', CuttingDataController::class); // Resource route last

    //sublimation print send routes
    Route::resource('sublimation_print_send_data', SublimationPrintSendController::class);

    // Sublimation Print Send Report Routes
    Route::get('/sublimation_print_send_data/reports/total', [SublimationPrintSendController::class, 'totalPrintEmbSendReport'])->name('sublimation_print_send_data.report.total');
    Route::get('/sublimation_print_send_data/reports/wip', [SublimationPrintSendController::class, 'wipReport'])->name('sublimation_print_send_data.report.wip');
    Route::get('/sublimation_print_send_data/reports/ready', [SublimationPrintSendController::class, 'readyToInputReport'])->name('sublimation_print_send_data.report.ready');

    // Sublimation Print Receive Routes

    Route::resource('sublimation_print_receive_data', SublimationPrintReceiveController::class);

    Route::prefix('sublimation_print_receive_data/reports')->name('sublimation_print_receive_data.report.')->group(function () {
        Route::get('/total-receive', [SublimationPrintReceiveController::class, 'totalPrintEmbReceiveReport'])->name('total_receive');
        Route::get('/balance-quantity', [SublimationPrintReceiveController::class, 'totalPrintEmbBalanceReport'])->name('balance_quantity');
    });










    // Print/Send Data Routes
    Route::prefix('print_send_data')->group(function () {
        // CRUD Routes
        Route::get('/', [PrintSendDataController::class, 'index'])->name('print_send_data.index');
        Route::get('/create', [PrintSendDataController::class, 'create'])->name('print_send_data.create');
        Route::post('/', [PrintSendDataController::class, 'store'])->name('print_send_data.store');
        Route::get('/{printSendDatum}', [PrintSendDataController::class, 'show'])->name('print_send_data.show');
        Route::get('/{printSendDatum}/edit', [PrintSendDataController::class, 'edit'])->name('print_send_data.edit');
        Route::put('/{printSendDatum}', [PrintSendDataController::class, 'update'])->name('print_send_data.update');
        Route::delete('/{printSendDatum}', [PrintSendDataController::class, 'destroy'])->name('print_send_data.destroy');

        // Report Routes
        Route::get('/reports/total', [PrintSendDataController::class, 'totalPrintEmbSendReport'])->name('print_send_data.report.total');
        Route::get('/reports/wip', [PrintSendDataController::class, 'wipReport'])->name('print_send_data.report.wip');
        Route::get('/reports/ready', [PrintSendDataController::class, 'readyToInputReport'])->name('print_send_data.report.ready');
    });

    Route::resource('print_receive_data', PrintReceiveDataController::class);

    Route::prefix('print_receive_data/reports')->name('print_receive_data.report.')->group(function () {
        Route::get('/total-receive', [PrintReceiveDataController::class, 'totalPrintEmbReceiveReport'])->name('total_receive');
        Route::get('/balance-quantity', [PrintReceiveDataController::class, 'totalPrintEmbBalanceReport'])->name('balance_quantity');
    });


    Route::resource('line_input_data', LineInputDataController::class);
    Route::get('line_input_data/available_quantities/{productCombination}', [LineInputDataController::class, 'getAvailableQuantities'])->name('line_input_data.available_quantities');

    Route::prefix('line_input_data/reports')->name('line_input_data.report.')->group(function () {
        Route::get('/total-input', [LineInputDataController::class, 'totalInputReport'])->name('total_input');
        Route::get('/input-balance', [LineInputDataController::class, 'inputBalanceReport'])->name('input_balance');
    });

    // Output Finishing Data Routes
    Route::resource('output_finishing_data', OutputFinishingDataController::class);
    Route::get('output_finishing_data/max_quantities/{id}', [OutputFinishingDataController::class, 'maxQuantities'])
        ->name('output_finishing_data.max_quantities');
    Route::get('output_finishing_data/report/total_balance', [OutputFinishingDataController::class, 'totalBalanceReport'])->name('output_finishing_data.report.total_balance');
    Route::get('/output_finishing_data/sewing-wip', [OutputFinishingDataController::class, 'sewingWipReport'])->name('sewing_wip');
    Route::get('output_finishing_data/max_quantities/{id}', [OutputFinishingDataController::class, 'maxQuantities'])
        ->name('output_finishing_data.max_quantities');


    Route::resource('finish_packing_data', FinishPackingDataController::class);
    Route::get('finish_packing_data/available_quantities/{productCombination}', [FinishPackingDataController::class, 'getAvailablePackingQuantities'])->name('finish_packing_data.available_quantities');

    Route::prefix('finish_packing_data/reports')->name('finish_packing_data.report.')->group(function () {
        Route::get('/total-packing', [FinishPackingDataController::class, 'totalPackingReport'])->name('total_packing');
        Route::get('/sewing-wip', [FinishPackingDataController::class, 'sewingWipReport'])->name('sewing_wip');
        Route::get('/balance', [FinishPackingDataController::class, 'balanceReport'])->name('balance');
    });

    // Shipment Data Routes
    Route::resource('shipment_data', ShipmentDataController::class);
    // Route::get('shipment_data/available_quantities/{product_combination}', [ShipmentDataController::class, 'getAvailableShipmentQuantities'])->name('shipment_data.available_quantities');

    Route::get('/shipment_data/available_quantities/{productCombination}', [ShipmentDataController::class, 'getAvailableQuantities'])
        ->name('shipment_data.available_quantities');

    Route::get('shipment_data/report/total_shipment', [ShipmentDataController::class, 'totalShipmentReport'])->name('shipment_data.report.total_shipment');
    Route::get('shipment_data/report/ready_goods', [ShipmentDataController::class, 'readyGoodsReport'])->name('shipment_data.report.ready_goods');

    //finalbalanceReport
    Route::get('shipment_data/report/final_balance', [ShipmentDataController::class, 'finalBalanceReport'])->name('shipment_data.report.final_balance');
});



// Add these routes to web.php
Route::get('/get-colors/{styleId}', [ProductCombinationController::class, 'getColorsByStyle'])
    ->name('get_colors_by_style');

Route::get('/get-combination/{styleId}/{colorId}', [ProductCombinationController::class, 'getCombinationByStyleColor'])
    ->name('get_combination_by_style_color');

// Routes for Product Combinations
Route::get('/get-colors-by-style/{styleId}', [ProductCombinationController::class, 'getColorsByStylecom'])->name('get-colors-by-style');
Route::get('/get-combination-sizes/{styleId}/{colorId}', [ProductCombinationController::class, 'getCombinationSizes'])->name('get-combination-sizes');

Route::get('/get-size-name/{sizeId}', function ($sizeId) {
    $size = \App\Models\Size::find($sizeId);
    return response()->json(['name' => $size ? $size->name : 'Size ' . $sizeId]);
});

// web.php
Route::get('/print_send_data/get-colors/{style_id}', [PrintSendDataController::class, 'getColors']);
Route::get('/print_send_data/get-combination/{style_id}/{color_id}', [PrintSendDataController::class, 'getCombination']);
Route::get('/print_send_data/available/{product_combination_id}', [PrintSendDataController::class, 'available']);


Route::get('/get-order-and-cutting-quantities/{product_combination_id}', [CuttingDataController::class, 'getOrderAndCuttingQuantities'])->name('get.order.and.cutting.quantities');

//create print_receive_data/available_quantities/{product_combination_id}
Route::get('/print_receive_data/available_quantities/{product_combination_id}', [PrintReceiveDataController::class, 'getAvailableReceiveQuantities'])->name('print_receive_data.available_quantities');

//create for print_send_data/available_quantities/{product_combination_id}
Route::get('/print_send_data/available_quantities/{product_combination_id}', [PrintSendDataController::class, 'getAvailableSendQuantities'])->name('print_send_data.available_quantities');












Route::get('/read/{notification}', [NotificationController::class, 'read'])->name('notification.read');


require __DIR__ . '/auth.php';

//php artisan command

Route::get('/foo', function () {
    Artisan::call('storage:link');
});

Route::get('/cleareverything', function () {
    $clearcache = Artisan::call('cache:clear');
    echo "Cache cleared<br>";

    $clearview = Artisan::call('view:clear');
    echo "View cleared<br>";

    $clearconfig = Artisan::call('config:cache');
    echo "Config cleared<br>";
});

Route::get('/key =', function () {
    $key =  Artisan::call('key:generate');
    echo "key:generate<br>";
});

Route::get('/migrate', function () {
    $migrate = Artisan::call('migrate');
    echo "migration create<br>";
});

Route::get('/migrate-fresh', function () {
    $fresh = Artisan::call('migrate:fresh --seed');
    echo "migrate:fresh --seed create<br>";
});

Route::get('/optimize', function () {
    $optimize = Artisan::call('optimize:clear');
    echo "optimize cleared<br>";
});
Route::get('/route-clear', function () {
    $route_clear = Artisan::call('route:clear');
    echo "route cleared<br>";
});

Route::get('/route-cache', function () {
    $route_cache = Artisan::call('route:cache');
    echo "route cache<br>";
});

Route::get('/updateapp', function () {
    $dump_autoload = Artisan::call('dump-autoload');
    echo 'dump-autoload complete';
});
