@props([
    'tip',
])

<section
    id="tip-comments"
    class="tip-show__section tip-show__comments"
    data-tip-comments
    data-comments-index-url="{{ route('tips.comments.index', $tip) }}"
    @auth
        data-comments-store-url="{{ route('tips.comments.store', $tip) }}"
    @endauth
>
    <h2 class="tip-show__section-title">
        댓글
        <span data-comment-count>
            {{ $tip->comment_count }}
        </span>
    </h2>

    @auth
        <form
            class="tip-show__comment-form"
            action="{{ route('tips.comments.store', $tip) }}"
            method="post"
            data-comment-form
        >
            @csrf

            <label for="tip-show-comment-{{ $tip->id }}">
                댓글 입력
            </label>

            <textarea
                id="tip-show-comment-{{ $tip->id }}"
                name="body"
                maxlength="1000"
                required
                placeholder="댓글을 입력해주세요."
                data-comment-body
                aria-describedby="tip-comment-error-{{ $tip->id }}"
            ></textarea>

            <p
                id="tip-comment-error-{{ $tip->id }}"
                class="tip-show__comment-error"
                data-comment-error
                role="alert"
                hidden
            ></p>

            <div class="tip-show__comment-form-actions">
                <button type="submit" data-comment-submit>
                    댓글 등록
                </button>
            </div>
        </form>
    @else
        <p class="tip-show__comment-login">
            댓글을 작성하려면
            <a href="{{ route('login') }}">로그인</a>해주세요.
        </p>
    @endauth

    @auth
        {{-- 대댓글 입력 폼의 원본 템플릿 --}}
        <template data-comment-reply-template>
            <form
                class="tip-show__reply-form"
                method="post"
                data-comment-reply-form
            >
                @csrf

                <div class="tip-show__reply-form-header">
                    {{--  실제 답글 대상 이름  --}}
                    <strong data-comment-reply-target></strong>

                    <button
                        type="button"
                        class="tip-show__reply-form-close"
                        data-comment-reply-cancel
                        aria-label="답글 입력 취소"
                    >
                        ×
                    </button>
                </div>

                <label class="sr-only" for="tip-comment-reply-body">
                    답글 내용
                </label>

                <textarea
                    id="tip-comment-reply-body"
                    class="tip-show__reply-input"
                    name="body"
                    maxlength="1000"
                    required
                    placeholder="답글을 입력해주세요."
                    data-comment-reply-body
                    aria-describedby="tip-comment-reply-error tip-comment-reply-count"
                ></textarea>

                <p
                    id="tip-comment-reply-error"
                    class="tip-show__comment-error"
                    data-comment-reply-error
                    role="alert"
                    hidden
                ></p>

                <div class="tip-show__reply-form-footer">
                    <span
                        id="tip-comment-reply-count"
                        class="tip-show__reply-count"
                        data-comment-reply-count
                    >
                        0 / 1,000
                    </span>

                    <div class="tip-show__reply-form-actions">
                        <button
                            type="button"
                            class="tip-show__reply-cancel"
                            data-comment-reply-cancel
                        >
                            취소
                        </button>

                        <button
                            type="submit"
                            class="tip-show__reply-submit"
                            data-comment-reply-submit
                            disabled
                        >
                            답글 등록
                        </button>
                    </div>
                </div>
            </form>
        </template>

        {{-- 인라인 수정 UI (원댓글, 대댓글 공통 사용) --}}
        <template data-comment-edit-template>
            <div
                class="tip-show__comment-editor"
                data-comment-editor
            >
                <textarea
                    class="tip-show__comment-edit-input"
                    data-comment-edit-input
                    maxlength="1000"
                    rows="4"
                    aria-label="댓글 내용 수정"
                ></textarea>

                <div class="tip-show__comment-edit-actions">
                    <button
                        type="button"
                        class="tip-show__comment-edit-cancel"
                        data-comment-edit-cancel
                    >
                        취소
                    </button>

                    <button
                        type="button"
                        class="tip-show__comment-edit-save"
                        data-comment-edit-save
                    >
                        저장
                    </button>
                </div>
            </div>
        </template>
    @endauth

    {{-- 재시도 UI --}}
    <template data-comment-load-error-template>
        <div class="tip-show__comment-load-error" role="alert">
            <p>댓글을 불러오지 못했습니다.</p>

            <button type="button" data-comment-retry>
                다시 시도
            </button>
        </div>
    </template>

    <div
        class="tip-show__comment-list-container"
        data-comment-list-container
        aria-busy="true"
    >
        <p class="tip-show__comment-loading" role="status">
            댓글을 불러오는 중입니다.
        </p>
    </div>
</section>

@vite(['resources/css/comments.css', 'resources/js/comments.js'])
