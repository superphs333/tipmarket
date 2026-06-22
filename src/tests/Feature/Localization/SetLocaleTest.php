<?php

use App\Models\User;

// 로그인한 사용자의 locale값이 en일 때, 웹 요청을 보낸 후 라라벨의 현재 locale도 'en'으로 바뀌는지 확인 
test('authenticated user locale is applied to web requests', function () {
    // 테스트용 사용자 생성 
    $user = User::factory()->create([
        'locale' => 'en',
    ]);

    // 저장된 사용자 locale이 middleware를 통해 현재 요청 locale로 반영되는지 확인한다.
    $this->actingAs($user) // 테스트 요청을 $user가 로그인 상태로 실행함.
        ->get(route('dashboard'))
        ->assertOk(); // dashboard 페이지가 정상적으로 열렸는지 확인 

    expect(app()->getLocale())->toEqual('en');
        // app()->getLocale() : 현재 라라벨 애플리케이션에 설정된 locale값을 가져옴 
        // 사용자의 locale이 en이므로 요청 후 현재 앱 locale도 en이어야 함 
});

test('unsupported user locale falls back to application locale', function () {
    $user = User::factory()->create([
        'locale' => 'fr',
    ]);

    // 지원하지 않는 locale이 DB에 있더라도 앱 기본 locale로 안전하게 되돌린다.
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();

    expect(app()->getLocale())->toEqual(config('app.locale'));
});

test('localized profile view is rendered with authenticated user locale', function () {
    $user = User::factory()->create([
        'locale' => 'ko',
    ]);

    // SetLocale 적용 후 __('...')가 ko.json 번역값으로 출력되는지 확인한다.
    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('설정')
        ->assertSee('언어')
        ->assertSee('저장');
});
