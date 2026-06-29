<?php

namespace App\Data\Tips;

use InvalidArgumentException;

/**
 * 저장 전 팁 초안 데이터를 하나의 형태로 통일하는 값 객체.
 *
 * [목적]
 * - ai 생성 결과
 * - 사용자 직접 작성 폼
 * - 관리자 일괄 등록
 * - CSV/import 데이터
 * => 출처가 달라도, 최종 저장 로직에는 동일한 데이터 형태로 넘기기 위함
 */
final readonly class TipDraftData
{
    /**
     * @param  array<int, int>  $tagIds : 이미 db에 존재하는 태그 id 목록
     * @param  array<int, string>  $tagNames 
     */
    public function __construct(
        public string $title,
        public string $content,
        public ?int $categoryId = null,
        public array $tagIds = [],
        public ?string $summary = null,
        public array $tagNames = [],
    ) {
        if (trim($this->title) === '') {
            throw new InvalidArgumentException('Tip draft title is required.');
        }

        if (trim($this->content) === '') {
            throw new InvalidArgumentException('Tip draft content is required.');
        }
    }

    /**
     * 검증된 폼 입력이나 내부 배열을 TipDraftData로 변환한다.
     * 
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $summary = isset($payload['summary'])
            ? trim((string) $payload['summary'])
            : null;

        return new self(
            title: trim((string) ($payload['title'] ?? '')),
            content: trim((string) ($payload['content'] ?? '')),
            categoryId: self::nullableInt($payload['category_id'] ?? $payload['categoryId'] ?? null),
            // 이미지 존재하는 태그를 연결할 때 사용
            tagIds: self::normalizeTagIds($payload['tag_ids'] ?? $payload['tagIds'] ?? []),
            summary: $summary !== '' ? $summary : null,
            // ai가 제안한 신규 태그 후보처럼 아직 db id가 없을 때 사용.
            tagNames: self::normalizeTagNames($payload['tag_names'] ?? $payload['tagNames'] ?? $payload['tags'] ?? []),
        );
    }

    /**
     * AI 응답 배열을 앱 내부 표준 초안 데이터로 변환한다.
     *
     * 기대하는 AI 응답 예:
     * [
     *     'title' => '전세 계약 전 확인할 항목',
     *     'content' => '<h2>전세 계약 전 확인할 항목</h2><p>등기부등본을 확인하세요.</p>',
     *     'summary' => '전세 계약 전 확인할 핵심 항목입니다.',
     *     'tags' => ['전세', '계약'],
     * ]
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromAiPayload(array $payload, ?int $categoryId = null, array $tagIds = []): self
    {
        $data = self::fromArray([
            'title' => $payload['title'] ?? null,
            'content' => $payload['content'] ?? null,
            'summary' => $payload['summary'] ?? null,
            'category_id' => $categoryId,
            'tag_ids' => $tagIds,
            'tag_names' => $payload['tagNames'] ?? $payload['tags'] ?? [],
        ]);

        return $data;
    }

    /**
     * Tip::create()에 넘길 수 있는 기본 속성 배열을 반환한다.
     *
     * @return array<string, mixed>
     */
    public function toTipAttributes(): array
    {
        return [
            'category_id' => $this->categoryId,
            'title' => $this->title,
            'content' => $this->content,
        ];
    }

    /**
     * 선택되지 않은 카테고리는 null로 유지하고, 값이 들어온 경우에만 int로 변환 
     */
    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * 기존 태그 id 목록을 저장 로직에서 바로 사용할 수 있게 정리
     * 
     * 
     * @return array<int, int>
     */
    private static function normalizeTagIds(mixed $tagIds): array
    {
        if (! is_array($tagIds)) {
            return [];
        }

        return collect($tagIds)
            ->map(fn (mixed $tagId): int => (int) $tagId) // int로 변환
            ->filter(fn (int $tagId): bool => $tagId > 0) // 0 이하 제거
            ->unique() // 중복제거
            ->values()
            ->all();
    }

    /**
     * AI/import가 제안한 태그명 목록을 정리
     * 
     * @return array<int, string>
     */
    private static function normalizeTagNames(mixed $tagNames): array
    {
        if (! is_array($tagNames)) {
            return [];
        }

        return collect($tagNames)
            ->map(fn (mixed $tagName): string => trim((string) $tagName))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
