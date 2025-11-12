<?php

namespace CmsOrbit\PasswordSecurity\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use CmsOrbit\PasswordSecurity\Traits\HasPasswordSecurity;

class CheckAccountActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // 설정 확인
        if (!config('password-security.enabled') ||
            !config('password-security.inactive_accounts.enabled')) {
            return $next($request);
        }

        $user = Auth::user();

        // 인증되지 않았거나 Trait이 없으면 통과
        if (!$user || !$this->hasPasswordSecurityTrait($user)) {
            return $next($request);
        }

        // 계정 활성화 상태 체크
        if (!$user->isAccountActive()) {
            // AJAX 요청인 경우
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('Your account has been deactivated. Please contact administrator.'),
                ], 403);
            }

            // 로그아웃 처리
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // 휴면 계정 화면 표시
            $viewName = config('password-security.views.account_inactive', 'password-security::account-inactive');
            
            $security = $user->passwordSecurity;
            
            return response()->view($viewName, [
                'user' => $user,
                'reason' => $security?->deactivation_reason ?? __('Inactivity for extended period'),
                'deactivatedAt' => $security?->deactivated_at,
            ], 403);
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
}

