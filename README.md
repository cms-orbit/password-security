# Laravel Password Security Package

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-9.x%20|%2010.x%20|%2011.x-red)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://php.net)

Laravel 애플리케이션을 위한 포괄적인 패스워드 보안 관리 패키지입니다. 강력한 패스워드 정책, 만료 관리, 휴면 계정 처리 등의 기능을 제공합니다.

## 주요 기능

### 🔐 패스워드 복잡도 검증
- 영문 대문자, 소문자, 숫자, 특수문자 조합 규칙
- 2가지 조합 시 10자 이상, 3가지 이상 조합 시 8자 이상
- 커스터마이징 가능한 복잡도 규칙

### 🚫 일반 패턴 차단
- 연속된 숫자/문자 차단 (123456, abcdef 등)
- 키보드 패턴 차단 (qwerty, asdfgh 등)
- 반복 문자 차단 (aaaaaa, 111111 등)
- 생일/전화번호 패턴 차단
- 일반적인 단어 차단 (password, admin 등)

### 👤 개인정보 보호
- 이름, 이메일, 사용자명 포함 차단
- 대소문자 구분 없는 검사
- 추가 개인정보 필드 커스터마이징

### 📜 패스워드 히스토리 관리
- 최근 N개 패스워드 재사용 방지
- 자동 히스토리 정리
- IP 주소 및 User Agent 기록

### ⏰ 패스워드 만료 관리
- 분기(90일) 단위 패스워드 변경 강제
- 만료 전 알림 (7일, 3일, 1일 전)
- 만료 시 자동 리다이렉션
- 유예 기간 설정 가능

### 💤 휴면 계정 관리
- 일정 기간 미사용 계정 자동 비활성화
- 비활성화 전 알림 발송
- 역할 및 이메일 기반 제외 규칙
- 자동 삭제 옵션

### 🔔 알림 시스템
- 패스워드 만료 알림
- 휴면 계정 알림
- 다중 채널 지원 (메일, 데이터베이스 등)

### 🛠️ 개발자 친화적
- Laravel Nova 통합 지원
- 쉬운 모델 통합 (Trait 기반)
- 커스터마이징 가능한 뷰
- 다국어 지원 (한국어, 영어)

## 요구사항

- PHP 8.2 이상
- Laravel 9.x, 10.x, 11.x
- MySQL 5.7+ 또는 PostgreSQL 9.6+

## 설치

### 1. Composer로 설치

```bash
composer require cms-orbit/password-security
```

### 2. 설정 파일 발행

```bash
php artisan vendor:publish --tag=password-security-config
php artisan vendor:publish --tag=password-security-migrations
php artisan vendor:publish --tag=password-security-views
php artisan vendor:publish --tag=password-security-lang
```

### 3. 마이그레이션 실행

```bash
php artisan migrate
```

## 기본 사용법

### 1. 모델에 Trait 추가

패스워드 보안 기능을 사용할 모델에 `HasPasswordSecurity` Trait을 추가합니다.

```php
<?php

namespace App\Models;

use CmsOrbit\PasswordSecurity\Traits\HasPasswordSecurity;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasPasswordSecurity;
    
    // 패스워드 필드명 (기본값: 'password')
    protected $passwordSecurityField = 'password';
    
    // 개인정보 필드 (패스워드에 포함 방지)
    protected $passwordSecurityPersonalFields = [
        'name',
        'email',
        'username',
    ];
}
```

### 2. 설정 파일에 모델 등록

`config/password-security.php` 파일에서 모델을 등록합니다.

```php
'models' => [
    \App\Models\User::class,
    // 다른 모델 추가...
],
```

### 3. 미들웨어 적용

#### 전역 적용 (app/Http/Kernel.php)

```php
protected $middlewareGroups = [
    'web' => [
        // ...
        \CmsOrbit\PasswordSecurity\Middleware\CheckPasswordExpiration::class,
        \CmsOrbit\PasswordSecurity\Middleware\CheckAccountActive::class,
    ],
];
```

#### 라우트 그룹에 적용

```php
Route::middleware(['auth', 'password.expiration', 'account.active'])->group(function () {
    // 보호된 라우트들...
});
```

### 4. Validation Rule 사용

#### 일반 폼 검증

```php
use CmsOrbit\PasswordSecurity\Rules\PasswordSecurityRule;

$request->validate([
    'password' => ['required', new PasswordSecurityRule($user)],
]);
```

#### Laravel Nova 통합

```php
use CmsOrbit\PasswordSecurity\Rules\PasswordSecurityRule;
use Laravel\Nova\Fields\Password;

Password::make('Password', 'password')
    ->rules('required', new PasswordSecurityRule($this->resource))
    ->creationRules('required')
    ->updateRules('nullable');
```

## 고급 사용법

### 패스워드 검증 커스터마이징

```php
use CmsOrbit\PasswordSecurity\Validators\PasswordValidator;

$validator = new PasswordValidator();

try {
    $validator->validate($password, $user, true);
    // 검증 성공
} catch (\CmsOrbit\PasswordSecurity\Exceptions\WeakPasswordException $e) {
    // 검증 실패
    $errors = $e->getErrors();
    $message = $e->getMessage();
}
```

