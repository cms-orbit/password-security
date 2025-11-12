<?php

use Illuminate\Support\Facades\Route;
use CmsOrbit\PasswordSecurity\Http\Controllers\PasswordChangeController;

/*
|--------------------------------------------------------------------------
| Password Security Routes
|--------------------------------------------------------------------------
|
| 패키지에서 제공하는 비밀번호 변경 라우트입니다.
| 미들웨어는 사용자가 직접 적용해야 합니다.
|
*/

Route::middleware(['web', 'auth'])->group(function () {
    // 비밀번호 변경 화면
    Route::get('/password-security/change', [PasswordChangeController::class, 'showChangeForm'])
        ->name('password-security.change');

    // 비밀번호 변경 처리
    Route::post('/password-security/change', [PasswordChangeController::class, 'update'])
        ->name('password-security.update');
});

