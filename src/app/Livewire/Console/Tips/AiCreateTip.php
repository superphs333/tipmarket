<?php

namespace App\Livewire\Console\Tips;

use App\Concerns\TaxonomyValidationRules;
use App\Jobs\GenerateAiTipsJob;
use App\Models\AiTipGenerationRequest;
use App\Models\Category;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * 콘솔 팁 관리 화면의 AI로 팁 추가 모달 상태와 생성 요청 접수를 조율
 */
class AiCreateTip extends Component
{
    use TaxonomyValidationRules;

    public ?int $categoryId = null;

    public string $prompt = '';

    public int $count = 1;

    public int $tagSelectorKey = 0;

    /**
     * 사용자가 모달에서 선택한 기존/신규 태그명 목록.
     *
     * @var array<int, string>
     */
    public array $tagNames = [];

    /**
     * AI 팁 생성 모달의 입력값을 검증한다.
     */
    protected function rules(): array
    {
        return [
            'categoryId' => $this->nullableActiveCategoryIdRules(),
            'prompt' => ['nullable', 'string', 'max:2000'],
            'count' => ['required', 'integer', 'min:1', 'max:20'],
            'tagNames' => ['array', 'max:20'],
            'tagNames.*' => ['string', 'min:2', 'max:50'],
        ];
    }

    public function generate(): void
    {
        $validated = $this->validate();
        $author = Auth::user();

        if ($author === null) {
            abort(403);
        }

        $requiredTagNames = $this->normalizeTagNames($validated['tagNames'] ?? []);

        // job이 나중에 참고할 생성 요청 정보 저장
        $generationRequest = AiTipGenerationRequest::create([
            'user_id' => $author->id,
            'category_id' => $validated['categoryId'],
            'status' => 'pending',
            'prompt' => $validated['prompt'] ?? '',
            'tag_names' => $requiredTagNames,
            'requested_count' => $validated['count'],
            'created_count' => 0,
            'failed_count' => 0,
        ]);

        GenerateAiTipsJob::dispatch(
            $generationRequest->id,
            $generationRequest->requested_count,
        );

        Flux::toast(
            variant : 'success',
            text : 'AI 팁 생성을 시작했습니다. 완료되면 이 화면에서 확인할 수 있습니다.'
        );

        Flux::modal('ai-tip-create')->close();

        // 같은 모달에서 연속 생성할 때 이전 입력이 남지 않도록 초기화
        $this->reset(['categoryId', 'prompt', 'tagNames']);
        $this->count = 1;
        $this->tagSelectorKey++;
    }

    public function render(): View
    {
        return view('livewire.console.tips.ai-create-tip', [
            'categories' => Category::query()->forSelect()->get(),
        ]);
    }

    /**
     * 프롬프트와 저장 로직에 넘길 태그명을 표준 형태로 정리한다.
     *
     * @param  array<int, string>  $tagNames
     * @return array<int, string>
     */
    private function normalizeTagNames(array $tagNames): array
    {
        return collect($tagNames)
            ->map(function (string $tagName): string {
                $tagName = trim($tagName);
                $tagName = ltrim($tagName, '#');

                return preg_replace('/\s+/u', '', $tagName) ?? $tagName;
            })
            ->filter(fn (string $tagName): bool => mb_strlen($tagName) >= 2 && mb_strlen($tagName) <= 50)
            ->unique()
            ->values()
            ->all();
    }
}
