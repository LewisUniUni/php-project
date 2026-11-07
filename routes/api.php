<?php

use App\Http\Controllers\Api\V1\DeliveryStopController;
use App\Http\Controllers\Api\V1\ReceiptController;
use App\Http\Controllers\Api\V1\ReservationController;
use App\Http\Controllers\Api\V1\ScanEventController;
use App\Http\Controllers\Api\V1\StockBalanceController;
use App\Http\Middleware\RequireIdempotencyKey;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckToken;

Route::prefix('v1')->group(function (): void {
    Route::get('stock-balances', [StockBalanceController::class, 'index'])
        ->middleware([CheckToken::using('inventory.read'), 'throttle:api']);

    Route::post('receipts', [ReceiptController::class, 'store'])
        ->middleware([CheckToken::using('inventory.write'), 'throttle:api', RequireIdempotencyKey::class]);

    Route::post('reservations', [ReservationController::class, 'store'])
        ->middleware([CheckToken::using('inventory.write'), 'throttle:api', RequireIdempotencyKey::class]);

    Route::get('reservations/{reservation}', [ReservationController::class, 'show'])
        ->whereUuid('reservation')
        ->middleware([CheckToken::using('inventory.read'), 'throttle:api']);

    Route::post('reservations/{reservation}/release', [ReservationController::class, 'release'])
        ->whereUuid('reservation')
        ->middleware([CheckToken::using('inventory.write'), 'throttle:api', RequireIdempotencyKey::class]);

    Route::get('delivery-stops', [DeliveryStopController::class, 'index'])
        ->middleware([CheckToken::using('delivery.read'), 'throttle:api']);

    Route::get('delivery-stops/{stop}', [DeliveryStopController::class, 'show'])
        ->whereUuid('stop')
        ->middleware([CheckToken::using('delivery.read'), 'throttle:api']);

    Route::post('delivery-stops/{stop}/transitions', [DeliveryStopController::class, 'transition'])
        ->whereUuid('stop')
        ->middleware([CheckToken::using('delivery.write'), 'throttle:api', RequireIdempotencyKey::class]);

    Route::get('scan-events', [ScanEventController::class, 'index'])
        ->middleware([CheckToken::using('telemetry.write'), 'throttle:api']);

    Route::post('scan-events', [ScanEventController::class, 'store'])
        ->middleware([CheckToken::using('telemetry.write'), 'throttle:api', RequireIdempotencyKey::class]);

    Route::get('scan-events/{eventId}', [ScanEventController::class, 'show'])
        ->whereUuid('eventId')
        ->middleware([CheckToken::using('telemetry.write'), 'throttle:api']);
});
