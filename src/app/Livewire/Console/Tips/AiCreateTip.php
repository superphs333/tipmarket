<?php

namespace App\Livewire\Console\Tips;

use App\Actions\Tips\CreateAiGeneratedTips;
use App\Concerns\TaxonomyValidationRules;
use App\Models\Category;
use App\Services\Ai\Tip\BuildTipGenerationPrompt;
use App\Services\Ai\Tip\GenerateTipsFromPrompt;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;

/**
 * 콘솔 팁 관리 화면의 "AI로 팁 추가" 모달 상태와 생성 흐름을 조율한다.
 *
 * 사용자가 모달에서 입력한 값을 검증하고,
 * AI 팁 생성에 필요한 여러 전담 클래스를 순서대로 호출한다.
 *
 * 로직]
 * - 모달 입력 상태를 관리
 * - categoryId, tagNames, prompt, count 입력값을 검증한다.
 * - 선택된 categoryId, tagNames를 AI 프롬프트에 넣을 값으로 정리한다.
 * - BuildTipGenerationPrompt를 호출해 AI 요청 문장을 만든다.
 * - GenerateTipsFromPrompt를 호출해 TipDraftData[]를 생성한다.
 * - CreateAiGeneratedTips를 호출해 생성된 초안을 draft 팁으로 저장한다.
 * - 완료 후 화면에 결과 메시지를 표시한다.
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

    public function generate(
        BuildTipGenerationPrompt $buildPrompt,
        GenerateTipsFromPrompt $generateTips,
        CreateAiGeneratedTips $createTips,
    ): void {
        $validated = $this->validate();
        $author = Auth::user();

        if ($author === null) {
            abort(403);
        }

        $categoryName = Category::query()
            ->whereKey($validated['categoryId'])
            ->value('name');

        $requiredTagNames = $this->normalizeTagNames($validated['tagNames'] ?? []);

        $prompt = $buildPrompt(
            prompt: $validated['prompt'] ?? '',
            count: $validated['count'],
            categoryName: $categoryName,
            tagNames: $requiredTagNames,
        );

        try {
            $drafts = $generateTips(
                prompt: $prompt,
                categoryId: $validated['categoryId'],
                requiredTagNames: $requiredTagNames,
            );
        } catch (Throwable $exception) {
            Log::warning('AI tip generation failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            $this->addError('aiGeneration', 'AI 생성에 실패했습니다. 잠시 후 다시 시도해 주세요.');

            return;
        }

        $tips = $createTips(
            author: $author,
            drafts: $drafts,
        );

        // 저장된 Tip 모델 개수를 기준으로 사용자에게 결과를 알려준다.
        session()->flash('status', count($tips).'개 추가되었습니다.');

        // 같은 모달에서 연속 생성할 때 이전 입력이 남지 않도록 초기화한다.
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
