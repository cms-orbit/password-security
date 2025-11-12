# 사용 가이드

## 기본 사용법

### 1. 모델에 Trait 추가

가장 심플한 방법! 그냥 Trait만 추가하면 끝입니다.

```php
<?php

namespace App\Models;

use CmsOrbit\PasswordSecurity\Traits\HasPasswordSecurity;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasPasswordSecurity;
    
    // ... 기존 코드 그대로
}
```

이것만으로 끝! 기존 테이블에 컬럼 추가 필요 없음!

### 2. 필드명 커스터마이징 (선택사항)

패스워드 필드명이 `password`가 아니거나, 개인정보 필드를 지정하려면:

```php
class Promoter extends Authenticatable
{
    use HasPasswordSecurity;
    
    // 방법 1: 프로퍼티로 지정 (간단!)
    protected $passwordSecurityField = 'password';
    protected $passwordSecurityPersonalFields = [
        'name' => 'name',
        'email' => 'email',
        'channel' => 'channel',  // 추가 필드
        'store' => 'store',      // 추가 필드
    ];
    
    // 방법 2: 메서드로 지정 (동적 처리 가능)
    public function passwordSecurityFieldName(): string
    {
        return 'password';
    }
    
    public function passwordSecurityPersonalFields(): array
    {
        return [
            'name' => 'name',
            'email' => 'email',
            'channel' => 'channel',
            'store' => 'store',
        ];
    }
}
```

### 3. Config 설정

`config/password-security.php`에서 배치 작업 대상 모델만 등록:

```php
'models' => [
    \App\Models\User::class,
    \AppTenants\Models\Promoter::class,
],
```

**주의**: 이건 배치 작업(휴면 계정 처리, 만료 알림)용입니다. 
패스워드 검증은 Trait만 있으면 자동으로 작동합니다!

## 패스워드 변경 예제

### 컨트롤러

```php
<?php

namespace App\Http\Controllers;

use CmsOrbit\PasswordSecurity\Validators\PasswordValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    public function update(Request $request, PasswordValidator $validator)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|confirmed',
        ]);

        $user = $request->user();

        // 현재 패스워드 확인
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => '현재 패스워드가 일치하지 않습니다.'
            ]);
        }

        // 패스워드 보안 정책 검증
        try {
            $validator->validate($request->password, $user);
        } catch (\Exception $e) {
            return back()->withErrors(['password' => $e->getMessage()]);
        }

        // 패스워드 업데이트 (자동으로 히스토리 저장됨)
        $user->password = $request->password;
        $user->save();

        return redirect()->route('dashboard')
            ->with('success', '패스워드가 성공적으로 변경되었습니다.');
    }
}
```

## 로그인 시 마지막 로그인 시간 업데이트

### EventServiceProvider

```php
<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class => [
            UpdateLastLoginTimestamp::class,
        ],
    ];
}
```

### Listener

```php
<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class UpdateLastLoginTimestamp
{
    public function handle(Login $event)
    {
        $user = $event->user;
        
        if (method_exists($user, 'updateLastLogin')) {
            $user->updateLastLogin();
        }
    }
}
```

## API

### Trait 메서드

```php
// 패스워드 만료 확인
$user->isPasswordExpired(); // bool

// 만료까지 남은 일수
$user->getDaysUntilPasswordExpiration(); // int|null

// 계정 활성화 상태
$user->isAccountActive(); // bool

// 계정 비활성화
$user->deactivateAccount('inactivity');

// 계정 활성화
$user->activateAccount();

// 마지막 로그인 업데이트
$user->updateLastLogin();

// 비활성화까지 남은 일수
$user->getDaysUntilDeactivation(); // int|null

// 비활성화 대상 여부
$user->shouldBeDeactivated(); // bool

// 패스워드 히스토리에 있는지 확인
$user->isPasswordInHistory('NewPassword123!'); // bool

// 관계
$user->passwordSecurity; // PasswordSecurity 모델 (1:1)
$user->passwordHistories; // Collection (1:Many)
```

### Validator 메서드

```php
$validator = new PasswordValidator();

// 전체 검증
$validator->validate('MyPassword123!', $user);

// 개별 검증
$validator->validateComplexity('MyPassword123!'); // 복잡도만
$validator->validateCommonPattern('MyPassword123!'); // 패턴만
$validator->validatePersonalInfo('MyPassword123!', $user); // 개인정보만
$validator->validateHistory('MyPassword123!', $user); // 히스토리만

// 에러 가져오기
$validator->getErrors(); // ['complexity' => '...', 'history' => '...']
$validator->getErrorMessage(); // 첫 번째 에러
$validator->getAllErrorMessages(); // 모든 에러를 문자열로
```

## 배치 명령어

```bash
# 휴면 계정 처리
php artisan password-security:deactivate-inactive

# Dry-run (실제 처리 없이 확인만)
php artisan password-security:deactivate-inactive --dry-run

# 비활성화 전 알림 발송
php artisan password-security:deactivate-inactive --notify

# 패스워드 만료 알림
php artisan password-security:notify-expiration

# Dry-run
php artisan password-security:notify-expiration --dry-run

# 만료된 계정에 강제 변경 플래그 설정
php artisan password-security:notify-expiration --force-change
```

## 데이터베이스 구조

패키지는 2개의 Morph 테이블을 사용합니다:

### password_securities (1:1)
- securable_type, securable_id
- password_changed_at
- password_expires_at
- password_must_change
- is_active
- deactivated_at
- deactivation_reason
- last_login_at

### password_histories (1:Many)
- securable_type, securable_id
- password_hash
- changed_at
- changed_by
- ip_address
- user_agent

**기존 테이블 변경 필요 없음!** 👍

