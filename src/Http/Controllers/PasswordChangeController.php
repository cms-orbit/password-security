<?php

namespace CmsOrbit\PasswordSecurity\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use CmsOrbit\PasswordSecurity\Rules\PasswordSecurityRule;

class PasswordChangeController extends Controller
{
    /**
     * 비밀번호 변경 폼 표시
     */
    public function showChangeForm()
    {
        $user = auth()->user();
        
        $viewName = config('password-security.views.password_change', 'password-security::password-change');
        
        return view($viewName, [
            'user' => $user,
        ]);
    }

    /**
     * 비밀번호 변경 처리
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        // Validation
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                'confirmed',
                new PasswordSecurityRule($user),
            ],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // 현재 비밀번호 확인
        $passwordField = method_exists($user, 'getPasswordFieldName') 
            ? $user->getPasswordFieldName() 
            : 'password';

        if (!Hash::check($request->current_password, $user->{$passwordField})) {
            return back()->withErrors([
                'current_password' => __('The current password is incorrect.'),
            ])->withInput();
        }

        // 비밀번호 업데이트
        $user->{$passwordField} = $request->password;
        $user->save();

        // PasswordSecurity 레코드 업데이트 (password_must_change = false)
        if (method_exists($user, 'passwordSecurity') && $user->passwordSecurity) {
            $user->passwordSecurity->password_must_change = false;
            $user->passwordSecurity->save();
        }

        // 성공 메시지와 함께 리다이렉트
        $redirectTo = config('password-security.expiration.redirect_after_change', '/');
        
        return redirect($redirectTo)->with('success', __('Password changed successfully.'));
    }
}

