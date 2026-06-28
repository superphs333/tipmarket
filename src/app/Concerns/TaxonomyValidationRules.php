<?php

namespace App\Concerns;

use Illuminate\Validation\Rule;

/**
* 카테고리와 태그 선택값을 검증하는 공통 규칙
* : 화면에서 선택지를 보여줘도 사용자가 요청 값을 조작할 수 있으므로, 서버에서도 *존재하는 활성 칸테고리/태그인지 다시 확인 
*/
trait TaxonomyValidationRules
{

    /**
     * 선택 사항인 카테고리 id를 검증
     * 
     * 허용)
     * - null : 카테고리 미선택
     * - 활성 상태인 categories.id
     * 
     * 차단)
     * - 존재하지 않는 카테고리
     * - 비활성 카테고리
     * - 정수가 아닌 값
     */
    protected function nullableActiveCategoryIdRules() : array
    {
        return [
            'nullable',
            'integer',
            Rule::exists('categories', 'id')->where('is_active', true), //[??]
        ];
    }

    /**
     * 태그 id 목록 자체를 검증 
     * 
     * - 배열 형태인지 확인
     * 
     * @return array <int, mixed>
     */
    protected function activeTagIdsRules() : array
    {
        return ['array'];
    }

    /**
     * 태그 id 하나를 검증
     * 
     * 허용)
     * - 활성 상태인 tags.id
     * 
     * 차단)
     * - 존재하지 않는 태그
     * - 비활성 태그
     * - 정수가 아닌 값 
     * 
     * @return array<int, mixed>
     */
    protected function activeTagIdRules(): array
    {
        return [
            'integer',
            Rule::exists('tags', 'id')->where('is_active', true),
        ];
    }
}