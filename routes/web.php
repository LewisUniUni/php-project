<?php

declare(strict_types=1);

use App\Http\Controllers\Web\SessionController;
use App\Http\Controllers\Web\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('warehouse.stock')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:login');
});

Route::post('/logout', [SessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'can:warehouse.view'])->prefix('warehouse')->group(function (): void {
    Route::get('/', [WarehouseController::class, 'stock'])->name('warehouse.stock');
    Route::get('/deliveries', [WarehouseController::class, 'deliveries'])->name('warehouse.deliveries');
    Route::get('/scans', [WarehouseController::class, 'scans'])->name('warehouse.scans');

    Route::middleware('can:warehouse.mutate')->group(function (): void {
        Route::post('/receipts', [WarehouseController::class, 'receive'])->name('warehouse.receipts.store');
        Route::post('/reservations', [WarehouseController::class, 'reserve'])->name('warehouse.reservations.store');
        Route::post('/reservations/{reservation}/release', [WarehouseController::class, 'release'])
            ->whereUuid('reservation')
            ->name('warehouse.reservations.release');
        Route::post('/deliveries/{stop}/transitions', [WarehouseController::class, 'transition'])
            ->whereUuid('stop')
            ->name('warehouse.deliveries.transition');
    });
});
