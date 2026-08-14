<?php

use Ahinest\LaravelAdvertising\Http\Controllers\AdvertisementController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('advertising.routes.prefix'))
    ->middleware(config('advertising.routes.middleware'))
    ->group(function (): void {
        Route::get('/{advertisement}', [AdvertisementController::class, 'show'])->name('advertising.show');
    });
