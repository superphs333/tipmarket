<?php

use App\Http\Controllers\Auth\SocialLoginController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('auth/{provider}', [SocialLoginController::class, 'redirect'])
        ->name('social.redirect');

    Route::get('auth/{provider}/callback', [SocialLoginController::class, 'callback'])
        ->name('social.callback');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
