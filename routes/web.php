<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;


Route::get('/', function () {
    return view('home');
});

Route::get("/payment/{order:code}", [PaymentController::class, 'invoice'])->name('invoice');
Route::post("/payment/success", [PaymentController::class, 'handleSuccess'])->name('payment.success');
Route::post("/payment/callback", [PaymentController::class, 'handleCallback'])->name('payment.callback');
