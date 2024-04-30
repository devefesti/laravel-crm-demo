<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MagentoController;
use App\Http\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/token', [MagentoController::class, 'index']);
Route::get('/orders', [MagentoController::class, 'getOrdersToFulfill']);
Route::post('/orders/management/assign/save', [MagentoController::class, 'assignOrders']);
//Route::get('/orders/{id}/details', [OrderController::class, 'show']);
