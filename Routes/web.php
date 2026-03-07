<?php

use Illuminate\Support\Facades\Route;
use Modules\SkyOps\Http\Controllers\SkyOpsController;

Route::middleware(['auth'])
    ->prefix('skyops')
    ->name('skyops.')
    ->group(function () {
        Route::get('/', [SkyOpsController::class, 'index'])->name('index');
        Route::get('/pireps', [SkyOpsController::class, 'pirepList'])->name('pireps');
        Route::get('/fleet', [SkyOpsController::class, 'fleet'])->name('fleet');
        Route::get('/pilots', [SkyOpsController::class, 'pilotStats'])->name('pilots');
        Route::get('/airlines', [SkyOpsController::class, 'airlineOverview'])->name('airlines');
        Route::get('/departures', [SkyOpsController::class, 'flightBoard'])->name('departures');
        Route::get('/guide', [SkyOpsController::class, 'guide'])->name('guide');
    });
