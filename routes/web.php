<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LogoUploadController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Middleware\VerifyCsrfToken;

Route::redirect('/', '/admin');

// Health check route untuk Docker
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'version' => config('app.version', '1.0.0'),
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected'
    ]);
})->name('health');

// Update routes
Route::middleware(['auth'])->group(function () {
    Route::get('/api/update/check', [App\Http\Controllers\UpdateController::class, 'checkUpdate'])->name('update.check');
    Route::post('/api/update/perform', [App\Http\Controllers\UpdateController::class, 'performUpdate'])->name('update.perform');
    Route::get('/api/update/status', [App\Http\Controllers\UpdateController::class, 'getUpdateStatus'])->name('update.status');
});

// GitHub Webhook route
Route::match(['get', 'post'], '/webhook', [WebhookController::class, 'handle'])->name('webhook');

// Payment Webhooks
Route::match(['get','post'], '/webhooks/payments/{gateway}', [PaymentWebhookController::class, 'handle'])
    ->where('gateway', '[A-Za-z0-9_-]+')
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->name('webhooks.payments');

Route::redirect('/dashboard', '/admin')
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

// Logo Upload Routes
Route::post('/upload/logo', [LogoUploadController::class, 'upload'])
    ->middleware(['auth'])
    ->name('upload.logo');

Route::post('/remove/logo', [LogoUploadController::class, 'remove'])
    ->middleware(['auth'])
    ->name('remove.logo');

require __DIR__.'/auth.php';
