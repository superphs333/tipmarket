@props(['variant' => 'desktop'])

@php
    $isMobile = $variant === 'mobile';
@endphp

<aside
    class="tip-show__action {{ $isMobile ? 'tip-show__action--mobile tip-show__mobile-only' : 'tip-show__action--desktop tip-show__desktop-only' }}"
    @if ($isMobile) aria-label="모바일 액션" @endif
>
    <x-tips.show.action-buttons />
</aside>
