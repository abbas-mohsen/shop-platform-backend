<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminOrderApiController;
use App\Http\Controllers\Api\CategoryApiController;

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

Route::get('/products', [ProductApiController::class, 'index']);
Route::get('/products/{product}', [ProductApiController::class, 'show']);
Route::get('/categories', [CategoryApiController::class, 'index']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });


    Route::middleware('admin')->prefix('admin')->group(function () {
    Route::get('/orders', [AdminOrderApiController::class, 'index']);
    Route::put('/orders/{order}', [AdminOrderApiController::class, 'updateStatus']);
});

    Route::post('/logout', [AuthController::class, 'logout']);
});