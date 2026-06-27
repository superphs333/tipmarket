<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 사용자가 특정 역할을 가지고 있는지 검사.  
 */
class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     * @param string ...$roles : 라우트에서 전달한 역할 목록
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $roles = array_values(array_filter($roles, fn (string $role): bool => $role !== ''));

        // 권한 조건 검사 (세 조건에 모두 true여야 통과)
        abort_unless(
            $user !== null && $roles !== [] && $user->hasAnyRole($roles),
            Response::HTTP_FORBIDDEN
        );

        return $next($request);
    }
}
