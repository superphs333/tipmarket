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
                            {{--
                                댓글 수정 중에는 현재 작업에만 집중할 수 있도록
                                댓글, 수정, 삭제 버튼이 들어 있는 액션 영역 전체를 숨긴다.

                                data-comment-actions는 JavaScript가 이 영역을 찾아
                                수정 시작 시 숨기고, 취소 또는 저장 성공 시 다시 표시하는 데 사용한다.
                            --}}
                            <div
                                class="tip-show__comment-actions"
                                data-comment-actions
                                aria-label="댓글 관리"
                            >
                                <button type="button" class="tip-show__comment-action">
                                    <svg aria-hidden="true" viewBox="0 0 24 24">
                                        <path d="M8 10h8M8 14h5" />
                                        <path d="M5 5.5h14v12H9l-4 3v-15Z" />
                                    </svg>
                                    <span>댓글</span>
                                </button>

                                {{-- 수정과 삭제는 댓글 작성자에게만 노출한다. --}}
                                @if (auth()->id() === $comment->user_id)
                                    <button
                                        type="button"
                                        {{-- js가 클릭된 요소가 댓글 수정 버튼인지 판별할 때 사용 --}}
                                        class="tip-show__comment-action"
                                        data-comment-edit
                                        {{-- 댓글을 수정할 서버 주소를 js에 전달 --}}
                                        data-comment-update-url="{{ route('comments.update', $comment) }}"
                                        {{-- 이 버튼이 제어하는 댓글 본문의 id를 지정 --}}
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
                        @endauth
                    </header>

                    {{-- 댓글 본문 --}}
                    <p
                        {{-- 수정 버튼의 aria-controls와 연결 --}}
                        id="comment-body-{{ $comment->id }}"
                        class="tip-show__comment-body"
                        {{-- js가 현재 댓글 본문 요소를 찾을 때 사용.
                        - 수정 시작 시 => 이 요소를 숨기고 편집창을 표시
                        - 수정 성공 시 서버에서 받은 본문을 이 요소에 다시 반영 --}}
                        data-comment-body
                    >{{ $comment->body }}</p>
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
