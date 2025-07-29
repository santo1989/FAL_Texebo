<?php

use App\Http\Controllers\PrintSendDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get("/check", function () {
        return "Hello check";
    });
});

// API Route for available quantity (add to routes/api.php)
Route::get('/print_send_data/available/{product_combination_id}', function ($productCombinationId) {
    $totalCut = App\Models\CuttingData::where('product_combination_id', $productCombinationId)
        ->sum('total_cut_quantity');

    $totalSent = App\Models\PrintSendData::where('product_combination_id', $productCombinationId)
        ->sum('total_send_quantity');

    return response()->json([
        'available' => $totalCut - $totalSent
    ]);
});




