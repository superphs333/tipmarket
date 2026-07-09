{{-- 
    [액션 항목 컴포넌트]
    : actions 배열에 들어 있는 액션 하나를 실제 클릭 가능한 UI로 렌더링 

    입력)
        - action : 액션 하나의 정의 배열
            ex) ['name' => 'like', 'label' => '좋아요', 'count' => 10]
        - variant : 부모 x-actions.group에서 전달한 배치 형태
            ex) responsive, sidebar, compact, pill, inline
    출력)   
        - action값에 따라 form, a, button 중 하나
        - x-actions.icon으로 렌더링한 SVG 아이콘
        - label 텍스트
        - count 숫자
        - active/disabled 상태 class와 접근성 속성 
--}}
@props([
    /*
     * 렌더링할 액션 하나의 정의 배열.
     *
     * 주요 키:
     * - name: 액션 종류. icon과 class modifier 기준
     * - label: 화면에 표시할 텍스트
     * - count: 숫자 표시. null이면 출력하지 않음
     * - active: 채워진 아이콘/활성 상태 여부
     * - disabled: 보이지만 동작하지 않는 상태
     * - action: form 전송 URL
     * - method: form method
     * - href: 링크 URL
     * - aria: 직접 지정할 aria-label
     */
    'action',
    /*
     * 부모 group에서 전달되는 배치 형태.
     *
     * class modifier로 붙어서 화면별 스타일을 조정할 수 있다.
     * 예: actions-item--responsive, actions-item--compact
     */
    'variant' => 'inline',
])

@php
    $name = $action['name'] ?? $action['key'] ?? ''; // 액션의 핵심 식별자
    /**
     * 화면에 표시할 기본 값들
     */
    $label = $action['label'] ?? null;
    $count = $action['count'] ?? null;
    $active = $action['active'] ?? false;
    $disabled = $action['disabled'] ?? false;
    /**
     * 동작 방식에 필요한 값 
     * - formAction 있음 => form submit, 
     * - href 있음 => 링크
     * - 둘 다 없음 => button
     */ 
    $href = $action['href'] ?? null;
    $formAction = $action['action'] ?? null;
    $method = strtoupper($action['method'] ?? 'POST');
    /**
     * aria-pressed를 붙일 수 있는 토글형 액션인지 판단 
     * 
     * 기본값은 like/bookmark만 토글형으로 봄.
     * +) 필요하면 action 배열에서 pressable 값을 직접 넘겨 덮어쓸 수 있음.
     */ 
    $pressable = $action['pressable'] ?? in_array($name, ['bookmark', 'like'], true);
    /**
    * 스크린리더용 액션 이름
    * 
    * action['aria']가 있으면 그 값을 우선 사용, 
    * 없으면 active 상태일 때 "좋아요 취소"처럼 표현.
    */ 
    $ariaLabel = $action['aria'] ?? ($active && $label ? "{$label} 취소" : $label);

    /**
     * 모든 렌더링 형태에서 공유하는 class 목록
     * 
     * name과 variant를 modifer로 붙여 CSS에서 액션 종류/배치별 스타일을 나눌 수 있음
     */
    $classes = [
        'actions-item',
        "actions-item--{$name}",
        "actions-item--{$variant}",
        'is-active' => $active,
        'is-disabled' => $disabled,
    ];
@endphp

@if ($formAction && ! $disabled)
    {{-- 
    form submit 액션
        ex) 좋아요, 북마크, 신고
    --}}
    <form method="POST" action="{{ $formAction }}" class="actions-item__form">
        @csrf

        @if (! in_array($method, ['GET', 'POST'], true))
            @method($method)
        @endif

        <button
            type="submit"
            @class($classes)
            aria-label="{{ $ariaLabel }}"
            @if ($pressable) aria-pressed="{{ $active ? 'true' : 'false' }}" @endif
        >
            <x-actions.icon :name="$name" :active="$active" />
            @if ($label)
                <span class="actions-item__label">{{ $label }}</span>
            @endif
            @if ($count !== null)
                <span class="actions-item__count">{{ $count }}</span>
            @endif
        </button>
    </form>
@elseif ($href && ! $disabled)
    {{-- 
    링크 액션
        ex) 댓글 섹션 이동, 상세 페에지로 이동, 공유 대상 URL열기 
    --}}
    <a href="{{ $href }}" @class($classes) aria-label="{{ $ariaLabel }}">
        <x-actions.icon :name="$name" :active="$active" />
        @if ($label)
            <span class="actions-item__label">{{ $label }}</span>
        @endif
        @if ($count !== null)
            <span class="actions-item__count">{{ $count }}</span>
        @endif
    </a>
@else
    {{-- 
    일반 button 또는 disabled 표시 액션
    : action/href가 없는 공유 버튼처럼 js가 붙을 수 있는 액션, 
    또는 목록 카드에서 숫자만 보여주는 비활성 액션 
    --}}
    <button
        type="button"
        @class($classes)
        @disabled($disabled)
        aria-label="{{ $ariaLabel }}"
        @if ($pressable) aria-pressed="{{ $active ? 'true' : 'false' }}" @endif
    >
        <x-actions.icon :name="$name" :active="$active" />
        @if ($label)
            <span class="actions-item__label">{{ $label }}</span>
        @endif
        @if ($count !== null)
            <span class="actions-item__count">{{ $count }}</span>
        @endif
    </button>
@endif
