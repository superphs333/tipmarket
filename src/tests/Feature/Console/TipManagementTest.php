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

    $tip = Tip::query()->create([
        'user_id' => $support->id,
        'title' => '지원 담당자 팁',
        'content' => '<p>지원 담당자 팁 본문</p>',
        'status' => Tip::STATUS_DRAFT,
        'audience' => Tip::AUDIENCE_PRIVATE,
    ]);

    $this->actingAs($support)
        ->get(route('console.tips.create'))
        ->assertForbidden();

    $this->actingAs($support)
        ->get(route('console.tips.edit', $tip))
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

test('tip creation and update policy follows general ownership rules', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $admin = createConsoleUserWithRole(Role::ADMIN);

    $tip = Tip::query()->create([
        'user_id' => $owner->id,
        'title' => '작성자 팁',
        'content' => '<p>작성자 팁 본문</p>',
        'status' => Tip::STATUS_DRAFT,
        'audience' => Tip::AUDIENCE_PRIVATE,
    ]);

    expect($owner->can('create', Tip::class))->toBeTrue()
        ->and($owner->can('update', $tip))->toBeTrue()
        ->and($otherUser->can('update', $tip))->toBeFalse()
        ->and($admin->can('update', $tip))->toBeTrue();
});

test('tip managers can see the tip search and list area', function () {
    $contentManager = createConsoleUserWithRole(Role::CONTENT_MANAGER);

    $this->actingAs($contentManager)
        ->get(route('console.tips.index'))
        ->assertOk()
        ->assertSee('카테고리')
        ->assertSee('노출')
        ->assertSee('상태')
        ->assertSee('태그')
        ->assertSee('기간')
        ->assertSee('검색어')
        ->assertSee('팁 목록')
        ->assertSee('초기화')
        ->assertSee('검색');
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
    ]);

    $this->actingAs($contentManager)
        ->get(route('console.tips.index'))
        ->assertOk()
        ->assertSee('Tips 관리')
        ->assertSee('총 2개')
        ->assertSee('최근 수정:')
        ->assertSee('2026-05-03')
        ->assertSee('AI로 팁 추가')
        ->assertSee('Tip 추가')
        ->assertSee(route('console.tips.create'), false);
});

test('tip managers can use the shared create and edit form screens', function () {
    $contentManager = createConsoleUserWithRole(Role::CONTENT_MANAGER);

    $tip = Tip::query()->create([
        'user_id' => $contentManager->id,
        'title' => '수정할 팁',
        'content' => '<p>수정할 팁 본문</p>',
        'status' => Tip::STATUS_DRAFT,
        'audience' => Tip::AUDIENCE_PRIVATE,
    ]);

    $this->actingAs($contentManager)
        ->get(route('console.tips.create'))
        ->assertOk()
        ->assertSee('Tip 추가')
        ->assertSee('새 팁을 작성합니다.')
        ->assertSee('등록 저장');

    $this->actingAs($contentManager)
        ->get(route('console.tips.edit', $tip))
        ->assertOk()
        ->assertSee('Tip 수정')
        ->assertSee('수정할 팁')
        ->assertSee('수정 저장');
});
