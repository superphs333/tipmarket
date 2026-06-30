<?php

namespace App\Livewire\Tags;

use App\Models\Tag;
use App\Services\Tags\TagSearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class TagSelector extends Component
{
    // 화면에 표시할 레벨
    public string $label = '태그';

    // 검색 input의 placeholder 문구
    public string $placeholder = '태그 이름 검색...';

    // form submit 시 hidden input의 name값
    public string $name = 'tag_ids';

    // wire:model로 부모에게 넘길 값의 종류. 기본은 기존 태그 id 배열이다.
    public string $valueMode = 'ids';

    // 선택 가능한 최대 태그 개수. null이면 제한하지 않는다.
    public ?int $maxCount = null;

    // 사용자가 입력한 검색어
    public string $query = '';

    // 검색을 단 한번이라도 실행했는지 여부 (true => 검색 결과 드롭다운이 열림.)
    public bool $hasSearched = false;

    /**
     * 선택된 태그 목록
     *
     * * 예:
     * [
     *     ['id' => 1, 'name' => '청소', 'isNew' => false],
     *     ['id' => 'new-a1b2', 'name' => '수납', 'isNew' => true],
     * ]
     *
     * @var array<int, array{id: int|string, name: string, isNew?: bool}>
     */
    public array $selectedTags = [];

    #[Modelable]
    public array $value = [];

    /**
     * Livewire 컴포넌트가 처음 생성될 때 실행되는 초기화 메서드.
     *
     * @param  iterable<int, mixed>  $selected
     */
    public function mount(
        string $label = '태그',
        string $placeholder = '태그 이름 검색...',
        string $name = 'tag_ids',
        string $valueMode = 'ids',
        ?int $maxCount = null,
        iterable $selected = [],
    ): void {
        $this->label = $label;
        $this->placeholder = $placeholder;
        $this->name = $name;
        $this->valueMode = $valueMode;
        $this->maxCount = $maxCount;
        $this->selectedTags = $this->normalizeTags($selected); // 내부에서 쓰기 좋은 배열 형태로 통일.
        $this->syncValue();
    }

    // 검색 실행 메서드
    public function search(): void
    {
        $this->query = trim($this->query);
        $this->hasSearched = $this->query !== '';
    }

    // 검색창과 검색 결과를 닫는 메서드.
    public function closeSearch(): void
    {
        $this->query = ''; // 검색어 초기화
        $this->hasSearched = false; // 검색 결과 드롭다운 닫기.
    }

    // 검색 결과에서 태그를 선택했을 때 실행.
    public function addTag(int $tagId): void
    {
        // 이미 최대 개수를 채웠거나 이미 선택된 태그면 아무 작업도 하지 않음
        if ($this->isMaxReached() || $this->isSelected($tagId)) {
            return;
        }

        // db에서 활성화된 태그만 조회
        $tag = Tag::query()
            ->where('is_active', true)
            ->find($tagId, ['id', 'name']);

        // 존재하지 않거나 비활성 태그면 추가하지 않음
        if (! $tag) {
            return;
        }
        // 선택된 태그 목록에 추가
        $this->selectedTags[] = [
            'id' => $tag->id,
            'name' => $tag->name,
        ];

        $this->syncValue();
    }

    /**
     * 검색어를 신규 태그 후보로 선택 목록에 추가한다.
     *
     * 이 메서드는 태그를 DB에 바로 저장하지 않는다. 태그 선택기는 입력 부품이므로
     * 신규 태그 후보를 `selectedTags`에만 추가하고, 실제 생성은 부모 폼/저장 로직이
     * `new_tag_names[]` 값을 검증한 뒤 처리한다.
     *
     * 추가되는 내부 형태:
     * [
     *     'id' => 'new-{hash}',
     *     'name' => '욕실정리',
     *     'isNew' => true,
     * ]
     */
    public function addNewTag(): void
    {
        // 사용자가 입력한 검색어를 저장 정책에 맞는 신규 태그명 후보로 정리한다.
        $tagName = $this->normalizeTagName($this->query);

        // UI에서 버튼을 숨겨도 Livewire 액션은 직접 호출될 수 있으므로 다시 검증한다.
        if (! $this->canCreateTagFromName($tagName)) {
            return;
        }

        // DB id가 없는 신규 후보를 선택 목록 안에서만 구분하기 위한 임시 id다.
        $temporaryId = 'new-'.md5(mb_strtolower($tagName));

        $this->selectedTags[] = [
            'id' => $temporaryId,
            'name' => $tagName,
            'isNew' => true,
        ];

        $this->closeSearch();
        $this->syncValue();
    }

    /**
     * 부모 Livewire 컴포넌트와 동기화할 값을 갱신한다.
     *
     * 기본 `ids` 모드는 일반 폼에서 기존 태그 id만 부모에 넘긴다.
     * `names` 모드는 AI 모달처럼 기존/신규 태그를 모두 이름 기준으로 다루는 화면에서
     * 선택된 모든 태그명을 부모에 넘긴다.
     *
     * `ids` 예: [1, 2]
     * `names` 예: ['청소', '욕실정리']
     */
    private function syncValue(): void
    {
        $this->value = $this->valueMode === 'names'
            ? $this->selectedTagNames()
            : $this->selectedTagIds();
    }

    // 선택된 태그를 제거
    public function removeTag(int|string $tagId): void
    {
        $this->selectedTags = collect($this->selectedTags)
            ->reject(fn (array $tag): bool => (string) $tag['id'] === (string) $tagId) // 제거하려는 id와 같은 태그를 제외.
            ->values() // 배열 인덱스를 0부터 다시 정렬
            ->all(); // Collection을 다시 일반 php 배열로 반환
        $this->syncValue();
    }

    public function render(TagSearchService $tagSearchService): View
    {
        // 현재 상태에 맞는 검색 결과 조회
        $results = $this->results($tagSearchService);

        return view('livewire.tags.tag-selector', [
            'resultItems' => $this->resultItems($results),
            'resultTitle' => $this->resultTitle(),
            'resultMeta' => $this->resultMeta($results),
            'resultMessage' => $this->resultMessage($results),
            'canCreateTag' => $this->canCreateTag($results),
            'creatableTagName' => $this->normalizeTagName($this->query),
        ]);
    }

    // 실제 검색 결과를 반환하는 내부 메서드
    private function results(TagSearchService $tagSearchService): Collection
    {
        if (! $this->hasSearched) {
            return new Collection;
        }

        return $tagSearchService->search($this->query);
    }

    // 검색 결과 영역의 제목 문구 생성
    private function resultTitle(): string
    {
        return $this->query !== '' ? "\"{$this->query}\" 검색 결과" : '검색 결과';
    }

    // 검색 결과 오른쪽에 표시할 보조 정보 생성
    private function resultMeta(Collection $results): string
    {
        // 아직 검색하지 않은 상태
        if (! $this->hasSearched) {
            return 'Enter로 검색';
        }
        // 검색어가 너무 짧은 상태
        if (mb_strlen($this->query) < 2) {
            return '검색어 부족';
        }

        // 정상 검색 상태에서는 결과 개수 표시.
        return $results->count().'개 결과';
    }

    // 결과가 없거나 검색 조건이 부족할 때 표시할 안내 문구 생성.
    private function resultMessage(Collection $results): string
    {
        // 검색 전 안내
        if (! $this->hasSearched) {
            return '태그 이름을 입력하고 Enter를 눌러 검색하세요.';
        }

        // 검색어 길이 부족 안내.
        if (mb_strlen($this->query) < 2) {
            return '2글자 이상 입력해 주세요.';
        }

        return $results->isEmpty()
            ? '일치하는 태그가 없습니다.'
            : '';
    }

    /**
     * 검색 결과 Tag 모델을 Blade 표시 전용 배열로 변환한다.
     *
     * Blade 안에서 선택 여부나 disabled 여부를 계산하지 않도록, 화면에 필요한
     * 상태를 여기서 미리 만들어 넘긴다.
     *
     * 최종 반환 형태:
     * [
     *     ['id' => 1, 'name' => '청소', 'isSelected' => false, 'isDisabled' => false],
     * ]
     *
     * @param  Collection<int, Tag>  $results
     * @return array<int, array{id: int, name: string, isSelected: bool, isDisabled: bool}>
     */
    private function resultItems(Collection $results): array
    {
        return $results
            ->map(function (Tag $tag): array {
                $isSelected = $this->isSelected($tag->id);

                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'isSelected' => $isSelected,
                    'isDisabled' => $isSelected || $this->isMaxReached(),
                ];
            })
            ->all();
    }

    /**
     * 현재 검색어로 신규 태그 추가 CTA를 보여줄 수 있는지 확인한다.
     *
     * 이 메서드는 화면 표시 여부만 판단한다. 실제 `addNewTag()` 액션에서도 같은
     * 조건을 서버 쿼리 기준으로 다시 검사하므로, UI 표시 조건과 상태 변경 조건이
     * 분리되어 있어도 요청 조작에 안전하게 대응할 수 있다.
     *
     * @param  Collection<int, Tag>  $results  현재 검색 결과 목록
     * @return bool 신규 태그 추가 CTA를 보여줄 수 있으면 true
     */
    private function canCreateTag(Collection $results): bool
    {
        $normalizedQuery = $this->normalizeTagName($this->query);

        return $normalizedQuery !== ''
            && mb_strlen($normalizedQuery) >= 2
            && ! $this->isMaxReached()
            && ! $this->resultsContainName($results, $normalizedQuery)
            && ! $this->selectedTagsContainName($normalizedQuery);
    }

    /**
     * 입력된 태그명을 신규 태그 후보로 추가할 수 있는지 확인한다.
     *
     * 허용 조건:
     * - 앞뒤 공백을 제거한 이름이 비어 있지 않다.
     * - 이름 길이가 2글자 이상이다.
     * - 최대 선택 개수에 도달하지 않았다.
     * - 활성 태그 중 같은 이름의 기존 태그가 없다.
     * - 이미 선택된 태그 중 같은 이름의 태그가 없다.
     *
     * @param  string  $name  사용자가 입력한 신규 태그명 후보
     * @return bool 신규 태그 후보로 선택 목록에 추가할 수 있으면 true
     */
    private function canCreateTagFromName(string $name): bool
    {
        $normalizedName = $this->normalizeTagName($name);
        $normalizedNameForComparison = mb_strtolower($normalizedName);

        return $normalizedName !== ''
            && mb_strlen($normalizedName) >= 2
            && ! $this->isMaxReached()
            && ! Tag::query()
                ->where('is_active', true)
                ->whereRaw('LOWER(name) = ?', [$normalizedNameForComparison])
                ->exists()
            && ! $this->selectedTagsContainName($normalizedNameForComparison);
    }

    /**
     * 외부에서 들어온 태그명을 저장 전 표준 형태로 변환한다.
     *
     * 처리 규칙:
     * - 앞뒤 공백을 제거한다.
     * - 사용자가 붙였을 수 있는 앞쪽 # 기호를 제거한다.
     * - 태그명에는 공백을 허용하지 않으므로 모든 공백 문자를 제거한다.
     *
     * @return string 정규화된 태그명
     */
    private function normalizeTagName(string $tagName): string
    {
        $tagName = trim($tagName);
        $tagName = ltrim($tagName, '#');

        return preg_replace('/\s+/u', '', $tagName) ?? $tagName;
    }

    /**
     * 현재 검색 결과 안에 입력값과 같은 이름의 태그가 있는지 확인한다.
     *
     * 검색 결과에 같은 이름이 있으면 사용자는 기존 태그를 선택하면 되므로
     * 신규 태그 추가 CTA를 숨긴다.
     *
     * @param  Collection<int, Tag>  $results  현재 검색 결과 목록
     * @param  string  $normalizedName  비교용으로 정규화한 태그명
     * @return bool 검색 결과에 같은 이름이 있으면 true
     */
    private function resultsContainName(Collection $results, string $normalizedName): bool
    {
        $normalizedNameForComparison = mb_strtolower($normalizedName);

        return $results->contains(
            fn (Tag $tag): bool => mb_strtolower($this->normalizeTagName($tag->name)) === $normalizedNameForComparison
        );
    }

    /**
     * 이미 선택된 태그 안에 입력값과 같은 이름이 있는지 확인한다.
     *
     * 기존 태그와 신규 태그 후보를 모두 같은 선택 목록에서 관리하므로,
     * 이름 기준 중복을 여기서 한 번에 막는다.
     *
     * @param  string  $normalizedName  비교용으로 정규화한 태그명
     * @return bool 선택된 태그 목록에 같은 이름이 있으면 true
     */
    private function selectedTagsContainName(string $normalizedName): bool
    {
        $normalizedNameForComparison = mb_strtolower($normalizedName);

        return collect($this->selectedTags)
            ->contains(fn (array $tag): bool => mb_strtolower($this->normalizeTagName($tag['name'])) === $normalizedNameForComparison);
    }

    // 특정 태그 id가 이미 선택되어 있는지 확인
    private function isSelected(int|string $tagId): bool
    {
        return collect($this->selectedTags)
            ->contains(fn (array $tag): bool => (string) $tag['id'] === (string) $tagId);
    }

    // 선택된 태그 개수가 최대 개수에 도달했는지 확인
    private function isMaxReached(): bool
    {
        return $this->maxCount !== null
            && count($this->selectedTags) >= $this->maxCount;
    }

    /**
     * 선택된 기존 태그 id만 반환한다.
     *
     * @return array<int, int>
     */
    private function selectedTagIds(): array
    {
        return collect($this->selectedTags)
            ->reject(fn (array $tag): bool => $tag['isNew'] ?? false)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * 선택된 기존/신규 태그명을 모두 반환한다.
     *
     * @return array<int, string>
     */
    private function selectedTagNames(): array
    {
        return collect($this->selectedTags)
            ->map(fn (array $tag): string => $this->normalizeTagName($tag['name']))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * 외부에서 들어온 초기 선택 태그 목록을 내부 표준 형태로 변환한다.
     *
     * 허용하는 입력 형태 :
     * - 문자열: '청소'
     * - Tag모델 : Tag{id:1, name:'청소'}
     * - 배열 : ['id'=>1, 'name'=>'청소', 'isNew'=>true]
     *
     * 최종 반환 형태 :
     * [
     *      ['id' => 1, 'name' => '청소', 'isNew' => false],
     * ]
     *
     * @param  iterable<int, mixed>  $tags
     * @return array<int, array{id: int|string, name: string, isNew?: bool}>
     */
    private function normalizeTags(iterable $tags): array
    {
        return SupportCollection::make($tags)
            ->map(function (mixed $tag, int $index): array {
                if (is_string($tag)) {
                    return [
                        'id' => "tag-{$index}-{$tag}",
                        'name' => $tag,
                        'isNew' => true,
                    ];
                }

                if ($tag instanceof Tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'isNew' => false,
                    ];
                }

                return [
                    'id' => $tag['id'] ?? "tag-{$index}-{$tag['name']}",
                    'name' => $tag['name'],
                    'isNew' => $tag['isNew'] ?? false,
                ];
            })
            ->values()
            ->all();
    }
}
