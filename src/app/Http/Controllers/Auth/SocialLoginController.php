<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\FindOrCreateSocialUser;
use App\Enums\SocialProvider;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

/**
 * 소셜 로그인 진입과 콜백 처리 담당
 */
class SocialLoginController extends Controller
{
    /**
     * 소셜 로그인 페이지로 리다이렉트 -> 해당 소셜 서비스 인증 페이지로 보냄
     */
    public function redirect(string $provider)
    {
        $provider = SocialProvider::fromRoute($provider);

        if ($provider === SocialProvider::Google) {
            return Socialite::driver($provider->value)
                ->with([
                    'access_type' => 'offline',
                    'prompt' => 'consent',
                ])
                ->redirect();
        }

        return Socialite::driver($provider->value)->redirect();
    }

    /**
     * 소셜 로그인 완료 후 provider가 호출하는 콜백을 처리
     * 
     * [과정]
     * provider 검증 -> 소셜 사용자 정보 가져옴 -> 기존 회원을 찾거나 새로 만들기 -> 로그인 처리 -> 세션 재생성 -> 원래 가려던 페이지 | dashboard로 이동
     */
    public function callback(string $provider, FindOrCreateSocialUser $findOrCreateSocialUser)
    {
        $provider = SocialProvider::fromRoute($provider);

        // Socialite를 통해 현재 provider에서 인증된 사용자 정보를 가져옴
        $socialUser = Socialite::driver($provider->value)->user();

        // user 정보 
        $user = $findOrCreateSocialUser->handle($provider, $socialUser);

        // 로그인 처리
        Auth::login($user, true);

        // 세션 ID 재생성 (보안)
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
