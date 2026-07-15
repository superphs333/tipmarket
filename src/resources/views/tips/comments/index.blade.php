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
                data-root-comment
            >
                @include('tips.comments._comment', [
                    'comment' => $comment,
                    'rootComment' => $comment,
                    'isReply' => false,
                ])

                {{-- 원댓글이 삭제되어도 기존 대댓글은 대화 흐름 유지를 위해 표시한다. --}}
                @if ($comment->replies->isNotEmpty())
                    <ul
                        class="tip-show__reply-list"
                        aria-label="{{ $comment->id }}번 댓글의 대댓글"
                    >
                        @foreach ($comment->replies as $reply)
                            <li
                                id="comment-{{ $reply->id }}"
                                class="tip-show__reply-item"
                                data-comment-id="{{ $reply->id }}"
                            >
                                @include('tips.comments._comment', [
                                    'comment' => $reply,
                                    'rootComment' => $comment,
                                    'isReply' => true,
                                ])
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- JavaScript가 공용 대댓글 입력 폼을 이 영역으로 이동시킨다. --}}
                @auth
                    <div
                        id="comment-reply-region-{{ $comment->id }}"
                        class="tip-show__reply-form-region"
                        data-comment-reply-region
                        data-root-comment-id="{{ $comment->id }}"
                    ></div>
                @endauth
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
