<?php

use App\Models\Tip;
use App\Models\User;
use Illuminate\Support\Facades\Redis;

test('guest view increments public tip view count once', function () {
    $tip = Tip::factory()->create([
        'status' => Tip::STATUS_PUBLISHED,
        'audience' => Tip::AUDIENCE_PUBLIC,
        'view_count' => 0,
    ]);

    $redis = Mockery::mock();
    $redis->shouldReceive('set')
        ->once()
        ->andReturn(true);

    Redis::shouldReceive('connection')
        ->once()
        ->andReturn($redis);

    $this->get(route('tips.show', $tip))
        ->assertOk();

    expect($tip->refresh()->view_count)->toBe(1);
});

test('duplicate guest view does not increment tip view count', function () {
    $tip = Tip::factory()->create([
        'status' => Tip::STATUS_PUBLISHED,
        'audience' => Tip::AUDIENCE_PUBLIC,
        'view_count' => 0,
    ]);

    $redis = Mockery::mock();
    $redis->shouldReceive('set')
        ->once()
        ->andReturn(null);

    Redis::shouldReceive('connection')
        ->once()
        ->andReturn($redis);

    $this->get(route('tips.show', $tip))
        ->assertOk();

    expect($tip->refresh()->view_count)->toBe(0);
});

test('authenticated user view increments public tip view count once', function () {
    $user = User::factory()->create();

    $tip = Tip::factory()->create([
        'status' => Tip::STATUS_PUBLISHED,
        'audience' => Tip::AUDIENCE_PUBLIC,
        'view_count' => 0,
    ]);

    $redis = Mockery::mock();
    $redis->shouldReceive('set')
        ->once()
        ->andReturn(true);

    Redis::shouldReceive('connection')
        ->once()
        ->andReturn($redis);

    $this->actingAs($user)
        ->get(route('tips.show', $tip))
        ->assertOk();

    expect($tip->refresh()->view_count)->toBe(1);
});

test('owner view does not increment draft tip view count', function () {
    $user = User::factory()->create();

    $tip = Tip::factory()->create([
        'user_id' => $user->id,
        'status' => Tip::STATUS_DRAFT,
        'audience' => Tip::AUDIENCE_PUBLIC,
        'view_count' => 0,
    ]);

    Redis::shouldReceive('connection')->never();

    $this->actingAs($user)
        ->get(route('tips.show', $tip))
        ->assertOk();

    expect($tip->refresh()->view_count)->toBe(0);
});
