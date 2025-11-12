# Laravel Password Security Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cms-orbit/password-security.svg?style=flat-square)](https://packagist.org/packages/cms-orbit/password-security)
[![Total Downloads](https://img.shields.io/packagist/dt/cms-orbit/password-security.svg?style=flat-square)](https://packagist.org/packages/cms-orbit/password-security)

강력한 비밀번호 보안 정책을 제공하는 Laravel 패키지입니다. 기업 및 공공기관의 보안 요구사항을 충족합니다.

**✨ 특징: 기존 테이블 변경 불필요! Trait만 추가하면 끝!**

## 주요 기능

- ✅ **복잡도 검증**: 2가지 조합 10자 이상 또는 3가지 조합 8자 이상
- ✅ **추측 가능한 패턴 차단**: 연속 숫자/문자, 키보드 패턴, 일반 단어 등
- ✅ **개인정보 포함 차단**: 이름, 이메일 등 개인정보 사용 금지
- ✅ **패스워드 히스토리**: 최근 사용한 N개 패스워드 재사용 금지
- ✅ **강제 변경**: 분기(90일) 1회 이상 패스워드 변경
- ✅ **휴면 계정 처리**: 90일 미사용 계정 자동 비활성화
- ✅ **다국어 지원**: 한국어, 영어
- ✅ **Non-invasive**: Morph 테이블 사용으로 기존 코드 영향 최소화

## 요구사항

- PHP ^8.2
- Laravel ^9.0|^10.0|^11.0
- rappasoft/laravel-authentication-log ^2.0|^3.0

## 빠른 시작

### 1. 설치

```bash
composer require cms-orbit/password-security
```

### 2. 마이그레이션 실행

```bash
php artisan vendor:publish --tag=password-security-migrations
php artisan migrate
```

### 3. 모델에 Trait 추가

```php
use CmsOrbit\PasswordSecurity\Traits\HasPasswordSecurity;

class User extends Authenticatable
{
    use HasPasswordSecurity;
}
```

**끝!** 이제 패스워드 보안 정책이 자동으로 적용됩니다! 🎉

## 상세 사용법

### 1. Config 설정 (선택사항)

```bash
php artisan vendor:publish --tag=password-security-config
```

`config/password-security.php`에서 배치 작업 대상 모델만 등록:

```php
'models' => [
    \App\Models\User::class,
    \AppTenants\Models\Promoter::class,
],
```

### 2. 필드 커스터마이징 (선택사항)

패스워드 필드명이나 개인정보 필드를 지정하려면:

```php
class Promoter extends Authenticatable
{
    use HasPasswordSecurity;
    
    protected $passwordSecurityField = 'password';
    protected $passwordSecurityPersonalFields = [
        'name' => 'name',
        'email' => 'email',
        'channel' => 'channel',
        'store' => 'store',
    ];
}
```

### 3. Middleware 등록

`app/Http/Kernel.php`에 미들웨어를 등록합니다:

```php
protected $routeMiddleware = [
    // ...
    'password.expiration' => \CmsOrbit\PasswordSecurity\Middleware\CheckPasswordExpiration::class,
    'account.active' => \CmsOrbit\PasswordSecurity\Middleware\CheckAccountActive::class,
];
```

보호할 라우트에 미들웨어를 적용합니다:

```php
Route::middleware(['auth', 'password.expiration', 'account.active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    // ...
});
```

### 4. 스케줄러 등록

`app/Console/Kernel.php`에 스케줄러를 등록합니다:

```php
protected function schedule(Schedule $schedule)
{
    // 매일 오전 2시에 휴면 계정 체크
    $schedule->command('password-security:deactivate-inactive')
             ->dailyAt('02:00');
    
    // 매일 오전 9시에 패스워드 만료 알림
    $schedule->command('password-security:notify-expiration')
             ->dailyAt('09:00');
}
```

### 5. 비밀번호 변경 라우트 생성

패스워드 만료 시 리다이렉트될 라우트를 생성합니다:

```php
Route::get('/password/change', [PasswordController::class, 'showChangeForm'])
    ->name('password.change');
    
Route::post('/password/change', [PasswordController::class, 'update'])
    ->name('password.update');
```

## 설정 옵션

### 복잡도 검증

```php
'complexity' => [
    'enabled' => true,
    'min_length_2_types' => 10,  // 2가지 조합 시 최소 길이
    'min_length_3_types' => 8,   // 3가지 조합 시 최소 길이
    'min_length_4_types' => 8,   // 4가지 조합 시 최소 길이
],
```

### 패스워드 히스토리

```php
'history' => [
    'enabled' => true,
    'check_last_n_passwords' => 3,          // 최근 N개 패스워드 체크
    'keep_history_for_days' => 365,         // 히스토리 보관 기간
],
```

### 패스워드 만료

```php
'expiration' => [
    'enabled' => true,
    'expires_in_days' => 90,                 // 패스워드 만료 기간 (분기)
    'notify_before_days' => [7, 3, 1],       // 만료 전 알림 (일)
    'force_change_route' => 'password.change',
],
```

### 휴면 계정

```php
'inactive_accounts' => [
    'enabled' => true,
    'inactive_days' => 90,                   // 휴면 기준 (일)
    'notify_before_days' => [14, 7, 3],      // 비활성화 전 알림 (일)
    'auto_deactivate' => true,
],
```

## 테스트

```bash
composer test
```

## 변경 이력

자세한 변경 이력은 [CHANGELOG.md](CHANGELOG.md)를 참조하세요.

## 기여

기여는 언제나 환영합니다! Pull Request를 보내주세요.

## 라이선스

MIT 라이선스. 자세한 내용은 [License File](LICENSE)을 참조하세요.

