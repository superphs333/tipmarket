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
                        <div class="tip-show__comment-meta">
                            <strong class="tip-show__comment-author">
                                {{ $comment->user->name }}
                            </strong>

                            <time
                                class="tip-show__comment-created-at"
                                datetime="{{ $comment->created_at?->toIso8601String() }}"
                            >
                                {{ $comment->created_at?->diffForHumans() }}
                            </time>
                        </div>

                        {{-- 댓글 액션은 로그인 사용자에게만 노출하며 실제 동작은 추후 연결한다. --}}
                        @auth
                            <div class="tip-show__comment-actions" aria-label="댓글 관리">
                                <button type="button" class="tip-show__comment-action">
                                    <svg aria-hidden="true" viewBox="0 0 24 24">
                                        <path d="M8 10h8M8 14h5" />
                                        <path d="M5 5.5h14v12H9l-4 3v-15Z" />
                                    </svg>
                                    <span>댓글</span>
                                </button>

                                {{-- 수정과 삭제는 댓글 작성자에게만 노출한다. --}}
                                @if (auth()->id() === $comment->user_id)
                                    <button type="button" class="tip-show__comment-action">
                                        <svg aria-hidden="true" viewBox="0 0 24 24">
                                            <path d="m14.5 6.5 3 3M6 18l1-4 9-9 3 3-9 9-4 1Z" />
                                        </svg>
                                        <span>수정</span>
                                    </button>

                                    <button
                                        type="button"
                                        class="tip-show__comment-action tip-show__comment-action--danger"
                                        data-comment-delete
                                        data-comment-delete-url="{{ route('comments.destroy', $comment) }}"
                                    >
                                        <svg aria-hidden="true" viewBox="0 0 24 24">
                                            <path d="M8 8v10m4-10v10m4-10v10M5 5h14M9 5V3h6v2m2 0-1 16H8L7 5" />
                                        </svg>
                                        <span>삭제</span>
                                    </button>
                                @endif
                            </div>
                        @endauth
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
