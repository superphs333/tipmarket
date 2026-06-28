<?php

namespace App\Livewire\Console\Tips;

use App\Concerns\TaxonomyValidationRules;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AiCreateTip extends Component
{
    use TaxonomyValidationRules;

    public ?int $categoryId = null;

    public string $prompt = '';

    public int $count = 1;

    public array $tagIds = [];


    /**
     * AI 팁 생성 모달의 입력값을 검증 
     * 
     */
    protected function rules(): array
    {
        return [
            'categoryId' => $this->nullableActiveCategoryIdRules(),
            'prompt' => ['required', 'string', 'min:10', 'max:2000'],
            'count' => ['required', 'integer', 'min:1', 'max:20'],
            'tagIds' => $this->tagIdsRules(),
            'tagIds.*' => $this->activeTagIdRules(),
        ];
    }

    public function generate(): void
    {
        $this->validate();
    }

    public function render(): View
    {
        return view('livewire.console.tips.ai-create-tip', [
            'categories' => Category::query()->forSelect()->get(),
        ]);
    }
}
