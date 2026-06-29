<?php

use App\Models\Role;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Support\Carbon;

function createConsoleUserWithRole(string $roleName): User
{
    $user = User::factory()->create();
    $role = Role::query()->create([
        'name' => $roleName,
        'label' => $roleName,
        'description' => $roleName,
    ]);

    $user->roles()->attach($role);

    return $user;
}

test('guests are redirected from the console tips page', function () {
    $this->get(route('console.tips.index'))
        ->assertRedirect(route('login'));
});

test('non console users cannot visit the console tips page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('console.tips.index'))
        ->assertForbidden();
});

test('support users can enter the console but cannot manage tips', function () {
    $support = createConsoleUserWithRole(Role::SUPPORT);

    $this->actingAs($support)
        ->get(route('console.dashboard'))
        ->assertOk()
        ->assertDontSee('TIPS');

    $this->actingAs($support)
        ->get(route('console.tips.index'))
        ->assertForbidden();
});

test('content managers can manage tips', function () {
    $contentManager = createConsoleUserWithRole(Role::CONTENT_MANAGER);

    $this->actingAs($contentManager)
        ->get(route('console.dashboard'))
        ->assertOk()
        ->assertSee('TIPS');

    $this->actingAs($contentManager)
        ->get(route('console.tips.index'))
        ->assertOk()
        ->assertSee('TIPS');
});

test('admin users can manage tips', function () {
    $admin = createConsoleUserWithRole(Role::ADMIN);

    $this->actingAs($admin)
        ->get(route('console.tips.index'))
        ->assertOk()
        ->assertSee('TIPS');
});

test('tip managers can see the tip summary and creation actions', function () {
    $contentManager = createConsoleUserWithRole(Role::CONTENT_MANAGER);

    Carbon::setTestNow('2026-05-03 09:00:00');

    Tip::query()->create([
        'user_id' => $contentManager->id,
        'title' => '첫 번째 팁',
        'content' => '<p>첫 번째 팁 본문</p>',
        'status' => Tip::STATUS_DRAFT,
    ]);

    Tip::query()->create([
        'user_id' => $contentManager->id,
        'title' => '두 번째 팁',
        'content' => '<p>두 번째 팁 본문</p>',
        'status' => Tip::STATUS_PUBLISHED,
        'published_at' => '2026-05-03 09:00:00',
    ]);

    $this->actingAs($contentManager)
        ->get(route('console.tips.index'))
        ->assertOk()
        ->assertSee('Tips 관리')
        ->assertSee('총 2개')
        ->assertSee('최근 수정:')
        ->assertSee('2026-05-03')
        ->assertSee('AI로 팁 추가')
        ->assertSee('Tip 추가');
});
