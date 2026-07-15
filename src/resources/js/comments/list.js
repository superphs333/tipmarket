/**
 * @typedef {Object} LoadCommentsOptions 댓글 목록 조회 옵션
 * @property {string|URL} [url] 조회할 댓글 페이지 URL
 * @property {boolean} [scrollToList] 목록 시작 위치 이동 여부
 * @property {number|string|null} [focusCommentId] 강조할 댓글 ID
 */

/**
 * @callback LoadComments 댓글 목록 조회 함수
 * @param {LoadCommentsOptions} [options] 댓글 목록 조회 옵션
 * @return {Promise<void>}
 */

/**
 * 댓글 목록 조회와 페이지네이션을 초기화
 *
 * @param {{
 *     section: Element,
 *     indexUrl: string,
 *     listContainer: Element
 * }} options 댓글 목록 초기화에 필요한 요소와 URL
 * @return {{
 *     loadComments: LoadComments,
 *     getCurrentUrl: () => string|URL
 * }} 다른 댓글 기능이 사용할 목록 조회 상태
 */
export const initializeCommentList = ({
    section, // 댓글 기능 전체를 감싸는 요소
    indexUrl, // 댓글 목록 조회 기본 URL
    listContainer, // 서버가 반환한 댓글 목록 HTML을 출력할 영역 (이요소의 innerHTML 이 교체됨)
}) => {
    const countElement = section.querySelector('[data-comment-count]');
    const loadErrorTemplate = section.querySelector(
        '[data-comment-load-error-template]',
    );

    /**
     * 현재 진행 중인 댓글 목록 요청
     *
     * 페이지를 빠르게 연속 클릭했을 때 이전 응답이 최신 화면을
     * 덮어쓰지 않도록 이전 요청을 취소하는 데 사용
     *
     * @type {AbortController|null}
     */
    let activeListRequest = null;
    let currentUrl = indexUrl;

    /**
     * 댓글 목록 조회 실패 화면을 출력
     *
     * @return {void}
     */
    const renderLoadError = () => {
        const errorElement = loadErrorTemplate instanceof HTMLTemplateElement
            ? loadErrorTemplate.content.firstElementChild?.cloneNode(true)
            : null;

        // 템플릿 누락 시에도 사용자가 실패 상태를 알 수 있도록 문구를 남긴다.
        if (!(errorElement instanceof HTMLElement)) {
            listContainer.textContent = '댓글을 불러오지 못했습니다.';

            return;
        }

        // 기존 로딩 또는 댓글 목록을 템플릿에서 복제한 재시도 UI로 교체한다.
        listContainer.replaceChildren(errorElement);
    };

    /**
     * 댓글 목록 응답에 포함된 서버 기준 댓글 수를 화면에 반영
     *
     * paginator의 total은 원댓글만 계산하므로 사용하지 않음
     * 원댓글과 대댓글을 포함하는 tips.comment_count를 사용
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

        // data-comment-count-value는 dataset에서 commentCountValue로 접근
        const serverCount = fragment?.dataset.commentCountValue;

        // 서버 응답에 댓글 수가 없으면 현재 화면 값을 유지
        if (serverCount === undefined) {
            return;
        }

        countElement.textContent = serverCount;
    };

    /**
     * 댓글 목록 HTML을 서버에서 가져와 목록 영역을 교체
     *
     * @param {LoadCommentsOptions} options 목록 조회 및 교체 이후 화면 동작
     * @return {Promise<void>}
     */
    const loadComments = async ({
        url = indexUrl,
        scrollToList = false,
        focusCommentId = null,
    } = {}) => {
        // 이전 댓글 목록 요청이 남아 있다면 취소
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

            // fetch는 404나 500 응답을 자동으로 예외 처리하지 않음
            if (!response.ok) {
                throw new Error(`댓글 조회 실패: ${response.status}`);
            }

            const html = await response.text();

            // 서버가 Blade로 렌더링한 댓글 목록으로 교체
            listContainer.innerHTML = html;
            currentUrl = url;

            // 목록과 함께 전달받은 서버 기준 댓글 수를 반영
            syncCommentCount();

            // 댓글 등록 직후라면 새 댓글 ID에 해당하는 요소를 탐색
            if (focusCommentId !== null) {
                const commentElement = document.getElementById(
                    `comment-${focusCommentId}`,
                );

                // 찾은 요소가 현재 목록 내부에 있을 때만 이동
                if (
                    commentElement
                    && listContainer.contains(commentElement)
                ) {
                    commentElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                    });

                    // CSS에서 새 댓글을 강조할 수 있도록 상태 클래스를 적용
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

            // 페이지네이션으로 이동한 경우 목록 시작 위치로 이동
            if (scrollToList) {
                listContainer.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start',
                });
            }
        } catch (error) {
            // 새로운 요청으로 의도적으로 취소된 이전 요청은 정상 흐름
            if (
                error instanceof DOMException
                && error.name === 'AbortError'
            ) {
                return;
            }

            renderLoadError();
        } finally {
            // 종료된 요청이 최신 요청인 경우에만 로딩 상태를 해제
            if (activeListRequest === request) {
                listContainer.setAttribute('aria-busy', 'false');
                activeListRequest = null;
            }
        }
    };

    /**
     * 비동기로 교체되는 페이지네이션과 재시도 버튼을 처리
     *
     * 목록 내부 요소에 직접 이벤트를 등록하지 않고,
     * 교체되지 않는 section에서 이벤트를 위임수신
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

            // paginationLink는 <a> 요소이며 실제 페이지 URL은 href에 있음
            loadComments({
                url: paginationLink.href,
                scrollToList: true,
            });

            return;
        }

        const retryButton = event.target.closest(
            '[data-comment-retry]',
        );

        if (retryButton) {
            // URL을 생략하면 기본 indexUrl로 첫 페이지를 재조회
            loadComments();
        }
    });

    return {
        loadComments,
        getCurrentUrl: () => currentUrl,
    };
};
