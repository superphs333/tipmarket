<div
    class="tip-show__comment-list-fragment"
    data-comment-list-fragment
    data-comment-count-value="{{ $tip->comment_count }}"
>
    <ul class="tip-show__comment-list" aria-live="polite">
        @forelse ($comments as $comment)
            <li
                id="comment-{{ $comment->id }}"
                class="tip-show__comment-item"
                data-comment-id="{{ $comment->id }}"
            >
                @if ($comment->isDeleted())
                    {{-- 삭제된 댓글은 작성자와 기존 본문을 노출하지 않는다. --}}
                    <p class="tip-show__comment-status">
                        삭제된 댓글입니다.
                    </p>
                @elseif ($comment->isHidden())
                    {{-- 관리자 숨김은 작성자 삭제와 구분해서 표시한다. --}}
                    <p class="tip-show__comment-status">
                        관리자에 의해 숨겨진 댓글입니다.
                    </p>
                @else
                    <header class="tip-show__comment-header">
                        <strong class="tip-show__comment-author">
                            {{ $comment->user->name }}
                        </strong>

                        <time
                            class="tip-show__comment-created-at"
                            datetime="{{ $comment->created_at?->toIso8601String() }}"
                        >
                            {{ $comment->created_at?->diffForHumans() }}
                        </time>
                    </header>

                    {{-- Blade 이스케이프 출력으로 댓글 안의 HTML을 실행하지 않는다. --}}
                    {{-- pre-wrap이 Blade 들여쓰기까지 표시하지 않도록 본문은 태그와 같은 줄에 출력한다. --}}
                    <p class="tip-show__comment-body">{{ $comment->body }}</p>
                @endif
            </li>
        @empty
            <li class="tip-show__comment-empty">
                아직 작성된 댓글이 없습니다.
            </li>
        @endforelse
    </ul>

    @if ($comments->hasPages())
        <div
            class="tip-show__comment-pagination"
            data-comment-pagination
        >
            {{ $comments->onEachSide(1)->links() }}
        </div>
    @endif
</div>
