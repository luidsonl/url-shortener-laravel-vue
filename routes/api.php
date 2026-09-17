<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShortLinkController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\NewPasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [\App\Http\Controllers\HealthCheckController::class, 'check']);

Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->middleware(['signed'])
    ->name('verification.verify');

Route::post('/email/resend', [VerificationController::class, 'resend'])
    ->name('verification.resend');

Route::post('/forgot-password', [NewPasswordController::class, 'forgotPassword'])
    ->name('password.email');

Route::post('/reset-password', [NewPasswordController::class, 'reset'])
    ->name('password.update');


Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/validate-token', [AuthController::class, 'validateToken']);
});

Route::middleware('auth:api')->group(function () {
    // Admin only routes
    Route::middleware('isAdmin')->group(function () {
        Route::middleware('hasVerifiedEmail')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::get('/users/{id}', [UserController::class, 'show']);
            Route::put('/users/{id}', [UserController::class, 'update']);
            Route::delete('/users/{id}', [UserController::class, 'destroy']);
        });
    });
    
    Route::get('/profile', [ProfileController::class, 'show']);
    
    Route::middleware('hasVerifiedEmail')->group(function () {
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::delete('/profile', [ProfileController::class, 'destroy']);
        
        // ShortLink routes - requerem autenticação E verificação de email
        Route::prefix('short-links')->group(function () {
            Route::get('/', [ShortLinkController::class, 'index']);
            Route::post('/', [ShortLinkController::class, 'store']);
            Route::get('/{shortLink}', [ShortLinkController::class, 'show']);
            Route::put('/{shortLink}', [ShortLinkController::class, 'update']);
            Route::delete('/{shortLink}', [ShortLinkController::class, 'destroy']);
            Route::post('/bulk-delete', [ShortLinkController::class, 'bulkDestroy']);
        });
    });

    // Authenticated user routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
    });
});