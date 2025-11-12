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
            // 로그아웃 처리
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // AJAX 요청인 경우
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('password-security::messages.account_deactivated'),
                ], 403);
            }

            // 일반 요청인 경우
            return redirect()->route('login')
                ->with('error', __('password-security::messages.account_deactivated'));
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

