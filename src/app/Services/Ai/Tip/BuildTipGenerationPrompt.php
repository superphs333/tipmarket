<?php

namespace App\Services\Ai\Tip;

/**
 * AI 팁 생성 모달 입력값을 외부 AI 모델에 전달할 프롬프트 문자열로 변환
 * - Livewire 모달에서 검증된 입력값을 AI 요청 문장으로 ㅗ리
 * - 선택된 카테고리명과 태그명을 사람이 읽을 수 있는 맥락으로 포함
 * - AI 응답이 TipDraftData::fromAiPayload()로 변환 가능한 JSON 구조가 되도록 형식을 지시
 */
final class BuildTipGenerationPrompt
{

    /**
     * 검증된 모달 입력값을 AI 팁 생성용 프롬프트로 변환
     * 
     */
    public function __invoke(
        string $prompt,
        int $count = 1,
        ?string $categoryName = null,
        array $tagNames = [],
    ) : string
    {
        $normalizedPrompt = trim($prompt);
        $normalizedCategoryName = $categoryName !== null && trim($categoryName) !== '' 
            ? trim($categoryName)
            : '미지정';
        $lines = [
            'TipMarket 관리자용 팁 초안을 생성해줘.',
            '',
            '[사용자 요청]',
            $normalizedPrompt,
            '',
            '[생성 조건]',
            '- 생성 개수: '.$count.'개',
            '- 카테고리: '.$normalizedCategoryName,
            '- 참고 태그: '.$this->formatTagNames($tagNames),
            '',
            '[작성 기준]',
            '- 생활 문제를 해결하는 실용적인 팁으로 작성한다.',
            '- 제목은 검색과 클릭을 고려해 명확하게 작성한다.',
            '- 본문은 바로 저장 가능한 HTML 조각으로 작성한다.',
            '- 과장된 표현, 확인되지 않은 사실, 위험한 조언은 피한다.',
            '- 사용자가 실제로 따라 할 수 있는 단계와 주의사항을 포함한다.',
            '',
            '[응답 형식]',
            'JSON 배열로만 응답한다.',
            '각 항목은 title, summary, content, tags 키를 가진다.',
            'content는 <h2>, <p>, <ul>, <li> 등을 사용한 HTML 문자열로 작성한다.',
            'tags는 문자열 배열로 작성한다.',
        ];
        return implode("\n", $lines);
    }

    /**
     * 선택된 태그명을 프롬프트에 넣기 좋은 형태로 정리
     */
    private function formatTagNames(array $tagNames) : string
    {
        $names = collect($tagNames)
            ->map(fn (string $tagName): string => trim($tagName))
            ->filter()
            -> unique()
            ->values();
        if($names->isEmpty()) return '미지정';
        return $names -> map(fn (string $tagName) : string => '#'.$tagName)->implode(',');
    }
}