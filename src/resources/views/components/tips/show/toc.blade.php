@props(['variant' => 'desktop'])

@php
    $isMobile = $variant === 'mobile';
@endphp

<{{ $isMobile ? 'section' : 'aside' }}
    class="{{ $isMobile ? 'tip-show__toc-mobile tip-show__mobile-only' : 'tip-show__toc tip-show__desktop-only' }}"
    {{ $isMobile ? 'data-toc-mobile' : 'data-toc-desktop' }}
>
    @if ($isMobile)
        <button type="button" class="tip-show__toc-toggle tip-show__toc-toggle--mobile" aria-expanded="false">
            목차 ▾
        </button>
    @else
        <div class="tip-show__toc-header">
            <strong>TOC</strong>
            <button type="button" class="tip-show__toc-toggle" aria-expanded="false">펼치기</button>
        </div>
    @endif

    <nav class="tip-show__toc-panel" aria-label="{{ $isMobile ? '모바일 목차' : '목차' }}">
        <x-tips.show.toc-items />
    </nav>
</{{ $isMobile ? 'section' : 'aside' }}>
