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
