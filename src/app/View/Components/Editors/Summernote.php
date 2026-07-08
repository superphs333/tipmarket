<?php

namespace App\View\Components\Editors;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Override;

class Summernote extends Component
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $value = null,
        public string $placeholder = '본문을 입력하세요.',
        public int $height = 420,
    ) {}

    #[Override]
    public function render(): View|Closure|string
    {
        return view('components.editors.summernote');
    }
}
