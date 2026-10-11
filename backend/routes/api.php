<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransactionController;

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

Route::get('/dashboard', [TransactionController::class, 'dashboard']);
Route::apiResource('categories', CategoryController::class)->except(['show']);
Route::apiResource('suppliers', SupplierController::class)->except(['show']);
Route::apiResource('items', ItemController::class)->except(['show']);
Route::get('/purchases', [TransactionController::class, 'purchases']);
Route::post('/purchases', [TransactionController::class, 'storePurchase']);
Route::get('/sales', [TransactionController::class, 'sales']);
Route::post('/sales', [TransactionController::class, 'storeSale']);
