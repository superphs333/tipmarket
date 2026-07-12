<?php

use App\Http\Controllers\Console\DashboardController as ConsoleDashboardController;
use App\Http\Controllers\Console\TipController as ConsoleTipController;
use App\Http\Controllers\EditorImageController;
use App\Http\Controllers\TipBookmarkController;
use App\Http\Controllers\TipCommentController;
use App\Http\Controllers\TipController;
use App\Http\Controllers\TipLikeController;
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
Route::middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/tips/create', [TipController::class, 'create'])
            ->can('create', Tip::class)
            ->name('tips.create');

        Route::post('/tips', [TipController::class, 'store'])
            ->can('create', Tip::class)
            ->name('tips.store');

        Route::get('/tip/{tip}/edit', [TipController::class, 'edit'])
            ->can('update', 'tip')
            ->name('tips.edit');

        Route::put('/tip/{tip}', [TipController::class, 'update'])
            ->can('update', 'tip')
            ->name('tips.update');

        Route::delete('/tip/{tip}', [TipController::class, 'destroy'])
            ->can('delete', 'tip')
            ->name('tips.destroy');

        Route::post('/tip/{tip}/like', [TipLikeController::class, 'toggle'])
            ->can('view', 'tip')
            ->name('tips.like.toggle');

        Route::post('/tip/{tip}/bookmark', [TipBookmarkController::class, 'toggle'])
            ->can('view', 'tip')
            ->name('tips.bookmark.toggle');
    });

/**
 * 팁 댓글
 *
 * 목록은 비회원도 조회할 수 있다.
 * 등록은 로그인 사용자만 가능하며 이메일 인증은 요구하지 않는다.
 */
Route::get('/tip/{tip}/comments', [TipCommentController::class, 'index'])
    ->can('view', 'tip')
    ->name('tips.comments.index');

Route::post('/tip/{tip}/comments', [TipCommentController::class, 'store'])
    ->middleware('auth')
    ->can('view', 'tip')
    ->name('tips.comments.store');

Route::delete('/comments/{comment}', [TipCommentController::class, 'destroy'])
    ->middleware('auth')
    ->name('comments.destroy');

Route::patch('/comments/{comment}', [TipCommentController::class, 'update'])
    ->middleware('auth')
    ->name('comments.update');

// 프론트 Tip 상세
Route::get('/tip/{tip}', [TipController::class, 'show'])
    ->can('view', 'tip')
    ->name('tips.show');

require __DIR__.'/settings.php';
