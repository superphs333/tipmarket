@php
    $tocItems = [
        ['href' => '#tip-content', 'label' => '본문'],
        ['href' => '#tip-tags', 'label' => '태그'],
        ['href' => '#tip-reactions', 'label' => '반응'],
        ['href' => '#tip-comments', 'label' => '댓글'],
    ];
@endphp

<aside class="tip-show__toc tip-show__desktop-only" data-toc-desktop>
    <div class="tip-show__toc-header">
        <strong>TOC</strong>
        <button type="button" class="tip-show__toc-toggle" aria-expanded="false">펼치기</button>
    </div>

    <nav class="tip-show__toc-panel" aria-label="목차">
        <ol class="tip-show__toc-list">
            @foreach ($tocItems as $item)
                <li><a href="{{ $item['href'] }}">{{ $item['label'] }}</a></li>
            @endforeach
        </ol>
    </nav>
</aside>

<section class="tip-show__toc-mobile tip-show__mobile-only" data-toc-mobile>
    <button type="button" class="tip-show__toc-toggle tip-show__toc-toggle--mobile" aria-expanded="false">
        목차 ▾
    </button>

    <nav class="tip-show__toc-panel" aria-label="모바일 목차">
        <ol class="tip-show__toc-list">
            @foreach ($tocItems as $item)
                <li><a href="{{ $item['href'] }}">{{ $item['label'] }}</a></li>
            @endforeach
        </ol>
    </nav>
</section>
