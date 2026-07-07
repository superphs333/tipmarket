<?php

namespace App\View\Components\Layouts;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Front extends Component
{
    public function __construct(
        public ?string $title = null,
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.layouts.front');
    }
}
