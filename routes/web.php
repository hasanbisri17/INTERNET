<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Middleware\VerifyCsrfToken;

Route::redirect('/', '/admin');

// GitHub Webhook route
Route::match(['get', 'post'], '/webhook', [WebhookController::class, 'handle'])->name('webhook');

// Payment Webhooks
Route::match(['get','post'], '/webhooks/payments/{gateway}', [PaymentWebhookController::class, 'handle'])
    ->where('gateway', '[A-Za-z0-9_-]+')
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->name('webhooks.payments');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('/invoice/{payment}/download', [InvoiceController::class, 'download'])
    ->middleware(['auth'])
    ->where('payment', '[0-9]+')
    ->missing(function () {
        return redirect()->back()->with('error', 'Invoice not found');
    })
    ->name('invoice.download');

require __DIR__.'/auth.php';
