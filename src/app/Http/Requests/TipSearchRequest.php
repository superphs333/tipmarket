<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 프론트 Tip 검색 페이지의 쿼리스트링 형식을 검증한다.
 *
 * 검색 조건의 허용 여부와 기본값 처리는 TipListQuery가 담당하고,
 * 이 요청 객체는 Blade와 쿼리에 배열 등 잘못된 타입이 전달되는 것을 막는다.
 */
class TipSearchRequest extends FormRequest
{
    /**
     * 공개 검색 페이지이므로 모든 사용자의 요청을 허용한다.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:160'],
            'category' => ['nullable', 'integer', 'min:1'],
            'tag_names' => ['nullable', 'array'],
            'tag_names.*' => ['string', 'max:50'],
            // 실제 허용 정렬값과 fallback은 공통 TipListQuery에서 한 번만 관리한다.
            'sort' => ['nullable', 'string', 'max:20'],
        ];
    }
}
