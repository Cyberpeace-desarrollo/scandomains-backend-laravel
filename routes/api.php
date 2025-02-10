<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FoundSuspiciousDomainController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::middleware('api')->prefix('/v1')->group(function (){
    Route::post('auth/login',[AuthController::class,'login']);
    Route::post('auth/loginPermanent', [AuthController::class, 'loginPermanent']);
});

Route::middleware(['auth:sanctum','api'])->prefix('/v1')->group(function(){
    Route::post('auth/createaccount',[AuthController::class, 'create']);
    Route::post('auth/logout',[AuthController::class, 'logout']);
    Route::post('addcustomer', [CustomerController::class, 'addCustomer']);
    Route::get('customers/view', [CustomerController::class, 'viewCustomers']);
    Route::post('/add-domain-to-customer', [CustomerController::class, 'addDomainToCustomer']);
    Route::post('/add-suspicious-domains', [FoundSuspiciousDomainController::class, 'addSuspiciousDomains']);
    Route::get('/suspicious-domains', [FoundSuspiciousDomainController::class, 'viewSuspiciousDomains']);
    Route::post('/change-flag-suspicious', [FoundSuspiciousDomainController::class, 'changeFlagSuspicious']);
    Route::post('/customer/add-image', [CustomerController::class, 'addCustomerImage']);
    Route::delete('/customer/delete-image', [CustomerController::class, 'deleteCustomerImage']);

});
