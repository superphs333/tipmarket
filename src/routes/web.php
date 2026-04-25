<?php

use App\Http\Controllers\Auth\SocialLoginController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// 로그인 하지 않은 사용자만 접근 가능한 라우트 그룹
Route::middleware('guest')->group(function () {
    // 소셜 로그인 시작 URL
    Route::get('auth/{provider}', [SocialLoginController::class, 'redirect'])
        ->name('social.redirect');

    // 소셜 로그인 완료 후 provider가 다시 호출하는 콜백 URL 
    Route::get('auth/{provider}/callback', [SocialLoginController::class, 'callback'])
        ->name('social.callback');
});

// 로그인 + 이메일 인증까지 끝난 사용자만 접근 가능한 라우트 그룹 
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
