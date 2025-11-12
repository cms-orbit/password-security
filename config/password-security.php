<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Password Security Enabled
    |--------------------------------------------------------------------------
    |
    | 패스워드 보안 기능 전체를 활성화/비활성화합니다.
    |
    */
    'enabled' => env('PASSWORD_SECURITY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Target Models
    |--------------------------------------------------------------------------
    |
    | HasPasswordSecurity Trait을 사용하는 모델 목록입니다.
    | 배치 작업(휴면 계정 처리, 만료 알림 등)에 사용됩니다.
    |
    | 참고: 각 모델에서 필드명은 다음과 같이 지정할 수 있습니다:
    | - protected $passwordSecurityField = 'password';
    | - protected $passwordSecurityPersonalFields = ['name', 'email', ...];
    |
    */
    'models' => [
        // \App\Models\User::class,
        // \AppTenants\Models\Promoter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Complexity Rules
    |--------------------------------------------------------------------------
    |
    | 패스워드 복잡도 규칙을 설정합니다.
    | - 영문 대문자, 소문자, 숫자, 특수문자 중 2가지 이상 조합 시 10자 이상
    | - 3가지 이상 조합 시 8자 이상
    |
    */
    'complexity' => [
        'enabled' => true,
        'min_length_2_types' => 10,         // 2가지 조합 시 최소 길이
        'min_length_3_types' => 8,          // 3가지 조합 시 최소 길이
        'min_length_4_types' => 8,          // 4가지 조합 시 최소 길이
        'require_uppercase' => false,        // 대문자 필수 여부
        'require_lowercase' => false,        // 소문자 필수 여부
        'require_numbers' => false,          // 숫자 필수 여부
        'require_special' => false,          // 특수문자 필수 여부
        'special_characters' => '!@#$%^&*()_+-=[]{}|;:,.<>?~`', // 허용 특수문자
    ],

    /*
    |--------------------------------------------------------------------------
    | Common Patterns Blocking
    |--------------------------------------------------------------------------
    |
    | 추측하기 쉬운 일반적인 패턴을 차단합니다.
    | - 연속된 숫자/문자, 키보드 패턴, 일반 단어, 생일/전화번호 패턴 등
    |
    */
    'common_patterns' => [
        'enabled' => true,
        'block_sequential_numbers' => true,      // 연속 숫자 차단 (123456, 987654)
        'block_sequential_letters' => true,      // 연속 문자 차단 (abcdef, fedcba)
        'block_repeated_characters' => true,     // 반복 문자 차단 (aaaaaa, 111111)
        'repeated_char_threshold' => 3,          // 반복 허용 횟수
        'block_keyboard_patterns' => true,       // 키보드 패턴 차단 (qwerty, asdfgh)
        'block_common_words' => true,            // 일반 단어 차단
        'block_birthday_patterns' => true,       // 생일 패턴 차단 (19900101, 2000-01-01)
        'block_phone_patterns' => true,          // 전화번호 패턴 차단 (01012345678)
        'sequential_threshold' => 4,             // 연속 문자 허용 개수
        'common_words_list' => [                 // 차단할 일반 단어
            'password', 'admin', 'welcome', 'qwerty', 'letmein',
            '1234', '12345', '123456', '1234567', '12345678',
            'pass', 'user', 'guest', 'test', 'demo','abc','qwer','asd'
        ],
        'keyboard_patterns' => [                 // 키보드 패턴
            'qwerty', 'qwertyuiop', 'asdfgh', 'asdfghjkl', 'zxcvbn', 'zxcvbnm',
            'qazwsx', 'qaz', 'wsx', 'edc', 'rfv', 'tgb', 'yhn', 'ujm',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Personal Information Blocking
    |--------------------------------------------------------------------------
    |
    | 개인정보(이름, 이메일 등)가 패스워드에 포함되는 것을 차단합니다.
    |
    */
    'personal_info' => [
        'enabled' => true,
        'block_name' => true,                    // 이름 포함 차단
        'block_email' => true,                   // 이메일 포함 차단
        'block_username' => true,                // 사용자명 포함 차단
        'additional_fields' => [],               // 추가 차단 필드 (예: 'phone', 'employee_id')
        'min_substring_length' => 3,             // 최소 부분 문자열 길이
        'case_insensitive' => true,              // 대소문자 구분 없이 검사
    ],

    /*
    |--------------------------------------------------------------------------
    | Password History Management
    |--------------------------------------------------------------------------
    |
    | 패스워드 히스토리를 관리하여 최근 사용한 패스워드 재사용을 방지합니다.
    |
    */
    'history' => [
        'enabled' => true,
        'check_last_n_passwords' => 3,          // 최근 N개 패스워드 체크
        'keep_history_for_days' => 365,         // 히스토리 보관 기간 (일)
        'auto_cleanup' => true,                 // 자동 정리 활성화
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Expiration Settings
    |--------------------------------------------------------------------------
    |
    | 패스워드 만료 정책을 설정합니다.
    | 분기(90일) 1회 이상 패스워드 변경을 강제합니다.
    |
    */
    'expiration' => [
        'enabled' => true,
        'expires_in_days' => 90,                 // 패스워드 만료 기간 (분기 = 90일)
        'notify_before_days' => [7, 3, 1],       // 만료 전 알림 (일)
        'grace_period_days' => 0,                // 만료 후 유예기간 (일)
        'force_change_on_first_login' => false,  // 첫 로그인 시 강제 변경

        // 강제 변경 라우트 설정
        'force_change_route' => 'password.change',      // 라우트명
        'force_change_url' => '/password/change',       // 또는 URL

        // 미들웨어 제외 라우트
        'excluded_routes' => [
            'password.change',           // 비밀번호 변경 페이지
            'password.update',           // 비밀번호 변경 처리
            'password.expired',          // 만료 안내 페이지
            'logout',                    // 로그아웃
        ],

        // 미들웨어 제외 URL 패턴
        'excluded_urls' => [
            'password/*',                // 패스워드 관련 모든 URL
            'api/*',                     // API 제외
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Inactive Accounts Management
    |--------------------------------------------------------------------------
    |
    | 휴면 계정 관리 정책을 설정합니다.
    | 일정 기간 미사용 계정을 자동으로 비활성화합니다.
    |
    */
    'inactive_accounts' => [
        'enabled' => true,
        'inactive_days' => 90,                   // 휴면 기준 (일)
        'notify_before_days' => [14, 7, 3],      // 비활성화 전 알림 (일)
        'auto_deactivate' => true,               // 자동 비활성화 여부
        'delete_after_days' => null,             // 비활성화 후 삭제 기간 (null이면 삭제 안함)

        // 휴면 계정 처리 제외 조건
        'exclusions' => [
            'roles' => [],                       // 제외할 역할 (예: ['super-admin'])
            'emails' => [],                      // 제외할 이메일
            'has_active_sessions' => true,       // 활성 세션이 있으면 제외
        ],

        // 계정 활성화 상태 필드명
        'active_field' => 'is_active',
        'deactivated_at_field' => 'deactivated_at',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exception Handling
    |--------------------------------------------------------------------------
    |
    | 예외 처리 방식을 설정합니다.
    |
    */
    'exceptions' => [
        'throw_exceptions' => true,              // true면 Exception 발생, false면 validation errors 반환
        'log_violations' => true,                // 위반 사항 로그 기록
        'log_channel' => null,                   // 로그 채널 (null이면 기본 채널)
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | 알림 설정을 구성합니다.
    |
    */
    'notifications' => [
        'enabled' => true,
        'channels' => ['mail'],                  // 알림 채널 (mail, database, slack 등)

        // 만료 알림
        'expiration' => [
            'enabled' => true,
            'mail_subject' => 'Password Expiration Notice',
        ],

        // 휴면 계정 알림
        'inactive_account' => [
            'enabled' => true,
            'mail_subject' => 'Account Inactivity Notice',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Table Names
    |--------------------------------------------------------------------------
    |
    | 패키지에서 사용할 데이터베이스 테이블명을 지정합니다.
    |
    */
    'tables' => [
        'password_securities' => 'password_securities',
        'password_histories' => 'password_histories',
    ],

];

