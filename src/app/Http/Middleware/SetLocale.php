<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * 저장된 언어를 요청에 적용 
 */
class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = array_keys((array) config('app.supported_locales', []));
        // 로그인 사용자의 저장 언어를 우선 적용하고, 없으면 앱 기본 언어를 사용한다.
        $locale = $request->user()?->locale ?? config('app.locale', 'ko');

        if (! in_array($locale, $supportedLocales, true)) {
            $locale = config('app.locale', 'ko');
        }

        // 이후 렌더링되는 __('...') 호출은 여기서 설정한 locale의 JSON 번역을 사용한다.
        App::setLocale($locale);

        return $next($request);
    }
}
