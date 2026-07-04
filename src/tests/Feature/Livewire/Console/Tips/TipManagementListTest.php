<?php

use App\Livewire\Console\Tips\TipManagementList;
use App\Models\Tip;
use App\Models\User;
use Livewire\Livewire;

test('selected tips can be published in bulk', function () {
    $user = User::factory()->create();
    $firstTip = createTip($user, ['status' => Tip::STATUS_DRAFT]);
    $secondTip = createTip($user, ['status' => Tip::STATUS_DRAFT]);

    Livewire::test(TipManagementList::class)
        ->set('selectedIds', [(string) $firstTip->id, (string) $secondTip->id])
        ->set('bulkAction', 'status')
        ->set('bulkValue', Tip::STATUS_PUBLISHED)
        ->call('applyBulkAction')
        ->assertSet('selectedIds', [])
        ->assertSet('bulkAction', '')
        ->assertSet('bulkValue', '');

    expect($firstTip->refresh()->status)->toBe(Tip::STATUS_PUBLISHED)
        ->and($secondTip->refresh()->status)->toBe(Tip::STATUS_PUBLISHED);
});

test('selected tips can change audience in bulk', function () {
    $user = User::factory()->create();
    $firstTip = createTip($user, ['audience' => Tip::AUDIENCE_PRIVATE]);
    $secondTip = createTip($user, ['audience' => Tip::AUDIENCE_PRIVATE]);

    Livewire::test(TipManagementList::class)
        ->set('selectedIds', [(string) $firstTip->id, (string) $secondTip->id])
        ->set('bulkAction', 'audience')
        ->set('bulkValue', Tip::AUDIENCE_PUBLIC)
        ->call('applyBulkAction');

    expect($firstTip->refresh()->audience)->toBe(Tip::AUDIENCE_PUBLIC)
        ->and($secondTip->refresh()->audience)->toBe(Tip::AUDIENCE_PUBLIC);
});

test('selected tips can be deleted in bulk', function () {
    $user = User::factory()->create();
    $firstTip = createTip($user);
    $secondTip = createTip($user);

    Livewire::test(TipManagementList::class)
        ->set('selectedIds', [(string) $firstTip->id, (string) $secondTip->id])
        ->call('deleteSelected')
        ->assertSet('selectedIds', []);

    expect(Tip::withTrashed()->find($firstTip->id)?->trashed())->toBeTrue()
        ->and(Tip::withTrashed()->find($secondTip->id)?->trashed())->toBeTrue();
});

/**
 * @param  array<string, mixed>  $overrides
 */
function createTip(User $user, array $overrides = []): Tip
{
    return Tip::query()->create([
        'user_id' => $user->id,
        'title' => '관리자 벌크 테스트 팁',
        'content' => '<p>관리자 벌크 테스트 본문</p>',
        'status' => Tip::STATUS_DRAFT,
        'audience' => Tip::AUDIENCE_PRIVATE,
        ...$overrides,
    ]);
}
