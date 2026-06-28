<?php

namespace App\Livewire\Tags;

use App\Models\Tag;
use App\Services\Tags\TagSearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Component;


class TagSelector extends Component
{
    // 화면에 표시할 레벨
    public string $label = '태그';
    // 검색 input의 placeholder 문구 
    public string $placeholder = '태그 이름 검색...';
    // form submit 시 hidden input의 name값 
    public string $name = 'tag_ids';
    // 선택 가능한 최대 태그 개수
    public int $maxCount = 5;
    // 사용자가 입력한 검색어 
    public string $query = '';
    // 검색을 단 한번이라도 실행했는지 여부 (true => 검색 결과 드롭다운이 열림.)
    public bool $hasSearched = false;

    /**
     * 선택된 태그 목록 
     * 
     * * 예:
     * [
     *     ['id' => 1, 'name' => '청소'],
     *     ['id' => 2, 'name' => '수납'],
     * ]
     * 
     * @var array<int, array{id: int|string, name: string}>
     */
    public array $selectedTags = [];

    /**
     * Livewire 컴포넌트가 처음 생성될 때 실행되는 초기화 메서드. 
     * 
     * @param  iterable<int, mixed>  $selected
     */
    public function mount(
        string $label = '태그',
        string $placeholder = '태그 이름 검색...',
        string $name = 'tag_ids',
        int $maxCount = 5,
        iterable $selected = [],
    ): void {
        $this->label = $label;
        $this->placeholder = $placeholder;
        $this->name = $name;
        $this->maxCount = $maxCount;
        $this->selectedTags = $this->normalizeTags($selected); // 내부에서 쓰기 좋은 배열 형태로 통일. 
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
    }

    // 선택된 태그를 제거 
    public function removeTag(int|string $tagId): void
    {
        $this->selectedTags = collect($this->selectedTags)
            ->reject(fn (array $tag): bool => (string) $tag['id'] === (string) $tagId) // 제거하려는 id와 같은 태그를 제외.
            ->values() // 배열 인덱스를 0부터 다시 정렬
            ->all(); // Collection을 다시 일반 php 배열로 반환
    }


    public function render(TagSearchService $tagSearchService): View
    {
        // 현재 상태에 맞는 검색 결과 조회
        $results = $this->results($tagSearchService);

        return view('livewire.tags.tag-selector', [
            'results' => $results,
            'resultTitle' => $this->resultTitle(),
            'resultMeta' => $this->resultMeta($results),
            'resultMessage' => $this->resultMessage($results),
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

    // 특정 태그 id가 이미 선택되어 있는지 확인
    private function isSelected(int|string $tagId): bool
    {
        return collect($this->selectedTags)
            ->contains(fn (array $tag): bool => (string) $tag['id'] === (string) $tagId);
    }

    // 선택된 태그 개수가 최대 개수에 도달했는지 확인
    private function isMaxReached(): bool
    {
        return count($this->selectedTags) >= $this->maxCount;
    }

    /**
     * 외부에서 들어온 초기 선택 태그 목로긍ㄹ 내부 표준 형태로 변환 
     * 
     * 허용하는 입력 형태 :
     * - 문자열: '청소'
     * - Tag모델 : Tag{id:1, name:'청소'}
     * - 배열 : ['id'=>1, 'name'=>'청소']
     * 
     * 최종 반환 형태 : 
     * [
     *      ['id' => 1, 'name' => '청소'],
     * ]
     * 
     * @param  iterable<int, mixed>  $tags
     * @return array<int, array{id: int|string, name: string}>
     */
    private function normalizeTags(iterable $tags): array
    {
        return SupportCollection::make($tags)
            ->map(function (mixed $tag, int $index): array {
                if (is_string($tag)) {
                    return [
                        'id' => "tag-{$index}-{$tag}",
                        'name' => $tag,
                    ];
                }

                if ($tag instanceof Tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ];
                }

                return [
                    'id' => $tag['id'] ?? "tag-{$index}-{$tag['name']}",
                    'name' => $tag['name'],
                ];
            })
            ->values()
            ->all();
    }
}
