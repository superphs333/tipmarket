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
        public string $mode = 'edit',
        // 저장/수정 라우트는 콘솔과 프론트 화면마다 달라질 수 있다.
        // 공유 폼이 특정 route name을 알게 되면 재사용성이 떨어지므로 부모 화면에서 주입한다.
        public ?string $action = null,
        // 취소 이동 경로도 콘솔 목록, 프론트 상세, 이전 페이지 등 화면 맥락마다 다르다.
        // 폼 컴포넌트는 링크를 렌더링만 하고, 실제 목적지는 호출한 화면이 결정한다.
        public ?string $cancelUrl = null,
        // HTML form은 GET/POST만 직접 전송한다.
        // PUT/PATCH 같은 수정 요청은 Blade에서 Laravel method spoofing으로 표현한다.
        public string $method = 'POST',
        null|array|Collection|EloquentCollection $categories = null,
    ) {
        if ($this->tip->exists) {
            $this->tip->loadMissing('tags');
        } else {
            $this->tip->setRelation('tags', new EloquentCollection);
        }

        $this->categories = $categories !== null
            ? collect($categories)
            : Category::query()->forSelect()->get();
    }

    public function render(): View
    {
        return view('components.tips.edit-form');
    }
}
