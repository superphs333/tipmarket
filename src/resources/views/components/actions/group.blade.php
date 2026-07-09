{{-- 
    [액션 묶음 렌더링 컴포넌트]
    => 여러 화면에서 반복되는 액션 목록을 같은 규칙으로 출력하기 위한 공통 진입점
    : 목록을 반복하고 배치 class를 제공만 함, 액션 하나의 실제 html은 x-actions.item이 담당
    ex) 팁 상세 우측 액션바, 모바일 하단 액션바, 카드 하단의 좋아요/댓글/북마크 묶음이
    이 컴포넌트를 통해 렌더링 됨
    - 화면마다 좋아요/북마크/댓글/공유 버튼 html을 반복 작성하지 않게 함
    - 어떤 액션을 보여줄지와 액션 하나를 어떻게 렌더링할지를 분리
    - 데스크톱/모바일/카드/목록처럼 배치가 달라도 같은 actions 데이터를 재사용하게 함. 

    입력)
        - actions : 렌더링할 액션 정의 배열
            ex) 좋아요, 북마크, 댓글, 공유
        - variant : 같은 액션 목록을 어떤 배치로 보여줄지 결정
            ex) responsive, sidebar, compact, pill, inline
        - label : 스크린리더가 액션 묶음을 식별할 수 있게 하는 접근성 이름
    출력)
        - actions-group actions-group--{variant} class를 가진 wrapper div
        - visible=false가 아닌 action마다 x-actions.item 하나
        - actions가 비어 있으면 slot fallback

 --}}
@props([
    // 렌더링할 액션 정의 배열
    'actions' => [],
    // 액션 묶음의 표시 형태
    'variant' => 'inline',
    // 액션 묶음에 부여할 접근성 라벨 
    'label' => null,
])

@php
    /**
     * 컴포넌트 기본 class와 외부에서 전달한 class를 합친다. 
     * 
     * ex)
     *  <x-actions.group variant="responsive" class="tip-show__actions" />
     * => class="actions-group actions-group--responsive tip-show__actions"
    */ 
    $groupAttributes = $attributes
        ->class([
            'actions-group',
            "actions-group--{$variant}",
        ])
        ->merge(['role' => 'group']); // [??] 이건 무슨 기능이지

    if ($label !== null) {
        $groupAttributes = $groupAttributes->merge(['aria-label' => $label]);
    }

    // visible=false인 액션은 렌더링 하지 않음
    $visibleActions = collect($actions)
        ->filter(fn (array $action):bool => $action['visible'] ?? true);
@endphp

<div {{ $groupAttributes }}>
    {{-- 액션 하나의 실제 HTML은 => x-actions.item이 담당 --}}
    @forelse ($visibleActions as $action)
        <x-actions.item :action="$action" :variant="$variant" />
    @empty
        {{ $slot }}
    @endforelse
</div>
