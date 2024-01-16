<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientInvoiceController;
use App\Http\Controllers\ClientPaymentController;
use App\Http\Controllers\DailyBasisController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DriverInvoiceController;
use App\Http\Controllers\DriverPaymentController;
use App\Http\Controllers\FuelAdvancePaymentController;
use App\Http\Controllers\MonthlyContractController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VendorInvoiceController;
use App\Http\Controllers\VendorPaymentController;
use App\Models\MonthlyContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/verify_token', [AuthController::class, 'verify_auth'])->middleware("auth:api");


//Guarded Route

Route::middleware('auth:api')->group(function () {
    Route::apiResource('clients', ClientController::class);
    Route::apiResource('clientInvoices', ClientInvoiceController::class);
    Route::resource('clientInvoicePayments', ClientPaymentController::class);
    Route::get('clientInvoicePayments/invoice/{id}', [ClientPaymentController::class, 'getPaymentByInvoice']);
    Route::get('clientInvoicePayments/client/{client_id}', [ClientPaymentController::class, 'getPaymentByClient']);

    Route::apiResource('vendors', VendorController::class);
    Route::apiResource('vendorInvoices', VendorInvoiceController::class);
    Route::resource('vendorInvoicePayments', VendorPaymentController::class);
    Route::get('vendorInvoicePayments/invoice/{id}', [VendorPaymentController::class, 'getPaymentByInvoice']);
    Route::get('vendorInvoicePayments/vendor/{vendor_id}', [VendorPaymentController::class, 'getPaymentByVendor']);

    Route::apiResource('drivers', DriverController::class);
    Route::apiResource('driverInvoices', DriverInvoiceController::class);
    Route::resource('driverInvoicePayments', DriverPaymentController::class);
    Route::get('driverInvoicePayments/invoice/{id}', [DriverPaymentController::class, 'getPaymentByInvoice']);
    Route::get('driverInvoicePayments/driver/{driver_id}', [DriverPaymentController::class, 'getPaymentByDriver']);

    Route::apiResource('vehicles', VehicleController::class);


    Route::apiResource('dailyBasis', DailyBasisController::class);
    Route::resource('fuelAdvancePayments', FuelAdvancePaymentController::class);
    Route::get('fuelAdvancePayments/daily-basis/{daily_basis_id}', [FuelAdvancePaymentController::class, "getFuelAdvancePaymentsByDailyBasis"]);

    Route::apiResource('monthlyContracts', MonthlyContractController::class);
    Route::get('fuelAdvancePayments/monthly-contract/{monthly_contract_id}', [FuelAdvancePaymentController::class, "getFuelAdvancePaymentsByDailyBasis"]);



    Route::name('resource.')->prefix('resource')->group(function() {
        Route::get("getDriverList", [ResourceController::class, "getDriverList"])->name("driverList");
        Route::get("getClientList", [ResourceController::class, "getClientList"])->name("clientList");
        Route::get("getVehicleList", [ResourceController::class, "getVehicleList"])->name("vehicleList");
        Route::get("getVendorList", [ResourceController::class, "getVendorList"])->name("vendorList");
    });
});
