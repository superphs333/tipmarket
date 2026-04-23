<?php
/**
 * 소셜 로그인 제공자를 관리하는 enum
 * 
 * - 라우트에서 넘어온 provider 문자열을 안전하게 enum으로 변환
 * : 허용된 provider만 통과시키고, 잘못된 값은 404 처리
 */

namespace App\Enums;


enum SocialProvider: string
{
    case Google = 'google'; 
    case Kakao = 'kakao';

    /**
     * 라우트 파라미터로 받은 provider 문자열을 enum으로 변환
     * 
     * @param string $provider 라우트에서 전달된 소셜 로그인 제공자 값
     * @return self 유효한 SocialProvider enum 객체
     */
    public static function fromRoute(string $provider): self
    {
        // 전달받은 문자열이 enum값과 일치하면 해당 enum case를 반환, 일치하지 않으면 null 반환
        $socialProvider = self::tryFrom($provider);

        // 유효하지 않은 provider라면 404에러를 발생
        abort_if($socialProvider === null, 404);

        return $socialProvider;
    }
}
