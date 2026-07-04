<?php

namespace App\View\Components\Tips;

use App\Models\Category;
use App\Models\Tip;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class EditForm extends Component
{
    public Collection $categories;

    public function __construct(
        public Tip $tip,
        null|array|Collection|EloquentCollection $categories = null,
    ) {
        $this->tip->loadMissing('tags');

        $this->categories = $categories !== null
            ? collect($categories)
            : Category::query()->forSelect()->get();
    }

    public function render(): View
    {
        return view('components.tips.edit-form');
    }
}
