<?php

use App\Http\Controllers\Api\CargoTraceApiController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [CargoTraceApiController::class, 'register']);
Route::post('/login', [CargoTraceApiController::class, 'login']);

Route::middleware('api.auth')->group(function () {
    Route::post('/logout', [CargoTraceApiController::class, 'logout']);
    Route::get('/user', [CargoTraceApiController::class, 'me']);

    Route::get('/shipments', [CargoTraceApiController::class, 'indexShipments']);
    Route::post('/shipments', [CargoTraceApiController::class, 'storeShipment']);
    Route::get('/shipments/{shipment}', [CargoTraceApiController::class, 'showShipment']);
    Route::match(['put', 'patch'], '/shipments/{shipment}', [CargoTraceApiController::class, 'updateShipment']);
    Route::delete('/shipments/{shipment}', [CargoTraceApiController::class, 'destroyShipment']);

    Route::get('/shipments/{shipment}/tracking', [CargoTraceApiController::class, 'trackingIndex']);
    Route::post('/shipments/{shipment}/tracking', [CargoTraceApiController::class, 'storeTrackingEvent']);

    Route::get('/carriers', [CargoTraceApiController::class, 'indexCarriers']);
    Route::post('/carriers', [CargoTraceApiController::class, 'storeCarrier']);

    Route::get('/locations', [CargoTraceApiController::class, 'indexLocations']);
    Route::post('/locations', [CargoTraceApiController::class, 'storeLocation']);

    Route::get('/shipments/{shipment}/exceptions', [CargoTraceApiController::class, 'indexExceptions']);
    Route::post('/shipments/{shipment}/exceptions', [CargoTraceApiController::class, 'storeException']);
    Route::patch('/exceptions/{exception}', [CargoTraceApiController::class, 'updateException']);
});