### 개별 규칙 검증

```php
$validator = new PasswordValidator();

// 복잡도만 검증
if (!$validator->validateComplexity($password)) {
    // 복잡도 검증 실패
}

// 일반 패턴만 검증
if (!$validator->validateCommonPattern($password)) {
    // 일반 패턴 검증 실패
}

// 개인정보만 검증
if (!$validator->validatePersonalInfo($password, $user)) {
    // 개인정보 검증 실패
}

// 히스토리만 검증
if (!$validator->validateHistory($password, $user)) {
    // 히스토리 검증 실패
}
```

### 모델 메서드 사용

```php
// 패스워드 만료 확인
if ($user->isPasswordExpired()) {
    // 만료됨
}

// 만료까지 남은 일수
$daysRemaining = $user->getDaysUntilPasswordExpiration();

// 계정 활성화 상태 확인
if ($user->isAccountActive()) {
    // 활성화됨
}

// 계정 비활성화
$user->deactivateAccount('inactivity');

// 계정 활성화
$user->activateAccount();

// 패스워드 히스토리 확인
if ($user->isPasswordInHistory($newPassword)) {
    // 최근 사용한 패스워드
}

// 마지막 로그인 업데이트
$user->updateLastLogin();
```

## Artisan 명령어

### 패스워드 만료 알림 발송

```bash
# 일반 실행
php artisan password-security:notify-expiration

# Dry-run (실제 발송 없이 확인)
php artisan password-security:notify-expiration --dry-run

# 만료된 계정에 강제 변경 플래그 설정
php artisan password-security:notify-expiration --force-change
```

### 휴면 계정 비활성화

```bash
# 일반 실행
php artisan password-security:deactivate-inactive

# Dry-run (실제 비활성화 없이 확인)
php artisan password-security:deactivate-inactive --dry-run

# 비활성화 전 알림 발송
php artisan password-security:deactivate-inactive --notify
```

## 스케줄러 설정

패키지는 자동으로 스케줄러를 등록합니다. `app/Console/Kernel.php`에 추가 설정이 필요하지 않습니다.

- **매일 오전 2시**: 휴면 계정 비활성화 (`password-security:deactivate-inactive`)
- **매일 오전 9시**: 패스워드 만료 알림 (`password-security:notify-expiration`)

스케줄러를 실행하려면 cron에 다음을 추가하세요:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## 설정 옵션

### 패스워드 복잡도 설정

