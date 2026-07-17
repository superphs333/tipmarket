<?php

namespace App\View\Components\Tips\Filters;

use App\Models\Tip;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

/**
 * Console과 Front에서 공통으로 사용하는 Tip 검색 필터 폼 컴포넌트.
 *
 * 검색 상태와 실행은 각 화면이 담당하고,
 * 이 컴포넌트는 필터 UI에 필요한 화면 구분값과 선택 옵션을 전달한다.
 */
class Form extends Component
{
    
    public Collection $categories; // 화면에서 반복 출력할 카테고리 목록
    public array $statusOptions; // tip 상태 선택 목록 
    public array $audienceOptions; // tip 노출 범위 선택 목록.
    

    public function __construct(
        public string $context = 'console', // 필터를 사용하는 화면 (console | front)
        iterable  $categories = [], // 카테고리 선택 목록
    ) {
        $this->categories = collect($categories);
        $this->statusOptions = Tip::statusOptions();
        $this->audienceOptions = Tip::audienceOptions();
    }

    /**
     * Tip 검색 필터 폼 View를 반환한다.
     */
    public function render(): View|Closure|string
    {
        return view('components.tips.filters.form');
    }
}
