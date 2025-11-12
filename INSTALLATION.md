# 설치 및 설정 가이드

## 1. 로컬 개발 환경 설정 (bi-survey 프로젝트)

### Composer 설정 확인

`bi-survey/composer.json`에 이미 로컬 저장소가 추가되어 있습니다:

```json
"repositories": {
    "4": {
        "type": "path",
        "url": "./packages/password-security"
    }
}
```

### 패키지 설치

```bash
cd /Users/xiso/projects/bi-survey
composer require cms-orbit/password-security:@dev
```

## 2. 설정 파일 및 마이그레이션 발행

```bash
# Config 파일 발행
php artisan vendor:publish --tag=password-security-config

# Migration 파일 발행
php artisan vendor:publish --tag=password-security-migrations

# 언어 파일 발행 (선택사항)
php artisan vendor:publish --tag=password-security-lang

# 마이그레이션 실행
php artisan migrate
```

## 3. 모델 설정

### User 모델에 Trait 추가

`app/Models/User.php`:

```php
<?php

namespace App\Models;

use CmsOrbit\PasswordSecurity\Traits\HasPasswordSecurity;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasPasswordSecurity;
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'password_changed_at',
        'password_expires_at',
        'password_must_change',
        'is_active',
        'deactivated_at',
        'deactivation_reason',
        'last_login_at',
    ];
    
    // ... 기타 코드
}
```

### Promoter 모델에 Trait 추가

`app-tenants/Models/Promoter.php`:

```php
<?php

namespace AppTenants\Models;

use CmsOrbit\PasswordSecurity\Traits\HasPasswordSecurity;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Promoter extends Authenticatable
{
    use HasPasswordSecurity;
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'plain_password',
        'channel',
        'store',
        'fcm_token',
        'password_changed_at',
        'password_expires_at',
        'password_must_change',
        'is_active',
        'deactivated_at',
        'deactivation_reason',
        'last_login_at',
    ];
    
    // ... 기타 코드
}
```

## 4. Config 설정

`config/password-security.php`:

```php
return [
    'enabled' => env('PASSWORD_SECURITY_ENABLED', true),

    'models' => [
        \App\Models\User::class => [
            'password_field' => 'password',
            'personal_info_fields' => [
                'name' => 'name',
                'email' => 'email',
            ],
        ],
        
        \AppTenants\Models\Promoter::class => [
            'password_field' => 'password',
            'plain_password_field' => 'plain_password',
            'personal_info_fields' => [
                'name' => 'name',
                'email' => 'email',
                'channel' => 'channel',
                'store' => 'store',
            ],
        ],
    ],
    
    // 나머지는 기본 설정 사용
];
```

## 5. Middleware 등록

`app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // ... 기존 미들웨어
    'password.expiration' => \CmsOrbit\PasswordSecurity\Middleware\CheckPasswordExpiration::class,
    'account.active' => \CmsOrbit\PasswordSecurity\Middleware\CheckAccountActive::class,
];
```

### 라우트에 적용

`routes/tenants/web.php` (Promoter용):

```php
Route::middleware(['auth:promoter', 'password.expiration', 'account.active'])->group(function () {
    // 보호된 라우트
});
```

`routes/central/web.php` (User용):

```php
Route::middleware(['auth', 'password.expiration', 'account.active'])->group(function () {
    // 보호된 라우트
});
```

## 6. 스케줄러 등록

`app/Console/Kernel.php`:

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

## 7. 패스워드 변경 페이지 생성

### 라우트 추가

```php
Route::get('/password/change', [PasswordController::class, 'showChangeForm'])
    ->middleware('auth')
    ->name('password.change');
    
Route::post('/password/change', [PasswordController::class, 'update'])
    ->middleware('auth')
    ->name('password.update');
```

### 컨트롤러 예제

```php
<?php

namespace App\Http\Controllers;

use CmsOrbit\PasswordSecurity\Validators\PasswordValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    public function showChangeForm()
    {
        return view('password.change');
    }

    public function update(Request $request, PasswordValidator $validator)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|confirmed',
        ]);

        $user = $request->user();

        // 현재 패스워드 확인
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => '현재 패스워드가 일치하지 않습니다.']);
        }

        // 패스워드 보안 정책 검증
        try {
            $validator->validate($request->password, $user);
        } catch (\Exception $e) {
            return back()->withErrors(['password' => $e->getMessage()]);
        }

        // 패스워드 업데이트 (Observer가 자동으로 히스토리 저장)
        $user->password = $request->password;
        $user->save();

        return redirect()->route('dashboard')
            ->with('success', '패스워드가 성공적으로 변경되었습니다.');
    }
}
```

## 8. 마이그레이션 파일 수정

기존 테이블에 필드 추가가 필요합니다:

### Users 테이블

```bash
php artisan make:migration add_password_security_fields_to_users_table
```

```php
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->timestamp('password_changed_at')->nullable()->after('password');
        $table->timestamp('password_expires_at')->nullable()->after('password_changed_at');
        $table->boolean('password_must_change')->default(false)->after('password_expires_at');
        $table->boolean('is_active')->default(true)->after('password_must_change');
        $table->timestamp('deactivated_at')->nullable()->after('is_active');
        $table->string('deactivation_reason')->nullable()->after('deactivated_at');
        $table->timestamp('last_login_at')->nullable()->after('deactivation_reason');
    });
}
```

### Promoters 테이블

```bash
php artisan make:migration add_password_security_fields_to_promoters_table
```

## 9. 로그인 리스너 추가

`app/Providers/EventServiceProvider.php`:

```php
use Illuminate\Auth\Events\Login;

protected $listen = [
    Login::class => [
        UpdateLastLoginTimestamp::class,
    ],
];
```

`app/Listeners/UpdateLastLoginTimestamp.php`:

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

## 10. 테스트

```bash
# Dry-run으로 테스트
php artisan password-security:deactivate-inactive --dry-run
php artisan password-security:notify-expiration --dry-run
```

## 11. GitHub에 패키지 업로드 (완료 후)

```bash
cd packages/password-security
git commit -m "Initial release v1.0.0"
git tag v1.0.0
git push origin main --tags
```

그 후 `composer.json`에서:

```json
"repositories": {
    "4": {
        "type": "vcs",
        "url": "git@github.com:cms-orbit/password-security.git"
    }
}
```

```bash
composer require cms-orbit/password-security:^1.0
```

