<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Routes publiques
Route::get('/status', function () {
    return response()->json(['status' => 'OK']);
});

// Routes protégées par authentification
Route::middleware('auth:sanctum')->group(function () {
    // Récupérer les informations de l'utilisateur connecté
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // API v1
    Route::prefix('v1')->group(function () {
        // Authentification par code secret
        Route::prefix('auth')->group(function () {
            Route::post('/code-secret', [\App\Http\Controllers\Api\V1\AuthController::class, 'generateSecretCode']);
            Route::post('/verify-code', [\App\Http\Controllers\Api\V1\AuthController::class, 'verifyCode']);
        });

        // Comptes
        Route::prefix('comptes')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\CompteController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\CompteController::class, 'store']);
            Route::get('/{compte}', [\App\Http\Controllers\Api\V1\CompteController::class, 'show']);
            Route::get('/{compte}/solde', [\App\Http\Controllers\Api\V1\CompteController::class, 'solde']);
            Route::post('/{compte}/bloquer', [\App\Http\Controllers\Api\V1\CompteController::class, 'bloquer']);
            Route::post('/{compte}/debloquer', [\App\Http\Controllers\Api\V1\CompteController::class, 'debloquer']);
        });

        // Soldes (protégé par code secret)
        Route::middleware('code.auth')->group(function () {
            Route::get('/solde', [\App\Http\Controllers\Api\V1\AuthController::class, 'listSoldes']);
        });

        // Transactions
        Route::prefix('transactions')->group(function () {
            Route::post('/transfert', [\App\Http\Controllers\Api\V1\TransactionController::class, 'transfert']);
            Route::get('/historique/{compteId}', [\App\Http\Controllers\Api\V1\TransactionController::class, 'historique']);
        });
    });
});
