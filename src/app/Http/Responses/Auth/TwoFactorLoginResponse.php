<?php

namespace App\Http\Responses\Auth;

use App\Actions\Auth\ResolvePostLoginRedirect;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

// 2차 인증 완료 후 응답을 만들 때 사용하는 커스텀 응답 클래스
class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    
    public function __construct(
        protected ResolvePostLoginRedirect $resolvePostLoginRedirect,
    ) {}

    public function toResponse($request)
    {
        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->to($this->resolvePostLoginRedirect->handle($request));
    }
}
