<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RateController;
use App\Http\Controllers\Api\V1\ShipmentController;
use App\Http\Controllers\Api\V1\ShipmentStatusController;
use App\Http\Controllers\Api\V1\TrackingController;
use App\Http\Controllers\Api\V1\WebhookEndpointController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public
    Route::post('auth/token', [AuthController::class, 'store'])->middleware('throttle:10,1');
    Route::get('track/{trackingNumber}', [TrackingController::class, 'show'])->middleware('throttle:30,1');

    // Authenticated with a Sanctum token
    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
        Route::delete('auth/token', [AuthController::class, 'destroy']);

        Route::post('rates/quote', [RateController::class, 'quote']);

        Route::get('shipments', [ShipmentController::class, 'index']);
        Route::post('shipments', [ShipmentController::class, 'store']);
        Route::get('shipments/{shipment}', [ShipmentController::class, 'show']);
        Route::patch('shipments/{shipment}/status', [ShipmentStatusController::class, 'update']);

        Route::get('webhook-endpoints', [WebhookEndpointController::class, 'index']);
        Route::post('webhook-endpoints', [WebhookEndpointController::class, 'store']);
        Route::delete('webhook-endpoints/{webhookEndpoint}', [WebhookEndpointController::class, 'destroy']);
    });
});
