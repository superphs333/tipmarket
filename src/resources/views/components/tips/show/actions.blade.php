@php
    $actions = [
        ['type' => 'button', 'label' => '북마크', 'icon' => '□', 'count' => '13', 'aria' => '북마크'],
        ['type' => 'button', 'label' => '좋아요', 'icon' => '♡', 'count' => '25', 'aria' => '좋아요'],
        ['type' => 'button', 'label' => '공유', 'icon' => '↗', 'count' => null, 'aria' => '공유'],
        ['type' => 'link', 'label' => '댓글', 'icon' => '□', 'count' => null, 'aria' => '댓글', 'href' => '#tip-comments'],
    ];
@endphp

<aside class="tip-show__action tip-show__action--desktop tip-show__desktop-only">
    <div class="tip-show__action-sticky">
        @foreach ($actions as $action)
            @if ($action['type'] === 'link')
                <a href="{{ $action['href'] }}" class="tip-show__action-btn" aria-label="{{ $action['aria'] }}">
                    <span class="tip-show__action-icon" aria-hidden="true">{{ $action['icon'] }}</span>
                    <span class="tip-show__action-label">{{ $action['label'] }}</span>
                </a>
            @else
                <button type="button" class="tip-show__action-btn" aria-label="{{ $action['aria'] }}">
                    <span class="tip-show__action-icon" aria-hidden="true">{{ $action['icon'] }}</span>
                    <span class="tip-show__action-label">{{ $action['label'] }}</span>
                    @if ($action['count'])
                        <span class="tip-show__action-count">{{ $action['count'] }}</span>
                    @endif
                </button>
            @endif
        @endforeach
    </div>
</aside>

<aside class="tip-show__action tip-show__action--mobile tip-show__mobile-only" aria-label="모바일 액션">
    <div class="tip-show__action-sticky">
        @foreach ($actions as $action)
            @if ($action['type'] === 'link')
                <a href="{{ $action['href'] }}" class="tip-show__action-btn" aria-label="{{ $action['aria'] }}">
                    <span class="tip-show__action-icon" aria-hidden="true">{{ $action['icon'] }}</span>
                    <span class="tip-show__action-label">{{ $action['label'] }}</span>
                </a>
            @else
                <button type="button" class="tip-show__action-btn" aria-label="{{ $action['aria'] }}">
                    <span class="tip-show__action-icon" aria-hidden="true">{{ $action['icon'] }}</span>
                    <span class="tip-show__action-label">{{ $action['label'] }}</span>
                    @if ($action['count'])
                        <span class="tip-show__action-count">{{ $action['count'] }}</span>
                    @endif
                </button>
            @endif
        @endforeach
    </div>
</aside>
