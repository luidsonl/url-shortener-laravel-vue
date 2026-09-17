<?php

use App\Http\Controllers\NewPasswordController;
use App\Http\Controllers\ShortLinkController;
use Illuminate\Support\Facades\Route;

Route::get('/api/documentation', function () {
    return view('swagger');
});

Route::get('/', fn () => view('app'));
Route::get('/login', fn () => view('app'));
Route::get('/register', fn () => view('app'));
Route::get('/forgot-password', fn () => view('app'));
Route::get('/dashboard', fn () => view('app'));
Route::get('/profile', fn () => view('app'));

Route::get('/reset-password', [NewPasswordController::class, 'resetPasswordForm'])
    ->name('password.reset');

Route::post('/reset-password', [NewPasswordController::class, 'reset'])
    ->name('password.update.web');

Route::get('/{code}', [ShortLinkController::class, 'redirect'])
    ->where('code', '[A-Za-z0-9]+')
    ->name('shortlink.redirect');