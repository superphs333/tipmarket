<?php

use App\Livewire\Console\Tips\TipManagementList;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('tips are sorted by the most recently updated by default', function () {
    $user = User::factory()->create();
    $olderTip = createTip($user, ['title' => '이전 수정 팁']);
    $newerTip = createTip($user, ['title' => '최근 수정 팁']);

    Tip::query()->whereKey($olderTip)->update([
        'updated_at' => Carbon::parse('2026-07-17 12:00:00'),
    ]);
    Tip::query()->whereKey($newerTip)->update([
        'updated_at' => Carbon::parse('2026-07-18 12:00:00'),
    ]);

    $component = Livewire::test(TipManagementList::class)
        ->assertSet('sort', 'latest');

    expect($component->viewData('tips')->pluck('id')->all())
        ->toBe([$newerTip->id, $olderTip->id]);
});

test('tips can be sorted by each reaction count', function (string $sort, string $column) {
    $user = User::factory()->create();
    $lowerTip = createTip($user, [
        'title' => "{$sort} 낮은 팁",
        $column => 10,
    ]);
    $higherTip = createTip($user, [
        'title' => "{$sort} 높은 팁",
        $column => 20,
    ]);

    $component = Livewire::test(TipManagementList::class)
        ->set('sort', $sort);

    expect($component->viewData('tips')->pluck('id')->all())
        ->toBe([$higherTip->id, $lowerTip->id]);
})->with([
    '조회순' => ['popular', 'view_count'],
    '좋아요순' => ['likes', 'like_count'],
    '북마크순' => ['bookmarks', 'bookmark_count'],
]);

test('tips with the same sort value are ordered by id descending', function () {
    $user = User::factory()->create();
    $firstTip = createTip($user, [
        'title' => '먼저 생성한 동률 팁',
        'like_count' => 10,
    ]);
    $secondTip = createTip($user, [
        'title' => '나중에 생성한 동률 팁',
        'like_count' => 10,
    ]);

    $component = Livewire::test(TipManagementList::class)
        ->set('sort', 'likes');

    expect($component->viewData('tips')->pluck('id')->all())
        ->toBe([$secondTip->id, $firstTip->id]);
});

test('changing the sort resets the list to the first page', function () {
    $user = User::factory()->create();

    foreach (range(1, 16) as $number) {
        createTip($user, ['title' => "페이지 초기화 팁 {$number}"]);
    }

    Livewire::test(TipManagementList::class)
        ->call('setPage', 2)
        ->assertSet('paginators.page', 2)
        ->set('sort', 'popular')
        ->assertSet('paginators.page', 1);
});

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
