<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    // 기본 locale이 ko이므로 회원가입 화면의 주요 문구가 한국어로 렌더링되는지 확인한다.
    $response
        ->assertOk()
        ->assertSee('계정 만들기')
        ->assertSee('이메일 주소')
        ->assertSee('비밀번호 확인')
        ->assertSee('이미 계정이 있으신가요?');
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    // 회원가입 폼에는 언어 입력이 없으므로 앱 기본 locale이 저장되어야 한다.
    expect(User::where('email', 'test@example.com')->value('locale'))->toEqual(config('app.locale'));
});
