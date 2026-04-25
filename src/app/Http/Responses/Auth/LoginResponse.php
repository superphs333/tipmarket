<?php

namespace App\Http\Responses\Auth;

use App\Actions\Auth\ResolvePostLoginRedirect;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

// 일반 로그인 성공 후 응답 커스텀
class LoginResponse implements LoginResponseContract
{
    public function __construct(
        protected ResolvePostLoginRedirect $resolvePostLoginRedirect,
    ) {}

    public function toResponse($request)
    {
        return $request->wantsJson()
            ? response()->json(['two_factor' => false]) // 로그인 페이지 이동이 아닌 JOSN응답을 기대하는 경우 json 반환
            : redirect()->to($this->resolvePostLoginRedirect->handle($request)); // 일반브라우저 요청 -> 공통 리다이렉트 정책에 따라 최종 이동 위치 결정
    }
}
