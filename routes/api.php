<?php

use App\Http\Controllers\Api\CallbackController;
use App\Http\Controllers\Api\BankNtbCallbackController;
use App\Http\Controllers\Api\PembayaranApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/pembayaran/status/{nim?}', [PembayaranApiController::class, 'status']);
});

Route::match(['get', 'post'], '/payment/callback/bankntb', BankNtbCallbackController::class);

// Callback endpoint for Bank NTB Syariah webhook
// TODO: Configure this URL in Bank NTB merchant portal as the callback URL
// This endpoint will receive POST requests from Bank server when payment is received
Route::post('/callback/ntbsyariah', [CallbackController::class, 'handleNtbSyariahCallback']);
