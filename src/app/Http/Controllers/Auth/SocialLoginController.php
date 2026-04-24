<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\FindOrCreateSocialUser;
use App\Actions\Auth\ResolvePostLoginRedirect;
use App\Enums\SocialProvider;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Two\InvalidStateException;
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
        $driver = Socialite::driver($provider->value);
        $origin = url()->previous();
        $originHost = parse_url($origin, PHP_URL_HOST);

        if (is_string($origin) && ($originHost === null || $originHost === request()->getHost())) {
            request()->session()->put('social_auth_origin', $origin);
        }

        if ($provider === SocialProvider::Google) {
            return $driver
                ->with([
                    'access_type' => 'offline',
                    'prompt' => 'consent',
                ])
                ->redirect();
        }

        return $driver->redirect();
    }

    /**
     * 소셜 로그인 완료 후 provider가 호출하는 콜백을 처리
     * 
     * [과정]
     * provider 검증 -> 소셜 사용자 정보 가져옴 -> 기존 회원을 찾거나 새로 만들기 -> 로그인 처리 -> 세션 재생성 -> 원래 가려던 페이지 | dashboard로 이동
     */
    public function callback(
        string $provider,
        FindOrCreateSocialUser $findOrCreateSocialUser,
        ResolvePostLoginRedirect $resolvePostLoginRedirect,
    )
    {
        $provider = SocialProvider::fromRoute($provider);
        $providerLabel = $this->providerLabel($provider);
        $driver = Socialite::driver($provider->value);
        $origin = request()->session()->pull('social_auth_origin', route('login'));

        try {
            $socialUser = $driver->user();

            // user 정보 
            $user = $findOrCreateSocialUser->handle($provider, $socialUser);

            // 로그인 처리
            Auth::login($user, true);

            // 세션 ID 재생성 (보안)
            request()->session()->regenerate();

            return redirect()->to($resolvePostLoginRedirect->handle(request(), $origin));
        } catch (InvalidStateException $exception) {
            return redirect()->to($origin)->with('auth_error', "{$providerLabel} 로그인 세션이 만료되었거나 검증에 실패했습니다. 다시 시도해 주세요.");
        } catch (ValidationException $exception) {
            return redirect()->to($origin)->with('auth_error', "{$providerLabel} 계정에서 이메일을 가져오지 못했습니다. {$providerLabel} 계정 이메일 제공 동의와 이메일 인증 상태를 확인해 주세요.");
        } catch (\Throwable $exception) {
            return redirect()->to($origin)->with('auth_error', "{$providerLabel} 로그인 처리 중 오류가 발생했습니다. 잠시 후 다시 시도해 주세요.");
        }
    }

    protected function providerLabel(SocialProvider $provider): string
    {
        return match ($provider) {
            SocialProvider::Google => '구글',
            SocialProvider::Kakao => '카카오',
        };
    }
}
