# Nova 통합 가이드

## 미들웨어 적용

Nova에서 패스워드 보안 미들웨어를 적용하려면 `app/Providers/NovaServiceProvider.php`를 수정하세요.

### 방법 1: Nova 전체에 적용

```php
use Laravel\Nova\Nova;

class NovaServiceProvider extends NovaServiceProviderBase
{
    public function boot()
    {
        parent::boot();

        Nova::serving(function () {
            // Nova 전체에 미들웨어 적용
            Nova::router()->middleware([
                'password.expiration',
                'account.active',
            ]);
        });
    }
}
```

### 방법 2: 특정 라우트 그룹에만 적용

```php
// routes/web.php 또는 별도 라우트 파일에서

Route::middleware(['nova', 'password.expiration', 'account.active'])->group(function () {
    // Nova 대시보드 등
});
```

### 방법 3: Kernel.php에서 전역 적용

```php
// app/Http/Kernel.php

protected $middlewareGroups = [
    'web' => [
        // ... 기존 미들웨어 ...
        \CmsOrbit\PasswordSecurity\Middleware\CheckPasswordExpiration::class,
        \CmsOrbit\PasswordSecurity\Middleware\CheckAccountActive::class,
    ],
];
```

## 비밀번호 변경 화면 커스터마이징

### 1. View 발행

```bash
php artisan vendor:publish --tag=password-security-views
```

### 2. View 수정

발행된 뷰 파일을 수정하세요:
- `resources/views/vendor/password-security/password-change.blade.php`
- `resources/views/vendor/password-security/account-inactive.blade.php`

### 3. 커스텀 View 사용

`config/password-security.php`:

```php
'views' => [
    'password_change' => 'my-custom-views.password-change',
    'account_inactive' => 'my-custom-views.account-inactive',
],
```

## 지원 연락처 설정

`.env`:

```env
PASSWORD_SECURITY_SUPPORT_EMAIL=admin@example.com
PASSWORD_SECURITY_SUPPORT_PHONE=+82-2-1234-5678
```

또는 `config/password-security.php`:

```php
'support' => [
    'email' => 'admin@example.com',
    'phone' => '+82-2-1234-5678',
],
```

## 동작 흐름

### 1. 비밀번호 만료 시

```
사용자 로그인
    ↓
CheckPasswordExpiration 미들웨어 실행
    ↓
패스워드 만료 감지
    ↓
/password-security/change 리다이렉트 (갇힘)
    ↓
비밀번호 변경 후 원래 페이지로 돌아감
```

### 2. 휴면 계정 시

```
사용자 로그인
    ↓
CheckAccountActive 미들웨어 실행
    ↓
계정 비활성화 감지
    ↓
로그아웃 + 휴면 계정 화면 표시 (403)
    ↓
관리자에게 문의 안내
```

## 제외 라우트 설정

특정 라우트를 미들웨어 검사에서 제외하려면:

```php
'expiration' => [
    'excluded_routes' => [
        'password-security.change',
        'password-security.update',
        'logout',
        'nova.login',  // Nova 로그인 제외
    ],
    'excluded_urls' => [
        'password-security/*',
        'api/*',
        'nova/login',  // Nova 로그인 URL 제외
    ],
],
```

## 트러블슈팅

### Q: Nova에서 무한 리다이렉트 발생

A: `excluded_routes`에 `password-security.change`, `password-security.update`가 포함되어 있는지 확인하세요.

### Q: 휴면 계정 화면이 표시되지 않음

A: `CheckAccountActive` 미들웨어가 적용되었는지, `is_active` 필드가 `false`인지 확인하세요.

### Q: 비밀번호 변경 후에도 계속 만료 화면이 뜸

A: Observer가 제대로 등록되었는지, `password_must_change` 필드가 `false`로 업데이트되는지 확인하세요.

