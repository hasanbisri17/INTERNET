<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Endpoint sederhana untuk membuat PAT (Personal Access Token) bagi admin (sementara / opsional)
Route::post('/auth/token', function (Request $request) {
    $data = $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
        'device' => 'nullable|string',
        'abilities' => 'nullable|array',
        'abilities.*' => 'string',
    ]);

    $user = User::where('email', $data['email'])->first();

    if (!$user || !Hash::check($data['password'], $user->password)) {
        return response()->json(['message' => 'Kredensial tidak valid'], 401);
    }

    $abilities = $data['abilities'] ?? [];
    if (!is_array($abilities) || count($abilities) === 0) {
        $abilities = [];
    }

    $token = $user->createToken($data['device'] ?? 'api', $abilities)->plainTextToken;

    return response()->json(['token' => $token, 'abilities' => $abilities]);
})->name('api.auth.token');

// Payment Webhooks (no CSRF on api middleware)
Route::match(['post'], '/webhooks/payments/{gateway}', [PaymentWebhookController::class, 'handle'])
    ->where('gateway', '[A-Za-z0-9_-]+')
    ->name('api.webhooks.payments');
