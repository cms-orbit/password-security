<?php

namespace CmsOrbit\PasswordSecurity;

use CmsOrbit\PasswordSecurity\Commands\DeactivateInactiveAccounts;
use CmsOrbit\PasswordSecurity\Commands\NotifyPasswordExpiration;
use CmsOrbit\PasswordSecurity\Middleware\CheckAccountActive;
use CmsOrbit\PasswordSecurity\Middleware\CheckPasswordExpiration;
use CmsOrbit\PasswordSecurity\Observers\PasswordSecurityObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;

class PasswordSecurityServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Config 파일 발행
        $this->publishes([
            __DIR__.'/../config/password-security.php' => config_path('password-security.php'),
        ], 'password-security-config');

        // Migration 파일 발행
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'password-security-migrations');

        // 언어 파일 발행
        $this->publishes([
            __DIR__.'/../resources/lang' => $this->app->langPath('vendor/password-security'),
        ], 'password-security-lang');

        // 언어 파일 로드
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'password-security');

        // Commands 등록
        if ($this->app->runningInConsole()) {
            $this->commands([
                DeactivateInactiveAccounts::class,
                NotifyPasswordExpiration::class,
            ]);
        }

        // Middleware 등록
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('password.expiration', CheckPasswordExpiration::class);
        $router->aliasMiddleware('account.active', CheckAccountActive::class);

        // Observer 등록
        $this->registerObservers();

        // 로그인 이벤트 리스닝 (last_login_at 업데이트)
        $this->registerLoginListener();

        // 스케줄러 등록
        $this->registerSchedule();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Config 파일 병합
        $this->mergeConfigFrom(
            __DIR__.'/../config/password-security.php',
            'password-security'
        );

        // 마이그레이션 파일 등록
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // 언어 파일 등록
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'password-security');

        // Config 파일 등록
        $this->publishes([
            __DIR__.'/../config/password-security.php' => config_path('password-security.php'),
        ], 'password-security-config');
    }

    /**
     * 모델 Observer 등록
     */
    protected function registerObservers(): void
    {
        if (!config('password-security.enabled')) {
            return;
        }

        $models = config('password-security.models', []);

        foreach ($models as $model) {
            if (class_exists($model)) {
                $model::observe(PasswordSecurityObserver::class);
            }
        }
    }

    /**
     * 로그인 이벤트 리스너 등록 (last_login_at 업데이트)
     */
    protected function registerLoginListener(): void
    {
        if (!config('password-security.enabled')) {
            return;
        }

        Event::listen(Login::class, function (Login $event) {
            $user = $event->user;

            // HasPasswordSecurity 트레이트를 사용하는 모델만 처리
            if (!method_exists($user, 'passwordSecurity')) {
                return;
            }

            $security = $user->passwordSecurity;

            if (!$security && method_exists($user, 'createPasswordSecurity')) {
                $security = $user->createPasswordSecurity();
            }

            if ($security) {
                $security->last_login_at = now();
                $security->save();
            }
        });
    }

    /**
     * 스케줄러 등록
     */
    protected function registerSchedule(): void
    {
        if (!config('password-security.enabled')) {
            return;
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // 매일 오전 2시에 휴면 계정 비활성화
            if (config('password-security.inactive_accounts.enabled')) {
                $schedule->command('password-security:deactivate-inactive')
                    ->dailyAt('02:00')
                    ->withoutOverlapping()
                    ->onOneServer();
            }

            // 매일 오전 9시에 패스워드 만료 알림
            if (config('password-security.expiration.enabled')) {
                $schedule->command('password-security:notify-expiration')
                    ->dailyAt('09:00')
                    ->withoutOverlapping()
                    ->onOneServer();
            }
        });
    }
}

