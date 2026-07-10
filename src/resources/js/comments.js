/**
 * 팁 상세 화면의 댓글 목록 조회와 원댓글 등록을 초기화한다.
 *
 * 목록 조회:
 * - 상세 화면 진입 시 최신 댓글 1페이지를 조회한다.
 * - 서버가 렌더링한 Blade HTML로 목록 영역을 교체한다.
 * - 목록 응답에 포함된 서버 기준 댓글 수를 화면에 반영한다.
 *
 * 댓글 등록:
 * - 로그인 사용자의 댓글을 비동기로 등록한다.
 * - 성공하면 입력창을 초기화하고 첫 페이지를 다시 조회한다.
 * - 새 댓글 위치로 스크롤한다.
 *
 * @param {Element} section 댓글 섹션
 * @return {void}
 */
const initializeTipComments = (section) => {
    /**
     * Blade가 data 속성으로 전달한 API URL이다.
     *
     * indexUrl은 비회원도 사용할 수 있는 댓글 목록 조회 URL이고,
     * storeUrl은 로그인 사용자에게만 전달되는 등록 URL이다.
     */
    const indexUrl = section.dataset.commentsIndexUrl;
    const storeUrl = section.dataset.commentsStoreUrl;

    /**
     * 댓글 목록과 등록 폼에서 사용할 DOM 요소다.
     *
     * 비회원 화면에는 폼 관련 요소가 없으므로 null일 수 있다.
     */
    const listContainer = section.querySelector(
        '[data-comment-list-container]',
    );
    const form = section.querySelector('[data-comment-form]');
    const bodyInput = section.querySelector('[data-comment-body]');
    const submitButton = section.querySelector('[data-comment-submit]');
    const errorElement = section.querySelector('[data-comment-error]');
    const countElement = section.querySelector('[data-comment-count]');

    // 목록 조회에 필요한 URL이나 컨테이너가 없으면 초기화를 중단한다.
    if (!indexUrl || !listContainer) {
        return;
    }

    /**
     * 현재 진행 중인 댓글 목록 요청이다.
     *
     * 페이지를 빠르게 연속 클릭했을 때 이전 응답이 최신 화면을
     * 덮어쓰지 않도록 이전 요청을 취소하는 데 사용한다.
     *
     * @type {AbortController|null}
     */
    let activeListRequest = null;

    // 댓글 등록 요청의 중복 실행을 방지한다.
    let isSubmitting = false;

    /**
     * 댓글 목록 조회 실패 화면을 출력한다.
     *
     * @return {void}
     */
    const renderLoadError = () => {
        listContainer.innerHTML = `
            <div class="tip-show__comment-load-error" role="alert">
                <p>댓글을 불러오지 못했습니다.</p>

                <button type="button" data-comment-retry>
                    다시 시도
                </button>
            </div>
        `;
    };

    /**
     * 댓글 작성 오류 메시지를 표시한다.
     *
     * @param {string} message 사용자에게 보여줄 메시지
     * @return {void}
     */
    const showFormError = (message) => {
        if (!errorElement) {
            return;
        }

        errorElement.textContent = message;
        errorElement.hidden = false;
    };

    /**
     * 기존 댓글 작성 오류 메시지를 제거한다.
     *
     * @return {void}
     */
    const clearFormError = () => {
        if (!errorElement) {
            return;
        }

        errorElement.textContent = '';
        errorElement.hidden = true;
    };

    /**
     * 댓글 목록 응답에 포함된 서버 기준 댓글 수를 화면에 반영한다.
     *
     * paginator의 total은 원댓글만 계산하므로 사용하지 않는다.
     * 원댓글과 대댓글을 포함하는 tips.comment_count를 사용한다.
     *
     * @return {void}
     */
    const syncCommentCount = () => {
        if (!countElement) {
            return;
        }

        const fragment = listContainer.querySelector(
            '[data-comment-list-fragment]',
        );

        // data-comment-count-value는 dataset에서 commentCountValue로 접근한다.
        const serverCount = fragment?.dataset.commentCountValue;

        // 서버 응답에 댓글 수가 없으면 현재 화면 값을 유지한다.
        if (serverCount === undefined) {
            return;
        }

        countElement.textContent = serverCount;
    };

    /**
     * 댓글 목록 HTML을 서버에서 가져와 목록 영역을 교체한다.
     *
     * @param {string|URL} url 조회할 댓글 페이지 URL
     * @param {{
     *     scrollToList?: boolean,
     *     focusCommentId?: number|string|null
     * }} options 목록 교체 이후 실행할 화면 동작
     * @return {Promise<void>}
     */
    const loadComments = async (
        url = indexUrl,
        {
            scrollToList = false,
            focusCommentId = null,
        } = {},
    ) => {
        // 이전 댓글 목록 요청이 남아 있다면 취소한다.
        activeListRequest?.abort();

        const request = new AbortController();
        activeListRequest = request;

        listContainer.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    Accept: 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                signal: request.signal,
            });

            // fetch는 404나 500 응답을 자동으로 예외 처리하지 않는다.
            if (!response.ok) {
                throw new Error(`댓글 조회 실패: ${response.status}`);
            }

            const html = await response.text();

            // 서버가 Blade로 렌더링한 댓글 목록으로 교체한다.
            listContainer.innerHTML = html;

            // 목록과 함께 전달받은 서버 기준 댓글 수를 반영한다.
            syncCommentCount();

            // 댓글 등록 직후라면 새 댓글 ID에 해당하는 요소를 찾는다.
            if (focusCommentId !== null) {
                const commentElement = document.getElementById(
                    `comment-${focusCommentId}`,
                );

                // 찾은 요소가 현재 목록 내부에 있을 때만 이동한다.
                if (
                    commentElement
                    && listContainer.contains(commentElement)
                ) {
                    commentElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                    });

                    // CSS에서 새 댓글을 강조할 수 있도록 상태 클래스를 적용한다.
                    commentElement.classList.add(
                        'tip-show__comment-item--new',
                    );

                    window.setTimeout(() => {
                        commentElement.classList.remove(
                            'tip-show__comment-item--new',
                        );
                    }, 2000);

                    return;
                }
            }

            // 페이지네이션으로 이동한 경우 목록 시작 위치로 이동한다.
            if (scrollToList) {
                listContainer.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start',
                });
            }
        } catch (error) {
            // 새로운 요청으로 의도적으로 취소된 이전 요청은 오류가 아니다.
            if (
                error instanceof DOMException
                && error.name === 'AbortError'
            ) {
                return;
            }

            renderLoadError();
        } finally {
            // 종료된 요청이 최신 요청인 경우에만 로딩 상태를 해제한다.
            if (activeListRequest === request) {
                listContainer.setAttribute('aria-busy', 'false');
                activeListRequest = null;
            }
        }
    };

    /**
     * 비동기로 교체되는 페이지네이션과 재시도 버튼을 처리한다.
     *
     * 목록 내부 요소에 직접 이벤트를 등록하지 않고,
     * 교체되지 않는 section에서 이벤트를 위임받는다.
     */
    section.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const paginationLink = event.target.closest(
            '[data-comment-pagination] a',
        );

        if (paginationLink) {
            event.preventDefault();

            // paginationLink는 <a> 요소이며 실제 페이지 URL은 href에 있다.
            loadComments(paginationLink.href, {
                scrollToList: true,
            });

            return;
        }

        const retryButton = event.target.closest(
            '[data-comment-retry]',
        );

        if (retryButton) {
            // URL을 생략하면 기본 indexUrl로 첫 페이지를 재조회한다.
            loadComments();
        }
    });

    /**
     * 로그인 사용자에게만 존재하는 댓글 작성 폼을 초기화한다.
     */
    if (
        form
        && bodyInput
        && submitButton
        && storeUrl
    ) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            // 등록 요청이 진행 중이면 추가 제출을 무시한다.
            if (isSubmitting) {
                return;
            }

            clearFormError();

            // 공백만 입력한 경우 서버 요청 전에 바로 안내한다.
            const body = bodyInput.value.trim();

            if (body === '') {
                showFormError('댓글 내용을 입력해주세요.');
                bodyInput.focus();

                return;
            }

            isSubmitting = true;
            submitButton.disabled = true;

            try {
                /**
                 * FormData에는 body와 @csrf가 만든 _token이 포함된다.
                 * Content-Type은 브라우저가 자동으로 설정하므로 직접 지정하지 않는다.
                 */
                const formData = new FormData(form);

                const response = await fetch(storeUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: formData,
                });

                // 성공과 검증 실패 응답을 모두 JSON으로 읽는다.
                const payload = await response
                    .json()
                    .catch(() => ({}));

                if (!response.ok) {
                    // FormRequest의 body 검증 메시지를 우선 표시한다.
                    if (
                        response.status === 422
                        && Array.isArray(payload.errors?.body)
                    ) {
                        showFormError(payload.errors.body[0]);

                        return;
                    }

                    // 로그인 세션 또는 CSRF 토큰이 만료된 경우다.
                    if (
                        response.status === 401
                        || response.status === 419
                    ) {
                        showFormError(
                            '로그인 세션이 만료되었습니다. 새로고침 후 다시 시도해주세요.',
                        );

                        return;
                    }

                    showFormError(
                        payload.message
                        || '댓글을 등록하지 못했습니다.',
                    );

                    return;
                }

                // DB 등록이 성공한 뒤에만 입력창을 초기화한다.
                form.reset();

                /**
                 * 최신순이므로 새 댓글은 항상 첫 페이지에 있다.
                 * 기본 indexUrl로 목록을 다시 불러와 목록과 댓글 수를 동기화한다.
                 */
                await loadComments(indexUrl, {
                    focusCommentId: payload.comment_id ?? null,
                });
            } catch {
                showFormError(
                    '네트워크 오류가 발생했습니다. 잠시 후 다시 시도해주세요.',
                );
            } finally {
                // 성공과 실패 여부에 관계없이 제출 상태를 복구한다.
                isSubmitting = false;
                submitButton.disabled = false;
            }
        });
    }

    // 상세 화면 진입 시 최신 댓글 첫 페이지를 최초 조회한다.
    loadComments();
};

// 문서에 존재하는 댓글 영역을 각각 초기화한다.
document
    .querySelectorAll('[data-tip-comments]')
    .forEach(initializeTipComments);
