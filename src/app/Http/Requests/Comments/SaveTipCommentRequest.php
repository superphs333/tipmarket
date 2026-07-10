<?php

namespace App\Http\Requests\Comments;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 댓글 등록 및 수정 요청을 검증하고 본문을 저장 가능한 형태로 정규화
 */
class SaveTipCommentRequest extends FormRequest
{
    // 로그인한 사용자만 댓글 저장 요청 처리 가능
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    // 앞뒤 공백 제거
    protected function prepareForValidation(): void
    {
        $body = $this->input('body');
        if (! is_string($body)) {
            return;
        }
        $this->merge(['body' => trim($body)]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'body' => [
                'required',
                'string',
                'max:1000',
            ],
        ];
    }

    // 댓글 저장 검증 실패 시 사용자에게 보여줄 메시지 반환
    public function messages(): array
    {
        return [
            'body.required' => '댓글 내용을 입력해주세요.',
            'body.string' => '댓글 내용은 문자열이어야 합니다.',
            'body.max' => '댓글은 1,000자 이하로 입력해주세요.',
        ];
    }

    /**
     * 검증 오류에서 사용할 입력 항목의 표시 이름을 반환한다.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'body' => '댓글',
        ];
    }
}
