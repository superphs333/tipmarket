<?php

namespace App\Actions\Auth;

use Illuminate\Http\Request;

/**
 * 로그인 성공 후 사용자를 어디로 이동시킬 지 결정
 * 
 * [역할]
 * - Laravel이 기본적으로 저장해둔 intented URL이 있으면 우선 사용
 * - 소셜 로그인 진입 전 페이지(origin)이 있으면 후보로 사용
 * - 단 외부 사이트 이동 / 로그인 페이지 진입 / auth 관련 URL은 차단
 * - 유효한 이동 대상이 없으면 dashboard로 이동
 */
class ResolvePostLoginRedirect
{
    
    public function handle(Request $request, ?string $origin = null): string
    {
        // 로그인 후 리다이렉트 할 최종 URL 결정
            // 1. 라라벨이 인증 페이지로 보내기 전에 저장해둔 intended URL
            // 2. 소셜 로그인 진입 직전 페이지 (origin)
        $candidates = [
            $request->session()->pull('url.intended'),
            $origin,
        ];

        // 후보를 순서대로 검사해서, 로그인 후 보내도 안전한 URL이 나오면 바로 반환
        foreach ($candidates as $candidate) {
            if ($this->isValidRedirectTarget($request, $candidate)) {
                return $candidate;
            }
        }

        return route('dashboard');
    }

    /**
     * 로그인 후 이동해도 안전한 URL인지 검사
     * 
     * [방지]
     * - 사용자가 조작한 URL로 외부 사이트에 이동하는 것 
     * - 로그인 성공 후 다시 로그인 페이지로 돌아가는 흐름
     * - 소셜 로그인 callback URL 같은 인증 처리 URL로 되돌아가는 것
     * 
     * @param Request $request 현재 HTTP 요청 객체
     * @param string | null $url 검사할 리다이렉트 URL
     * @return bool 이동 가능한 URL이면 true, 차단해야 하면 false
     */
    protected function isValidRedirectTarget(Request $request, ?string $url): bool
    {
        // url이 문자열이 아니거나 빈 문자열이면 유효x
        if (! is_string($url) || $url === '') {
            return false;
        }

        // 외부 도메인으로 이동하는 것 차단
        $host = parse_url($url, PHP_URL_HOST);

        // url 에서 path만 추출 
        if ($host !== null && $host !== $request->getHost()) {
            return false;
        }

        $path = '/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/');

        // 로그인 후 이동하면 부자연스러운 기본 페이지들 차단
        if (in_array($path, ['/', '/login', '/register'], true)) {
            return false;
        }
        // 인증 관련 url로 다시 이동하는 것 차단
        if (str_starts_with($path, '/auth/')) {
            return false;
        }

        return true;
    }
}
