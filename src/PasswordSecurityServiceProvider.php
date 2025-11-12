<?php

namespace CmsOrbit\PasswordSecurity;

use CmsOrbit\PasswordSecurity\Commands\DeactivateInactiveAccounts;
use CmsOrbit\PasswordSecurity\Commands\NotifyPasswordExpiration;
use CmsOrbit\PasswordSecurity\Middleware\CheckAccountActive;
use CmsOrbit\PasswordSecurity\Middleware\CheckPasswordExpiration;
use CmsOrbit\PasswordSecurity\Observers\PasswordSecurityObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

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
}

