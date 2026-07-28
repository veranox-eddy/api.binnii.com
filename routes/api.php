<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Parent API (v1)
|--------------------------------------------------------------------------
|
| Guardian-facing endpoints only. Everything behind `auth:guardian` is
| scoped to the authenticated guardian's children via `child_guardian` —
| see the policies, not just the queries.
|
*/

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/activation/validate', [AuthController::class, 'validateActivation']);
    Route::post('auth/activation/complete', [AuthController::class, 'completeActivation']);
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:guardian')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);
        Route::get('auth/me', [AuthController::class, 'me']);
    });
});
