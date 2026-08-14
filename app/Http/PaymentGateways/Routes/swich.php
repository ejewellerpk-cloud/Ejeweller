<?php

use App\Http\Controllers\Frontend\SwichPayinController;
use Illuminate\Support\Facades\Route;

Route::prefix('payment')->name('payment.')->middleware(['installed'])->group(function () {
    Route::match(['get', 'post'], '/swich/callback', [SwichPayinController::class, 'callback'])->name('swich.callback');
    Route::get('/swich/{paymentGateway:slug}/{order}/waiting', [SwichPayinController::class, 'waiting'])->name('swich.waiting');
    Route::get('/swich/{paymentGateway:slug}/{order}/status', [SwichPayinController::class, 'status'])->name('swich.status');
});
