<?php

namespace App\Livewire\Console\Tips;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AiCreateTip extends Component
{
    public ?int $categoryId = null;

    public string $prompt = '';

    public int $count = 1;

    public array $tagIds = [];

    public function generate(): void
    {
        $this->validate([
            'categoryId' => ['nullable', 'integer', 'exists:categories,id'],
            'prompt' => ['required', 'string', 'min:10', 'max:2000'],
            'count' => ['required', 'integer', 'min:1', 'max:20'],
            'tagIds' => ['array'],
            'tagIds.*' => ['integer', 'exists:tags,id'],
        ]);
    }

    public function render(): View
    {
        return view('livewire.console.tips.ai-create-tip', [
            'categories' => Category::query()->forSelect()->get(),
        ]);
    }
}
