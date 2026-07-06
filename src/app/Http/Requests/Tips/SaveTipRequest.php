<?php

namespace App\Http\Requests\Tips;

use App\Models\Tip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class SaveTipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * 폼에서 넘어온 값을 검증 전에 저장 정책에 맞는 형태로 정리한다.
     *
     * 카테고리 select의 "선택 안 함" option은 빈 문자열을 보낸다.
     * tips.category_id는 nullable foreign key이므로 빈 문자열을 DB에 넘기지 않고 null로 통일한다.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'category_id' => $this->input('category_id') === ''
                ? null
                : $this->input('category_id'),
        ]);
    }

    /**
     * 팁 저장 폼의 공통 입력 규칙을 반환한다.
     *
     * draft는 카테고리 없이 저장할 수 있지만, published 상태는 실제 목록/탐색에 노출되므로
     * 카테고리를 필수로 요구한다.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'content' => ['required', 'string'],
            'category_id' => [
                Rule::requiredIf(fn (): bool => $this->input('status') === Tip::STATUS_PUBLISHED),
                'nullable',
                'integer',
                'exists:categories,id',
            ],
            'status' => ['required', 'string', Rule::in(Tip::STATUSES)],
            'audience' => ['required', 'string', Rule::in(Tip::AUDIENCES)],
            'tag_names' => ['nullable', 'array'],
            'tag_names.*' => ['string', 'max:50'],
            'thumbnail' => [
                'nullable',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(5 * 1024),
            ],
            'delete_thumbnail' => ['sometimes', 'boolean'],
            /**
             * 본문 이미지 점검
             */
            'uploaded_body_image_ids' => ['nullable', 'array'],
            'uploaded_body_image_ids.*' => ['integer'],
        ];
    }
}
