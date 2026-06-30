<?php

namespace App\Actions\Tags;

use App\Models\Tag;

/**
 * 기존 태그 id와 신규 태그명 후보를 실제 연결 가능한 태그 id 목록으로 정리 
 * 
 * [입력값]
 * - tagIds : 이미 db에 존재하는 태그 id 목록 
 *  ex) 사용자가 AI 모달에서 기존 태그를 선택한 경우 
 * - tagNames : 아직 DB id가 없는 태그명 후보 
 *  ex) ai 응답의 tags 배열, 일반 작성 폼에서 사용자가 새로 입력한 태그명 
 * 
 * [처리 결과]
 * tag id 배열
 */
final class FindOrCreateTags
{
    // 자동 생성할 태그명의 최소 글자 수
    private const MIN_NAME_LENGTH = 2;
    // 자동 생성할 태그명의 최대 글자 수.
    private const MAX_NAME_LENGTH = 50;

    /**
     * 기존 tagIds와 신규 tagNames를 합쳐 최종 태그 id 목록을 반환한다.
     * 
     * [처리순서]
     * 1. 기존 tagIds를 정소 배열로 정리
     * 2. tagNames를 저장 정책에 맞게 정규화
     * 3. 유효하지 않은 tagNames를 제외
     * 4. tagName을 name기준으로 찾거나 생성
     * 5. 기존 tagIds와 생성/조회된 tag id를 합친다. 
     * 6. 최종 중복을 제거해 tag_tip에 연결 가능한 id 배열로 반환.
     *
     * @param  array<int, int>  $tagIds 이미 DB에 존재하는 태그 id 목록
     * @param  array<int, string>  $tagNames AI나 폼에서 들어온 신규 태그명 후보
     * @return array<int, int> tag_tip에 연결할 최종 태그 id 목록
     */
    public function __invoke(array $tagIds = [], array $tagNames = []): array
    {
        // 기존 tagIds 
        $existingTagIds = collect($tagIds)
            ->map(fn (mixed $tagId): int => (int) $tagId) // int로 통일
            ->filter(fn (int $tagId): bool => $tagId > 0) // 0이하 값 제거 
            ->unique()
            ->values();

        // 생성할 tagNames 
        $createdOrFoundTagIds = collect($tagNames)
            ->map(fn (string $tagName): string => $this->normalizeName($tagName))
            ->filter(fn (string $tagName): bool => $this->isValidName($tagName))
            ->unique()
            ->map(fn (string $tagName): int => $this->findOrCreate($tagName)->id)
            ->values();

        // 기존 선택 태그와 ai/신규 태그를 합침. 
        return $existingTagIds
            ->merge($createdOrFoundTagIds)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * 외부에서 들어온 태그명을 저장 전 표준 형태로 정리
     * 
     * 정리값] 
     * : 앞 뒤 공백, 사용자가 붙였거나 AI가 붙였을 수도 있는 #기호, 모든 공백 문자 
     */
    private function normalizeName(string $tagName): string
    {
        $tagName = trim($tagName);
        $tagName = ltrim($tagName, '#');

        return preg_replace('/\s+/u', '', $tagName) ?? $tagName;
    }

    /**
     * 자동 생성해도 되는 태그명인지 확인한다 ( 너무 짧거나, 긴 태그 방지)
     */
    private function isValidName(string $tagName): bool
    {
        $length = mb_strlen($tagName);

        return $length >= self::MIN_NAME_LENGTH
            && $length <= self::MAX_NAME_LENGTH;
    }

    /**
     * 공백이 제거된 name을 기준으로 기존 태그를 찾거나 새로 생성한다.
     */
    private function findOrCreate(string $tagName): Tag
    {
        // 같은 name이 이미 있으면 새 태그를 만들지 않고 기존 태그를 재사용 
        $existingTag = Tag::query()
            ->where('name', $tagName)
            ->first();

        if ($existingTag !== null) {
            return $existingTag;
        }

        // 기존 name이 없을 때만 새 태그를 만듦
        return Tag::query()->create([
            'name' => $tagName,
            'slug' => $this->makeUniqueSlug($tagName),
            'description' => null,
            'usage_count' => 0,
            'is_active' => true,
        ]);
    }

    /**
     * 태그명으로 unique slug를 만든다.
     *
     * 태그 URL에서도 의미가 보이도록 한글, 영문, 숫자는 그대로 유지한다.
     * slug로 쓸 문자가 하나도 없을 때만 hash fallback을 사용한다.
     */
    private function makeUniqueSlug(string $tagName): string
    {
        $baseSlug = $this->makeBaseSlug($tagName);

        $slug = $baseSlug;
        $index = 2;

        while (Tag::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$index}";
            $index++;
        }

        return $slug;
    }

    /**
     * 태그명에서 URL에 사용할 기본 slug를 만든다.
     *
     * 한글 서비스의 태그 URL은 번역보다 원문 의미가 유지되는 편이 더 읽기 쉽다.
     * 예: "전세계약" -> "전세계약", "주방청소" -> "주방청소"
     */
    private function makeBaseSlug(string $tagName): string
    {
        $slug = mb_strtolower($tagName);
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        if ($slug !== '') {
            return $slug;
        }

        return 'tag-'.substr(sha1(mb_strtolower($tagName)), 0, 12);
    }
}
