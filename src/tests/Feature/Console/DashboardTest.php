<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;

test('guests are redirected from the console dashboard', function () {
    $this->get(route('console.dashboard'))
        ->assertRedirect(route('login'));
});

test('non console users cannot visit the console dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('console.dashboard'))
        ->assertForbidden();
});

test('admin users can visit the console dashboard', function () {
    $admin = User::factory()->create();
    $role = Role::query()->create([
        'name' => Role::ADMIN,
        'label' => '관리자',
        'description' => '관리자 기능 접근 및 운영 권한을 가진 사용자',
    ]);

    $admin->roles()->attach($role);

    $this->actingAs($admin)
        ->get(route('console.dashboard'))
        ->assertOk()
        ->assertSee('Console Dashboard');
});

test('console roles can visit the console dashboard', function () {
    $operator = User::factory()->create();
    $role = Role::query()->create([
        'name' => Role::MODERATOR,
        'label' => '운영자',
        'description' => '신고 처리와 커뮤니티 운영 기능에 접근할 수 있는 역할',
    ]);

    $operator->roles()->attach($role);

    $this->actingAs($operator)
        ->get(route('console.dashboard'))
        ->assertOk()
        ->assertSee('Console Dashboard');
});

test('console dashboard uses the console layout', function () {
    $admin = User::factory()->create();
    $role = Role::query()->create([
        'name' => Role::ADMIN,
        'label' => '관리자',
        'description' => '관리자 기능 접근 및 운영 권한을 가진 사용자',
    ]);

    $admin->roles()->attach($role);

    $this->actingAs($admin)
        ->get(route('console.dashboard'))
        ->assertOk()
        ->assertSee('TipMarket Console')
        ->assertDontSee('Platform')
        ->assertDontSee('Settings');
});

test('console users can still visit the platform dashboard', function () {
    $operator = User::factory()->create();
    $role = Role::query()->create([
        'name' => Role::SUPPORT,
        'label' => '고객 지원',
        'description' => '사용자 문의와 지원 기능에 접근할 수 있는 역할',
    ]);

    $operator->roles()->attach($role);

    $this->actingAs($operator)
        ->get(route('dashboard'))
        ->assertOk();
});

test('role middleware allows any matching role', function () {
    $user = User::factory()->create();
    $role = Role::query()->create([
        'name' => Role::MODERATOR,
        'label' => '운영자',
        'description' => '콘텐츠 운영 권한을 가진 사용자',
    ]);

    $user->roles()->attach($role);

    Route::middleware(['web', 'auth', 'role:admin,moderator'])->get('/test-role-gate', fn () => 'ok');

    $this->actingAs($user)
        ->get('/test-role-gate')
        ->assertOk()
        ->assertSee('ok');
});

test('console tab routes can restrict roles separately', function () {
    $support = User::factory()->create();
    $supportRole = Role::query()->create([
        'name' => Role::SUPPORT,
        'label' => '고객 지원',
        'description' => '사용자 문의와 지원 기능에 접근할 수 있는 역할',
    ]);

    $contentManager = User::factory()->create();
    $contentManagerRole = Role::query()->create([
        'name' => Role::CONTENT_MANAGER,
        'label' => '콘텐츠 매니저',
        'description' => '팁과 콘텐츠 운영 기능에 접근할 수 있는 역할',
    ]);

    $support->roles()->attach($supportRole);
    $contentManager->roles()->attach($contentManagerRole);

    Route::middleware(['web', 'auth', 'role:admin,content_manager'])->get('/test-console-content-tab', fn () => 'content');

    $this->actingAs($support)
        ->get('/test-console-content-tab')
        ->assertForbidden();

    $this->actingAs($contentManager)
        ->get('/test-console-content-tab')
        ->assertOk()
        ->assertSee('content');
});