```php
'complexity' => [
    'enabled' => true,
    'min_length_2_types' => 10,         // 2가지 조합 시 최소 길이
    'min_length_3_types' => 8,          // 3가지 조합 시 최소 길이
    'min_length_4_types' => 8,          // 4가지 조합 시 최소 길이
    'require_uppercase' => true,        // 대문자 필수
    'require_lowercase' => true,        // 소문자 필수
    'require_numbers' => true,          // 숫자 필수
    'require_special' => true,          // 특수문자 필수
    'special_characters' => '!@#$%^&*()_+-=[]{}|;:,.<>?~`',
],
```

### 일반 패턴 차단 설정

```php
'common_patterns' => [
    'enabled' => true,
    'block_sequential_numbers' => true,      // 연속 숫자 차단
    'block_sequential_letters' => true,      // 연속 문자 차단
    'block_repeated_characters' => true,     // 반복 문자 차단
    'repeated_char_threshold' => 3,          // 반복 허용 횟수
    'block_keyboard_patterns' => true,       // 키보드 패턴 차단
    'block_common_words' => true,            // 일반 단어 차단
    'block_birthday_patterns' => true,       // 생일 패턴 차단
    'block_phone_patterns' => true,          // 전화번호 패턴 차단
    'sequential_threshold' => 3,             // 연속 허용 개수
    'common_words_list' => [...],            // 차단할 단어 목록
    'keyboard_patterns' => [...],            // 키보드 패턴 목록
],
```

### 개인정보 차단 설정

```php
'personal_info' => [
    'enabled' => true,
    'block_name' => true,                    // 이름 포함 차단
    'block_email' => true,                   // 이메일 포함 차단
    'block_username' => true,                // 사용자명 포함 차단
    'additional_fields' => [],               // 추가 차단 필드
    'min_substring_length' => 3,             // 최소 부분 문자열 길이
    'case_insensitive' => true,              // 대소문자 구분 없음
],
```

### 패스워드 히스토리 설정

```php
'history' => [
    'enabled' => true,
    'check_last_n_passwords' => 3,          // 최근 N개 체크
    'keep_history_for_days' => 365,         // 보관 기간
    'auto_cleanup' => true,                 // 자동 정리
],
```

### 패스워드 만료 설정

```php
'expiration' => [
    'enabled' => true,
    'expires_in_days' => 90,                 // 만료 기간 (분기)
    'notify_before_days' => [7, 3, 1],       // 만료 전 알림
    'grace_period_days' => 0,                // 유예 기간
    'force_change_on_first_login' => false,  // 첫 로그인 강제 변경
    'redirect_after_change' => '/',          // 변경 후 리다이렉트
    'excluded_routes' => [...],              // 제외 라우트
    'excluded_urls' => [...],                // 제외 URL 패턴
],
```

### 휴면 계정 설정

```php
'inactive_accounts' => [
    'enabled' => true,
    'inactive_days' => 90,                   // 휴면 기준 (일)
    'notify_before_days' => [14, 7, 3],      // 비활성화 전 알림
    'auto_deactivate' => true,               // 자동 비활성화
    'delete_after_days' => null,             // 삭제 기간 (null=삭제안함)
    'exclusions' => [
        'roles' => [],                       // 제외할 역할
        'emails' => [],                      // 제외할 이메일
        'has_active_sessions' => true,       // 활성 세션 제외
    ],
    'active_field' => 'is_active',
    'deactivated_at_field' => 'deactivated_at',
],
```

### 알림 설정

```php
'notifications' => [
    'enabled' => true,
    'channels' => ['mail'],                  // 알림 채널
    'expiration' => [
        'enabled' => true,
        'mail_subject' => 'Password Expiration Notice',
    ],
    'inactive_account' => [
        'enabled' => true,
        'mail_subject' => 'Account Inactivity Notice',
    ],
],
```

## 뷰 커스터마이징

### 패스워드 변경 화면

패키지가 제공하는 기본 뷰를 커스터마이징할 수 있습니다.

```bash
php artisan vendor:publish --tag=password-security-views
```

발행된 뷰는 `resources/views/vendor/password-security/` 디렉토리에 생성됩니다.

- `password-change.blade.php`: 패스워드 변경 화면
- `account-inactive.blade.php`: 휴면 계정 화면

설정 파일에서 뷰 경로를 변경할 수도 있습니다:

```php
'views' => [
    'password_change' => 'password-security::password-change',
    'account_inactive' => 'password-security::account-inactive',
],
```

## 다국어 지원

패키지는 한국어와 영어를 기본 지원합니다.

```bash
php artisan vendor:publish --tag=password-security-lang
```

발행된 언어 파일은 `resources/lang/vendor/password-security/` 디렉토리에 생성됩니다.

## 예외 처리

### 사용 가능한 예외

- `WeakPasswordException`: 약한 패스워드 (복잡도, 패턴 등)
- `PasswordReusedException`: 패스워드 재사용
- `PasswordExpiredException`: 패스워드 만료

### 예외 처리 방식 설정

```php
'exceptions' => [
    'throw_exceptions' => true,              // Exception 발생 여부
    'log_violations' => true,                // 위반 사항 로그 기록
    'log_channel' => null,                   // 로그 채널
],
```

`throw_exceptions`를 `false`로 설정하면 예외 대신 validation error를 반환합니다.

## 데이터베이스 테이블

패키지는 두 개의 테이블을 생성합니다:

### password_securities

사용자의 패스워드 보안 정보를 저장합니다.

- `securable_type`, `securable_id`: 다형성 관계
- `password_changed_at`: 마지막 패스워드 변경 일시
- `password_expires_at`: 패스워드 만료 일시
- `password_must_change`: 강제 변경 플래그
- `last_login_at`: 마지막 로그인 일시
- `is_active`: 계정 활성화 상태
- `deactivated_at`: 비활성화 일시
- `deactivation_reason`: 비활성화 사유

### password_histories

패스워드 변경 히스토리를 저장합니다.

- `securable_type`, `securable_id`: 다형성 관계
- `password_hash`: 패스워드 해시
- `changed_at`: 변경 일시
- `changed_by`: 변경한 사용자
- `ip_address`: IP 주소
- `user_agent`: User Agent

## 테스트

```bash
cd packages/password-security
composer install
./vendor/bin/phpunit
```

## 보안 취약점 보고

보안 취약점을 발견하시면 dev@cms-orbit.com으로 이메일을 보내주세요. 모든 보안 취약점은 신속하게 해결됩니다.

## 라이선스

MIT 라이선스입니다. 자세한 내용은 [LICENSE](LICENSE) 파일을 참조하세요.

## 크레딧

- CMS Orbit Development Team
- 모든 기여자들

## 변경 이력

### 1.0.0 (2025-01-12)

- 최초 안정 버전 릴리스
- 패스워드 복잡도 검증
- 일반 패턴 차단
- 개인정보 포함 차단
- 패스워드 히스토리 관리
- 패스워드 만료 관리
- 휴면 계정 관리
- 알림 시스템
- Laravel Nova 통합
- 다국어 지원 (한국어, 영어)

## 지원

- 이슈: [GitHub Issues](https://github.com/cms-orbit/password-security/issues)
- 이메일: dev@cms-orbit.com

## 기여

Pull Request는 언제나 환영합니다! 기여하기 전에 이슈를 먼저 열어 변경사항에 대해 논의해주세요.

## 로드맵

향후 추가 예정 기능:

- [ ] 2FA (Two-Factor Authentication) 통합
- [ ] 패스워드 강도 미터 컴포넌트
- [ ] 소셜 로그인 통합
- [ ] 추가 언어 지원
- [ ] 관리자 대시보드
- [ ] 감사 로그 강화

