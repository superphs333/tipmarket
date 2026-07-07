@props(['tags'])

<hr class="tip-show__divider">

<section id="tip-tags" class="tip-show__section">
    <h2 class="tip-show__section-title">태그</h2>

    <div class="tip-show__tags">
        @forelse ($tags as $tag)
            <a href="#">
                <span class="tip-show__tag">
                    #{{ $tag->name }}
                </span>
            </a>
        @empty
            <span class="tip-show__empty-text">
                등록된 태그가 없습니다.
            </span>
        @endforelse
    </div>
</section>
