<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/payments/{order}/token', [PaymentController::class, 'generateToken']);
Route::get('/payments/{order}', [PaymentController::class, 'show']);