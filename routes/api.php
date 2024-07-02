<?php

use App\Http\Controllers\Payment\ClickController;
use App\Http\Controllers\Payment\PaycomController;
use Illuminate\Support\Facades\Route;

/**
 * Payment routes
 */
Route::post('paycom', [PaycomController::class, 'index'])->middleware('paycom');
Route::post('click/prepare', [ClickController::class, 'prepare']);
Route::post('click/complete', [ClickController::class, 'complete']);
