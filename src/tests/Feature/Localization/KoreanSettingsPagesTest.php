<?php

namespace Tests\Feature\Localization;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class KoreanSettingsPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.locale' => 'ko']);
        app()->setLocale('ko');
    }

    public function test_profile_page_renders_in_korean(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('프로필 설정 -', false)
            ->assertSee('프로필과 계정 설정을 관리하세요')
            ->assertSee('프로필 이미지')
            ->assertSee('프로필 이미지, 이름, 이메일 주소를 수정하세요')
            ->assertSee('이미지 선택')
            ->assertSee('선택된 파일 없음');
    }

    public function test_confirm_password_page_renders_in_korean(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('password.confirm'))
            ->assertOk()
            ->assertSee('비밀번호 확인 -', false)
            ->assertSee('보안이 필요한 영역입니다. 계속하려면 비밀번호를 확인해 주세요.');
    }

    public function test_security_page_renders_in_korean(): void
    {
        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.edit'))
            ->assertOk()
            ->assertSee('보안 설정 -', false)
            ->assertSee('비밀번호 변경')
            ->assertSee('2단계 인증');
    }

    public function test_appearance_page_renders_in_korean(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('appearance.edit'))
            ->assertOk()
            ->assertSee('외형 설정 -', false)
            ->assertSee('계정의 외형 설정을 변경하세요')
            ->assertSee('라이트')
            ->assertSee('다크')
            ->assertSee('시스템');
    }
}
