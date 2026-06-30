<?php

namespace App\Services\Ai\Tip;

/**
 * AI 팁 생성 모달 입력값을 AI 요청 프롬프트 문자열로 변환
 * : 관리자가 입력한 요청 내용, 생성 개수, 카테고리명, 태그명을 프롬프트로 조립
 * , AI가 TipDraftData로 변환 가능한 JSON 구조를 반환하도록 응답 형식을 지시
 */
final class BuildTipGenerationPrompt
{
    /**
     * @param  array<int, string>  $tagNames
     */
    public function __invoke(
        string $prompt,
        int $count = 1,
        ?string $categoryName = null,
        array $tagNames = [],
    ): string {
        $category = $categoryName !== null && trim($categoryName) !== ''
            ? trim($categoryName)
            : '카테고리 제한 없음';
        $request = trim($prompt) !== ''
            ? trim($prompt)
            : '별도 요청 없음. 선택된 카테고리와 참고 태그를 바탕으로 TipMarket에 어울리는 실용적인 팁 주제를 직접 정해 작성한다.';

        return implode("\n", [
            'TipMarket 관리자용 팁 초안을 생성해줘.',
            '',
            '[사용자 요청]',
            $request,
            '',
            '[생성 조건]',
            '- 생성 개수: '.$count.'개',
            '- 카테고리: '.$category,
            '- 참고 태그: '.$this->formatTagNames($tagNames),
            '',
            '[작성 기준]',
            '- 생활 문제를 해결하는 실용적인 팁으로 작성한다.',
            '- 제목은 검색과 클릭을 고려해 명확하게 작성한다.',
            '- 본문은 바로 저장 가능한 HTML 조각으로 작성한다.',
            '- 과장된 표현, 확인되지 않은 사실, 위험한 조언은 피한다.',
            '- 사용자가 실제로 따라 할 수 있는 단계와 주의사항을 포함한다.',
            '- 각 팁마다 본문 주제에 맞는 태그를 생성한다.',
            '- 참고 태그가 있으면 모든 팁의 tags에 반드시 포함한다.',
            '- 참고 태그만으로 부족하면 본문에 더 적합한 태그를 추가로 제안한다.',
            '- 태그는 짧은 한국어 명사형 키워드로 작성한다.',
            '- 태그에는 공백과 # 기호를 넣지 않는다. 예: "전세 계약"이 아니라 "전세계약"으로 작성한다.',
            '',
            '[응답 형식]',
            'JSON 객체로만 응답한다.',
            '최상위 키는 tips로 한다.',
            'tips는 배열이며 각 항목은 title, summary, content, tags 키를 가진다.',
            'content는 <h2>, <p>, <ul>, <li> 등을 사용한 HTML 문자열로 작성한다.',
            'tags는 각 팁 내용을 대표하는 문자열 배열이며 참고 태그 전체와 추가 추천 태그를 포함한다.',
        ]);
    }

    /**
     * 선택한 태그명을 AI 프롬프트에 넣기 좋은 키워드 문자열로 변환
     *
     * 입력 전제]
     * - $tagNames는 AI 모달에서 선택한 기존/신규 태그명 목록
     * - DB id는 외부 ai에 전달하지 않고, 사람이 읽을 수 있는 이름만 전달
     *
     * 반환 예]
     * - 태그 있음 : "#전세, #계약, #청소"
     * - 태그 없음 : "태그 제한 없음"
     *
     * @param  array<int, string>  $tagNames
     */
    private function formatTagNames(array $tagNames): string
    {
        $names = collect($tagNames)
            ->map(fn (string $tagName): string => preg_replace('/\s+/u', '', trim($tagName)) ?? trim($tagName))
            ->filter()
            ->unique()
            ->values();

        // 태그가 없을 때 => 태그 제한 없음
        if ($names->isEmpty()) {
            return '태그 제한 없음';
        }

        return $names
            ->map(fn (string $tagName): string => '#'.$tagName)
            ->implode(', ');
    }
}
