<?php

use App\Http\Controllers\Console\DashboardController as ConsoleDashboardController;
use App\Http\Controllers\Console\TipController as ConsoleTipController;
use App\Http\Controllers\TipController;
use App\Http\Controllers\EditorImageController;
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

        Route::get('/tips/create', [ConsoleTipController::class, 'create'])
            ->can('manageInConsole', Tip::class)
            ->name('tips.create');

        Route::get('/tips/{tip}/edit', [ConsoleTipController::class, 'edit'])
            ->can('manageInConsole', Tip::class)
            ->name('tips.edit');

        /**
         * 콘솔 팁 저장/수정
         *
         * 작성/수정 화면은 console.tips.* 라우트 이름을 기준으로 action을 주입한다.
         * 이 라우트들은 콘솔 전용 권한과 /console prefix가 필요하므로 콘솔 그룹 안에 둔다.
         */
        Route::post('/tips', [ConsoleTipController::class, 'store'])
            ->can('manageInConsole', Tip::class)
            ->name('tips.store');

        Route::put('/tips/{tip}', [ConsoleTipController::class, 'update'])
            ->can('manageInConsole', Tip::class)
            ->name('tips.update');
    });

/**
 * 에디터 이미지
 */
Route::middleware(['auth', 'verified'])
    ->group(function () {
        Route::post('/editor/images', EditorImageController::class)
            ->name('editor.images.store');
    });

/**
 * 프론트 Tip 관련 : /tip/
 */
Route::get('/tip/{tip}', [TipController::class, 'show'])
    ->name('tips.show');

require __DIR__.'/settings.php';
