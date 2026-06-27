<?php

use App\Http\Controllers\Console\DashboardController as ConsoleDashboardController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

/**
 * 콘솔 영역 라우트 그룹
 * - role => Role::consoleAccessRoles()가 반환하는 역할 중 하나를 가지고 있어야 함. 
 */
Route::middleware(['auth', 'verified', 'role:'.implode(',', Role::consoleAccessRoles())])
    ->prefix('console') 
    ->name('console.') 
    ->group(function () {
        Route::get('/', ConsoleDashboardController::class)->name('dashboard');

        // 세부 메뉴는 각 기능 라우트에서 역할을 더 좁힌다.
        // 예: Route::middleware('role:admin,content_manager')->group(...);
    });

require __DIR__.'/settings.php';
