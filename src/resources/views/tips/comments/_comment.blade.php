{{--
    원댓글과 대댓글 한 건의 공통 화면을 출력

    전달 변수:
    - $comment: 현재 출력할 원댓글 또는 대댓글
    - $rootComment: 현재 댓글 묶음의 원댓글
    - $isReply: 현재 출력 대상이 대댓글인지 여부
--}}

@if ($comment->isDeleted())
    {{-- 삭제된 댓글은 작성자와 기존 본문을 노출하지 않는다. --}}
    <p class="tip-show__comment-status">
        삭제된 댓글입니다.
    </p>
@elseif ($comment->isHidden())
    {{-- 관리자 숨김 상태는 작성자 삭제 상태와 구분해서 표시한다. --}}
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

        @auth
            @php
                // 삭제된 원댓글 묶음에는 답글 버튼을 숨기되 작성자 관리 기능은 유지한다.
                $canReply = $rootComment->isActive();
                $isAuthor = auth()->id() === $comment->user_id;
            @endphp

            {{-- 수정 중에는 JavaScript가 이 액션 영역을 숨긴다. --}}
            @if ($canReply || $isAuthor)
                <div
                    class="tip-show__comment-actions"
                    data-comment-actions
                    aria-label="{{ $isReply ? '대댓글 관리' : '댓글 관리' }}"
                >
                    @if ($canReply)
                        <button
                            type="button"
                            class="tip-show__comment-action"
                            data-comment-reply
                            data-comment-reply-url="{{ route('comments.replies.store', $comment) }}"
                            data-comment-reply-author="{{ $comment->user->name }}"
                            data-comment-root-id="{{ $rootComment->id }}"
                            aria-controls="comment-reply-region-{{ $rootComment->id }}"
                            aria-expanded="false"
                        >
                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                <path d="M8 10h8M8 14h5" />
                                <path d="M5 5.5h14v12H9l-4 3v-15Z" />
                            </svg>
                            <span>댓글</span>
                        </button>
                    @endif

                    {{-- 수정과 삭제는 현재 댓글의 작성자에게만 노출한다. --}}
                    @if ($isAuthor)
                        <button
                            type="button"
                            class="tip-show__comment-action"
                            data-comment-edit
                            data-comment-update-url="{{ route('comments.update', $comment) }}"
                            aria-controls="comment-body-{{ $comment->id }}"
                        >
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
            @endif
        @endauth
    </header>

    <p
        id="comment-body-{{ $comment->id }}"
        class="tip-show__comment-body"
        data-comment-body
    >
        {{-- 대댓글이 다른 대댓글을 대상으로 작성된 경우에만 멘션을 표시한다. --}}
        @if (
            $isReply
            && $comment->reply_to_id !== $rootComment->id
            && $comment->replyTo?->isActive()
            && $comment->replyTo?->user !== null
        )
            <strong class="tip-show__reply-mention">
                {{ '@'.$comment->replyTo->user->name }}
            </strong>
        @endif

        {{-- Blade 들여쓰기는 줄바꿈으로 보존하지 않고 실제 본문만 pre-wrap 처리한다. --}}
        <span
            class="tip-show__comment-body-text"
            data-comment-body-text
        >{{ $comment->body }}</span>
    </p>
@endif
