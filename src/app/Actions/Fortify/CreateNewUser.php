<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // 회원가입 폼에는 언어 선택이 없으므로 앱 기본 언어를 사용자 기본값으로 채운다.
        $input['locale'] ??= config('app.locale', 'ko');

        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            // ProfileValidationRules에서 검증한 locale을 신규 사용자 설정으로 저장한다.
            'locale' => $input['locale'],
            'password' => $input['password'],
        ]);
    }
}
