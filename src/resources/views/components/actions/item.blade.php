{{--
    [액션 항목 컴포넌트]
    : actions 배열에 들어 있는 액션 하나를 실제 클릭 가능한 UI로 렌더링

    입력)
        - action : 액션 하나의 정의 배열
            ex) ['name' => 'like', 'label' => '좋아요', 'count' => 10]
        - variant : 부모 x-actions.group에서 전달한 배치 형태
            ex) responsive, sidebar, compact, pill, inline
    출력)
        - action값에 따라 a 또는 button 중 하나
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
     * - href: 링크 URL
     * - endpoint: JS fetch 요청 URL
     * - method: JS fetch HTTP method
     * - behavior: JS 전용 동작 이름. 예: toggle, share
     * - url: JS 동작에 사용할 URL. 예: 공유 URL
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
    $name = $action['name'] ?? $action['key'] ?? '';

    /**
     * 화면에 표시할 기본 값들
     */
    $label = $action['label'] ?? null;
    $count = $action['count'] ?? null;
    $active = $action['active'] ?? false;
    $disabled = $action['disabled'] ?? false;

    /**
     * 동작 방식에 필요한 값
     * - href 있음 => 링크
     * - 그 외 => button
     * - endpoint 있음 => button 클릭 시 JS가 fetch 요청
     */
    $href = $action['href'] ?? null;
    $endpoint = $action['endpoint'] ?? $action['action'] ?? null;
    $method = strtoupper($action['method'] ?? 'POST');

    /**
     * JS 동작에 필요한 값
     */
    $behavior = $action['behavior'] ?? null;
    $url = $action['url'] ?? null;

    /**
     * aria-pressed를 붙일 수 있는 토글형 액션인지 판단
     */
    $pressable = $action['pressable'] ?? in_array($name, ['bookmark', 'like'], true);

    /**
     * 스크린리더용 액션 이름
     */
    $ariaLabel = $action['aria'] ?? ($active && $label ? "{$label} 취소" : $label);

    /**
     * 모든 렌더링 형태에서 공유하는 class 목록
     */
    $classes = [
        'actions-item',
        "actions-item--{$name}",
        "actions-item--{$variant}",
        'is-active' => $active,
        'is-disabled' => $disabled,
    ];
@endphp

@if ($href && ! $disabled)
    {{--
        링크 액션
        ex) 댓글 섹션 이동, 상세 페이지로 이동
    --}}
    <a
        href="{{ $href }}"
        @class($classes)
        aria-label="{{ $ariaLabel }}"
        data-action-name="{{ $name }}"
        @if ($behavior) data-action-behavior="{{ $behavior }}" @endif
        @if ($url) data-action-url="{{ $url }}" @endif
    >
        <x-actions.icon :name="$name" :active="$active" />

        @if ($label)
            <span class="actions-item__label" data-action-label>{{ $label }}</span>
        @endif

        @if ($count !== null)
            <span class="actions-item__count" data-action-count>{{ $count }}</span>
        @endif
    </a>
@else
    {{--
        JS 전용 button 액션
        ex) 좋아요 토글, 북마크 토글, 공유
    --}}
    <button
        type="button"
        @class($classes)
        @disabled($disabled)
        aria-label="{{ $ariaLabel }}"
        data-action-name="{{ $name }}"
        @if ($behavior) data-action-behavior="{{ $behavior }}" @endif
        @if ($endpoint) data-action-endpoint="{{ $endpoint }}" @endif
        @if ($method) data-action-method="{{ $method }}" @endif
        @if ($url) data-action-url="{{ $url }}" @endif
        @if ($pressable) aria-pressed="{{ $active ? 'true' : 'false' }}" @endif
    >
        <x-actions.icon :name="$name" :active="$active" />

        @if ($label)
            <span class="actions-item__label" data-action-label>{{ $label }}</span>
        @endif

        @if ($count !== null)
            <span class="actions-item__count" data-action-count>{{ $count }}</span>
        @endif
    </button>
@endif
