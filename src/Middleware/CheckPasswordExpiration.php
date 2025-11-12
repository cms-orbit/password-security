<?php

namespace CmsOrbit\PasswordSecurity\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use CmsOrbit\PasswordSecurity\Traits\HasPasswordSecurity;

class CheckPasswordExpiration
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // 설정 확인
        if (!config('password-security.enabled') ||
            !config('password-security.expiration.enabled')) {
            return $next($request);
        }

        $user = Auth::user();

        // 인증되지 않았거나 Trait이 없으면 통과
        if (!$user || !$this->hasPasswordSecurityTrait($user)) {
            return $next($request);
        }

        // 제외 라우트 체크
        if ($this->isExcludedRoute($request)) {
            return $next($request);
        }

        // 패스워드 만료 체크
        if ($user->isPasswordExpired()) {
            // AJAX 요청인 경우
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('Your password has expired. Please change your password.'),
                    'redirect' => route('password-security.change'),
                ], 403);
            }

            // 일반 요청인 경우 - 라우트명 고정
            return redirect()
                ->route('password-security.change')
                ->with('warning', __('Your password has expired. Please change your password.'));
        }

        // 만료 임박 알림 (선택적)
        $daysUntilExpiration = $user->getDaysUntilPasswordExpiration();
        $notifyDays = config('password-security.expiration.notify_before_days', []);

        if ($daysUntilExpiration !== null && in_array($daysUntilExpiration, $notifyDays)) {
            session()->flash('password_expiring_soon', [
                'days' => $daysUntilExpiration,
                'message' => __('password-security::messages.password_expiring_soon', [
                    'days' => $daysUntilExpiration
                ]),
            ]);
        }

        return $next($request);
    }

    /**
     * 모델이 HasPasswordSecurity Trait을 사용하는지 확인
     */
    protected function hasPasswordSecurityTrait($user): bool
    {
        return in_array(
            HasPasswordSecurity::class,
            class_uses_recursive($user)
        );
    }

    /**
     * 제외된 라우트인지 확인
     */
    protected function isExcludedRoute(Request $request): bool
    {
        $currentRoute = Route::currentRouteName();
        $currentUrl = $request->path();

        // 라우트명으로 체크
        $excludedRoutes = config('password-security.expiration.excluded_routes', []);
        if ($currentRoute && in_array($currentRoute, $excludedRoutes)) {
            return true;
        }

        // URL 패턴으로 체크
        $excludedUrls = config('password-security.expiration.excluded_urls', []);
        foreach ($excludedUrls as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

}

