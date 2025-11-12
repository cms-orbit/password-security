# Laravel Password Security Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cms-orbit/password-security.svg?style=flat-square)](https://packagist.org/packages/cms-orbit/password-security)
[![Total Downloads](https://img.shields.io/packagist/dt/cms-orbit/password-security.svg?style=flat-square)](https://packagist.org/packages/cms-orbit/password-security)

강력한 비밀번호 보안 정책을 제공하는 Laravel 패키지입니다. 기업 및 공공기관의 보안 요구사항을 충족합니다.

**✨ 특징: 기존 테이블 변경 불필요! Trait만 추가하면 끝!**

## 주요 기능

- ✅ **복잡도 검증**: 2가지 조합 10자 이상 또는 3가지 조합 8자 이상
- ✅ **추측 가능한 패턴 차단**: 연속 숫자/문자, 키보드 패턴, 반복 문자, 일반 단어 등
- ✅ **개인정보 포함 차단**: 이름, 이메일 등 개인정보 사용 금지
- ✅ **패스워드 히스토리**: 최근 사용한 N개 패스워드 재사용 금지 (해시 비교)
- ✅ **강제 변경**: 분기(90일) 1회 이상 패스워드 변경
- ✅ **휴면 계정 처리**: 90일 미사용 계정 자동 비활성화
- ✅ **다국어 지원**: 한국어, 영어
- ✅ **Non-invasive**: Morph 테이블 사용으로 기존 코드 영향 최소화
- ✅ **UUID 지원**: UUID 기본 키 모델 완벽 지원

## 보안 요구사항 충족

✅ **1. 패스워드 복잡도**
- 2가지 이상 조합 시 10자 이상
- 3가지 이상 조합 시 8자 이상
- 영문 대소문자, 숫자, 특수문자(32개)

✅ **2. 추측 가능한 패스워드 제한**
- ID, 이메일, 이름 포함 차단
- 연속/반복 문자 (`123456`, `aaa`, `123123`)
- 생일 (`19900101`), 전화번호 (`01012345678`)
- 일반 단어 (`password`, `admin`)

✅ **3. 최근 패스워드 재사용 금지**
- 최근 N개(기본 3개) 히스토리 저장
- 해시 비교로 정확한 재사용 방지

✅ **4. 분기 1회 이상 강제 변경**
- 90일 주기 자동 만료
- Middleware로 강제 변경 페이지 리다이렉트

✅ **5. 90일 미사용 계정 비활성화**
- 자동 배치 처리
- 관리자 제외 옵션

## 요구사항

- PHP ^8.2
- Laravel ^9.0|^10.0|^11.0

## 빠른 시작

### 1. 설치

```bash
composer require cms-orbit/password-security
```

### 2. 마이그레이션 실행

```bash
php artisan migrate
```

패키지는 2개의 Morph 테이블을 생성합니다:
- `password_securities` - 보안 메타데이터 (1:1)
- `password_histories` - 패스워드 히스토리 (1:Many)

### 3. 모델에 Trait 추가

```php
use CmsOrbit\PasswordSecurity\Traits\HasPasswordSecurity;

class User extends Authenticatable
{
    use HasPasswordSecurity;
}
```

**끝!** 이제 패스워드 보안 정책이 자동으로 적용됩니다! 🎉

## 작동 방식

1. **평문 패스워드 캡처**: `setAttribute()` 인터셉터가 평문을 임시 저장
2. **검증**: Observer가 저장 전에 모든 규칙 검증
3. **히스토리 저장**: 해시된 값을 히스토리에 저장
4. **재사용 검증**: 평문을 히스토리의 해시값들과 `Hash::check()`로 비교

## 상세 사용법

### Config 설정 (선택사항)

```bash
php artisan vendor:publish --tag=password-security-config
```

엄격한 보안 설정 예제:

```php
// config/password-security.php
'complexity' => [
    'enabled' => true,
    'require_uppercase' => true,  // 대문자 필수
    'require_lowercase' => true,  // 소문자 필수
    'require_numbers' => true,    // 숫자 필수
    'require_special' => true,    // 특수문자 필수
],
```

`config/password-security-strict.php` 파일에 엄격한 설정 예제가 있습니다.

### 필드 커스터마이징

```php
class Promoter extends Authenticatable
{
    use HasPasswordSecurity;
    
    // 선택사항: 필드명 지정
    protected $passwordSecurityField = 'password';
    protected $passwordSecurityPersonalFields = [
        'name' => 'name',
        'email' => 'email',
        'channel' => 'channel',  // 추가 필드
    ];
}
```

### Middleware 등록

`app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    'password.expiration' => \CmsOrbit\PasswordSecurity\Middleware\CheckPasswordExpiration::class,
    'account.active' => \CmsOrbit\PasswordSecurity\Middleware\CheckAccountActive::class,
];
```

라우트에 적용:

```php
Route::middleware(['auth', 'password.expiration', 'account.active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

### 스케줄러 등록

`app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // 매일 오전 2시에 휴면 계정 체크
    $schedule->command('password-security:deactivate-inactive')->dailyAt('02:00');
    
    // 매일 오전 9시에 패스워드 만료 알림
    $schedule->command('password-security:notify-expiration')->dailyAt('09:00');
}
```

### 패스워드 변경 라우트

```php
Route::get('/password/change', [PasswordController::class, 'showChangeForm'])
    ->middleware('auth')->name('password.change');
    
Route::post('/password/change', [PasswordController::class, 'update'])
    ->middleware('auth')->name('password.update');
```

## 테스트 결과

```
✅ '123123' - 차단됨 (숫자만 사용)
✅ '123123123' - 차단됨 (반복 패턴)
✅ 'password123' - 차단됨 (일반 단어)
✅ 'qwerty1234' - 차단됨 (키보드 패턴)
✅ 패스워드 히스토리 저장 및 재사용 방지
✅ 만료일 자동 계산 (90일 후)
```

자세한 테스트 결과는 [TESTED.md](TESTED.md)를 참조하세요.

## Artisan Commands

```bash
# 휴면 계정 처리
php artisan password-security:deactivate-inactive
php artisan password-security:deactivate-inactive --dry-run --notify

# 패스워드 만료 알림
php artisan password-security:notify-expiration
php artisan password-security:notify-expiration --dry-run --force-change
```

## API

### Trait 메서드

```php
// 만료 확인
$user->isPasswordExpired(); // bool
$user->getDaysUntilPasswordExpiration(); // int|null

// 계정 상태
$user->isAccountActive(); // bool
$user->deactivateAccount('reason');
$user->activateAccount();

// 로그인 추적
$user->updateLastLogin();
$user->getDaysUntilDeactivation(); // int|null

// 히스토리
$user->isPasswordInHistory('MyPassword123!'); // bool

// 관계
$user->passwordSecurity; // PasswordSecurity 모델 (1:1)
$user->passwordHistories; // Collection (1:Many)
```

### Validator 사용

```php
use CmsOrbit\PasswordSecurity\Validators\PasswordValidator;

$validator = new PasswordValidator();

// 전체 검증
$validator->validate('MyPassword123!', $user);

// 개별 검증
$validator->validateComplexity('MyPassword123!');
$validator->validateCommonPattern('MyPassword123!');
$validator->validatePersonalInfo('MyPassword123!', $user);
$validator->validateHistory('MyPassword123!', $user);

// 에러 확인
$errors = $validator->getErrors();
```

## 데이터베이스 구조

패키지는 기존 테이블을 수정하지 않고, 2개의 Morph 테이블만 생성합니다:

### password_securities (1:1 Morph)
- `securable_type`, `securable_id` (UUID 지원)
- `password_changed_at`, `password_expires_at`
- `password_must_change`
- `is_active`, `deactivated_at`
- `last_login_at`

### password_histories (1:Many Morph)
- `securable_type`, `securable_id` (UUID 지원)
- `password_hash` (해시된 패스워드)
- `changed_at`, `changed_by`
- `ip_address`, `user_agent`

**기존 users, promoters 등 테이블 변경 불필요!** 👍

## 고급 사용법

자세한 내용은 다음 문서를 참조하세요:

- [README.md](README.md) - 이 파일
- [TESTED.md](TESTED.md) - 테스트 결과
- [config/password-security.php](config/password-security.php) - 전체 설정 옵션
- [config/password-security-strict.php](config/password-security-strict.php) - 엄격한 보안 설정 예제

## 변경 이력

### v1.0.0 - 2025-01-01

- 초기 릴리스
- 5가지 보안 요구사항 모두 충족
- UUID 지원
- 평문 패스워드 인터셉터로 mutator와 호환
- 완벽한 히스토리 재사용 방지

## 기여

기여는 언제나 환영합니다! Pull Request를 보내주세요.

## 라이선스

MIT 라이선스. 자세한 내용은 [LICENSE](LICENSE)을 참조하세요.
