<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * 저장 전 입력값 검증 규칙을 모아둠.
 */
trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
            // Profile 저장 시 언어 설정도 검증된 값에 포함시켜 User::fill()로 저장되게 한다.
            'locale' => $this->localeRules(),
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }

    /**
     * Get the validation rules used to validate user display language.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function localeRules(): array
    {
        return [
            'required',
            'string',
            // config/app.php의 지원 언어 목록만 허용해 임의 locale 값 저장을 막는다.
            Rule::in(array_keys((array) config('app.supported_locales', []))),
        ];
    }
}
