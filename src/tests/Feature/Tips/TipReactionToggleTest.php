<?php

use App\Models\Tip;
use App\Models\User;

test('authenticated users can toggle a tip like through the json endpoint', function () {
    $user = User::factory()->create();
    $tip = Tip::factory()->create([
        'like_count' => 0,
    ]);

    $this->actingAs($user)
        ->postJson(route('tips.like.toggle', $tip))
        ->assertOk()
        ->assertJson([
            'name' => 'like',
            'active' => true,
            'count' => 1,
            'label' => '좋아요 취소',
            'visible_label' => '좋아요',
        ]);

    expect($tip->refresh()->like_count)->toBe(1)
        ->and($tip->likedUsers()->whereKey($user->id)->exists())->toBeTrue();

    $this->actingAs($user)
        ->postJson(route('tips.like.toggle', $tip))
        ->assertOk()
        ->assertJson([
            'name' => 'like',
            'active' => false,
            'count' => 0,
            'label' => '좋아요',
            'visible_label' => '좋아요',
        ]);

    expect($tip->refresh()->like_count)->toBe(0)
        ->and($tip->likedUsers()->whereKey($user->id)->exists())->toBeFalse();
});

test('authenticated users can toggle a tip bookmark through the json endpoint', function () {
    $user = User::factory()->create();
    $tip = Tip::factory()->create([
        'bookmark_count' => 0,
    ]);

    $this->actingAs($user)
        ->postJson(route('tips.bookmark.toggle', $tip))
        ->assertOk()
        ->assertJson([
            'name' => 'bookmark',
            'active' => true,
            'count' => 1,
            'label' => '북마크 취소',
            'visible_label' => '북마크',
        ]);

    expect($tip->refresh()->bookmark_count)->toBe(1)
        ->and($tip->bookmarkedUsers()->whereKey($user->id)->exists())->toBeTrue();

    $this->actingAs($user)
        ->postJson(route('tips.bookmark.toggle', $tip))
        ->assertOk()
        ->assertJson([
            'name' => 'bookmark',
            'active' => false,
            'count' => 0,
            'label' => '북마크',
            'visible_label' => '북마크',
        ]);

    expect($tip->refresh()->bookmark_count)->toBe(0)
        ->and($tip->bookmarkedUsers()->whereKey($user->id)->exists())->toBeFalse();
});
