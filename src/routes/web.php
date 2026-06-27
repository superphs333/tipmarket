<?php

use App\Http\Controllers\Console\DashboardController as ConsoleDashboardController;
use App\Http\Controllers\Console\TipController as ConsoleTipController;
use App\Models\Role;
use App\Models\Tip;
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

        Route::get('/tips', ConsoleTipController::class)
            ->can('viewAny', Tip::class)
            ->name('tips.index');
    });

require __DIR__.'/settings.php';
