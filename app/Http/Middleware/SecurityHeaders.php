<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Clickjacking 방지
        $response->headers->set('X-Frame-Options', 'DENY');

        // MIME 타입 스니핑 방지
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // XSS 필터 활성화
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer 정책
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Content Security Policy (API 서버에 적합한 설정)
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'none'; frame-ancestors 'none'"
        );

        // 캐시 제어 (API 응답은 기본적으로 캐시하지 않음)
        if (!$response->headers->has('Cache-Control')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        // Strict Transport Security (HTTPS 강제)
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
