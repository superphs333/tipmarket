<?php

namespace App\Actions\Auth;

use App\Enums\SocialProvider;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * 소셜 로그인 결과로 전달받은 사용자 정보를 기준으로, 
 * 기존 회원을 찾거나 새 회원을 생성한 뒤
 * social_accounts 테이블에 소셜 계정 연결 정보를 저장
 *
 */
class FindOrCreateSocialUser
{
    /**
     * 소셜 로그인 사용자를 기존 회원에 연결하거나 새로 생성
     */
    public function handle(SocialProvider $provider, SocialiteUser $socialUser): User
    {
        $email = $socialUser->getEmail();

        if (! $email) {
            throw ValidationException::withMessages([
                'email' => '소셜 계정에서 이메일을 가져올 수 없습니다.',
            ]);
        }

        // 단위 : user 생성 + social_accounts 생성 
        return DB::transaction(function () use ($provider, $socialUser, $email) {
            
            // 1차 조회 : provider + provider_user_id => 이미 연결된 소셜 계정이 있는지 확인
            $socialAccount = SocialAccount::query()
                ->with('user')
                ->where('provider', $provider->value)
                ->where('provider_user_id', $socialUser->getId())
                ->first();

            // 이미 연결된 소셜 계정이 있으면 -> 토큰/메타 정보만 갱신하고 해당 user를 반환
            if ($socialAccount) {
                $socialAccount->forceFill([
                    'email' => $email,
                    'nickname' => $socialUser->getNickname(),
                    'raw_profile' => method_exists($socialUser, 'getRaw') ? $socialUser->getRaw() : null,
                    'access_token' => $socialUser->token ?? $socialAccount->access_token,
                    'refresh_token' => $socialUser->refreshToken ?? $socialAccount->refresh_token,
                    'token_expires_at' => isset($socialUser->expiresIn)
                        ? now()->addSeconds($socialUser->expiresIn)
                        : $socialAccount->token_expires_at,
                ])->save();

                return $socialAccount->user;
            }

            // 2차 조회 : email 기준으로 기존 회원 찾기 -> 같은 사람이 일반 회원가입 or 다른 소셜 계정으로 가입했을 가능성 처리
            $user = User::where('email', $email)->first();


            if (! $user) { // 회원 없으면 새로 생성
                $user = User::create([
                    'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: $email,
                    'email' => $email,
                    'password' => Str::random(40),
                    'profile_image_path' => $socialUser->getAvatar(),
                    'email_verified_at' => now(),
                ]);
            }

            // 소셜 계정 저장
            $user->socialAccounts()->create([
                'provider' => $provider->value,
                'provider_user_id' => $socialUser->getId(),
                'email' => $email,
                'nickname' => $socialUser->getNickname(),
                'raw_profile' => method_exists($socialUser, 'getRaw') ? $socialUser->getRaw() : null,
                'access_token' => $socialUser->token ?? null,
                'refresh_token' => $socialUser->refreshToken ?? null,
                'token_expires_at' => isset($socialUser->expiresIn)
                    ? now()->addSeconds($socialUser->expiresIn)
                    : null,
            ]);

            return $user;
        });
    }
}
