/**
 * 대댓글 입력 폼을 열고 등록 요청을 처리한다.
 *
 * Blade의 <template>에 정의된 대댓글 폼을 한 번 복제한 뒤, 
 * 사용자가 선택한 댓글이 속한 원댓글의 reply region으로 이동해 재사용.
 *
 * @param {{
 *     section: Element,
 *     loadComments: (options?: {
 *         url?: string|URL,
 *         focusCommentId?: number|string|null
 *     }) => Promise<void>,
 *     getCurrentUrl: () => string|URL
 * }} options : 대댓글 기능 초기화에 필요한 댓글 영역과 목록 제어 함수.
 * @return {void}
 */
export const initializeCommentReplies = ({
    section,
    loadComments,
    getCurrentUrl,
}) => {
    // 로그인 사용자 화면에 출력된 template 찾기
    const template = section.querySelector(
        '[data-comment-reply-template]',
    );

    // 비회원 화면에는 로그인 전용 대댓글 템플릿이 없다.
    if (!(template instanceof HTMLTemplateElement)) {
        return;
    }

    // template 내부의 첫 번재 요소인 <form> 깊은 복제 
    const form = template.content.firstElementChild?.cloneNode(true);

    if (!(form instanceof HTMLFormElement)) {
        return;
    }
    
    const targetElement = form.querySelector('[data-comment-reply-target]'); // 답글 대상
    const bodyInput = form.querySelector('[data-comment-reply-body]'); // 실제 대댓글 본문 textarea
    const countElement = form.querySelector('[data-comment-reply-count]'); // 현재 입력 글자 수 
    const errorElement = form.querySelector('[data-comment-reply-error]'); // 오류 메세지 표시
    const submitButton = form.querySelector('[data-comment-reply-submit]'); // 대댓글 등록 버튼
    const cancelButtons = form.querySelectorAll('[data-comment-reply-cancel]'); // 상단 닫기 버튼, 하단 취소 버튼

    // 템플릿 변경으로 필수 요소가 빠졌다면 잘못된 요청을 만들지 않는다.
    if (
        !(targetElement instanceof HTMLElement)
        || !(bodyInput instanceof HTMLTextAreaElement)
        || !(countElement instanceof HTMLElement)
        || !(errorElement instanceof HTMLElement)
        || !(submitButton instanceof HTMLButtonElement)
    ) {
        return;
    }

    /** 
     * 현재 대댓글 폼을 연 답글 버튼 보관
     * 
     * @type {HTMLButtonElement|null}
     */
    let activeButton = null;
    // 대댓글 등록 요청이 진행 중인지 나타냄
    let isSubmitting = false;

    const getBody = () => bodyInput.value.trim(); // 대댓글 본문

    /** 
     * 대댓글 폼의 오류 메세지를 표시하거나 초기화
     * 
     *  @param {string} message */
    const setError = (message = '') => {
        errorElement.textContent = message;
        errorElement.hidden = message === '';
    };

    // 입력값과 요청 상태를 기준으로 글자 수와 등록 버튼을 동기화한다.
    const syncInput = () => {
        countElement.textContent = `${bodyInput.value.length} / 1,000`;
        submitButton.disabled = getBody() === '' || isSubmitting;
    };

    /** 
     * 등록 요청 진행 상태를 폼 전체에 반영
     * 
     * 요청 중에는 texarea와 취소 버튼을 비활성화하고 등록 버튼 문구를 변경하여 동일한 요청이 중복 전송되거나 폼이 중간에 닫히지 않게 함. 
     * 
     * @param {boolean} submitting 등록 요청 진행 여부
     * 
     */
    const setSubmitting = (submitting) => {
        isSubmitting = submitting;
        bodyInput.disabled = submitting;
        submitButton.textContent = submitting ? '등록 중…' : '답글 등록';

        // 상단 닫기 버튼과 하단 취소 버튼을 동일하게 제어 
        cancelButtons.forEach((button) => {
            if (button instanceof HTMLButtonElement) {
                button.disabled = submitting;
            }
        });

        syncInput();
    };

    /**
     * 현재 작성 중인 초안을 버려도 되는지 확인.
     * : 본문이 비어 있으면 확인창 없이 바로 허용
     * , 본문이 있으면 확인을 받아 실수로 입력을 잃지 않게 함
     * @return {boolean} 폼을 닫거나 다른 대상으로 이동해도 되는지 여부
     */
    const canDiscardDraft = () => (
        getBody() === ''
        || window.confirm('작성 중인 답글을 지우시겠습니까?')
    );

    /** 
     * 현재 열린 대댓글 폼과 활성 버튼 상태를 초기화.
     * 
     * @param {boolean} force 등록 성공 시 요청 중에도 닫을지 여부 
     * @return {void}
     * */
    const close = (force = false) => {
        // 등록 요청 중 일반 취소|닫기 요청은 무시 
        if (isSubmitting && !force) {
            return;
        }
        // 현재 폼을 연 답글 버튼의 활성 스타일과 접근성 상태를 복원.
        activeButton?.classList.remove(
            'tip-show__comment-action--active',
        );
        activeButton?.setAttribute('aria-expanded', 'false');

        form.remove();
        form.reset();
        activeButton = null;
        setError();
        syncInput();
    };

    /**
     * 선택한 답글 버튼을 기준으로 대댓글 입력 폼을 연다.
     * : 버튼의 data 속성에서 등록 URL, 대상 작성자, 원댓글 id를 읽고
     * 동일한 원댓글 ID를 가진 reply region으로 공용 form을 이동
     *  
     * @param {HTMLButtonElement} button 사용자가 클릭한 답글 버튼
     * @return {void}
     * */
    const open = (button) => {
        const url = button.dataset.commentReplyUrl; // 실제 선택한 댓글에 대한 대댓글 등록 URL
        const author = button.dataset.commentReplyAuthor; // 폼 상단에 표시할 답글 대상 작성자
        const rootId = button.dataset.commentRootId; // 공용 폼을 배치할 원댓글 reply region 식별자 

        if (!url || !author || !rootId || isSubmitting) {
            return;
        }

        // 목록 교체로 폼이 제거된 경우 남아 있는 이전 상태를 정리한다.
        if (activeButton && !form.isConnected) {
            close(true);
        }

        // 이미 선택된 답글 버튼을 다시 누르면 폼을 재생성하거나 초기화하지 않고, 현재 textarea에 포커스만 이동
        if (activeButton === button) {
            bodyInput.focus();

            return;
        }

        // 다른 답글 대상으로 변경하려는데 기존 초안이 있으면, 사용자에게 초안을 버릴지 확인
        if (activeButton && !canDiscardDraft()) {
            return;
        }

        // 같은 원댓글 묶음 아래에 마련된 reply region을 찾기 
        const region = section.querySelector(
            `[data-comment-reply-region][data-root-comment-id="${CSS.escape(rootId)}"]`,
        );

        if (!(region instanceof HTMLElement)) {
            return;
        }

        // 기존 활성 버튼과 폼 입력 상태를 정리
        close(true);

        // URL과 사용자명은 HTML 문자열로 조립하지 않고 DOM 속성에 반영한다.
        form.action = url;
        targetElement.textContent = `${author}님에게 답글`;

        // 새로 선택한 버튼을 현재 활성 버튼으로 등록, 
        // 해당 원댓글의 reply region으로 공용 form을 이동
        activeButton = button;
        region.append(form);

        button.classList.add('tip-show__comment-action--active');
        button.setAttribute('aria-expanded', 'true');
        bodyInput.focus();
    };

    /** 
     * 서버 응답을 JSON으로 변환
     * 
     * @param {Response} response fetch 응답 객체
     * @return {Promise<Record<string, any>>} 변환된 JSON 또는 빈 객체
     * 
     * */
    const parseJsonResponse = async (response) => (
        response.json().catch(() => ({}))
    );

    /**
     * HTTP 상태와 Laravel JSON 응답에서 사용자 안내 문구를 결정한다.
     *
     * @param {Response} response fetch 응답 객체
     * @param {Record<string, any>} payload Laravel JSON 응답
     * @return {string} 사용자에게 표시할 오류 메세지
     */
    const getReplyErrorMessage = (response, payload) => {

        // FormRequest 검증 실패
        if (response.status === 422 && payload.errors?.body?.[0]) {
            return payload.errors.body[0];
        }

        if (response.status === 401 || response.status === 419) {
            return '로그인 세션이 만료되었습니다. 새로고침 후 다시 시도해주세요.';
        }

        // 삭제|숨김|댓글 또는 잘못된 계층에 답글을 작성하면 서버가 409와 구체적인 충돌 메세지 반환 
        if (response.status === 409 && typeof payload.message === 'string') {
            return payload.message;
        }

        if (response.status === 403) {
            return '이 팁에 답글을 등록할 권한이 없습니다.';
        }

        if (response.status === 404) {
            return '답글 대상 댓글을 찾을 수 없습니다.';
        }

        return '답글을 등록하지 못했습니다. 잠시 후 다시 시도해주세요.';
    };

    /**
     * 현재 대댓글 폼을 서버에 전송하고 응답과 JSON을 함께 반환
     *
     * @param {string} body
     * @return {Promise<{response: Response, payload: Record<string, any>}>} HTTP응답과 파싱한 JSON 
     */
    const postReply = async (body) => {
        const formData = new FormData(form);
        // 원본 textarea값 대신 getBody()에서 trim처리한 값 설정.
        formData.set('body', body);

        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: formData,
        });

        return {
            response,
            payload: await parseJsonResponse(response),
        };
    };

    /**
     * 현재 폼의 대댓글을 서버에 등록
     * 
     * @returns {Promise<void>}
     */
    const submit = async () => {
        // 열린 답글 대상이 없거나 이미 요청 중이면 제출하지 않음.
        if (!activeButton || isSubmitting) {
            return;
        }
        
        const body = getBody();

        if (body === '') {
            setError('답글 내용을 입력해주세요.');
            bodyInput.focus();

            return;
        }

        setError();
        setSubmitting(true);

        try {
            const { response, payload } = await postReply(body);

            // 서버 오류를 사용자 메세지로 변환
            if (!response.ok) {
                setError(getReplyErrorMessage(response, payload));

                return;
            }

            // 등록 성공 응답의 새 댓글 ID
            const commentId = payload.comment_id ?? null;

            // 목록이 교체되기 전에 폼과 활성 버튼 상태를 정리한다.
            setSubmitting(false);
            close();

            // 대댓글은 현재 원댓글 페이지에 속하므로 보고 있던 페이지를 유지한다.
            await loadComments({                
                url: getCurrentUrl(),
                focusCommentId: commentId,
            });
        } catch {
            setError(
                '네트워크 오류가 발생했습니다. 잠시 후 다시 시도해주세요.',
            );
        } finally {
            setSubmitting(false);
        }
    };

    // 댓글 목록은 list.js가 innerHTML로 계속 교체 
    section.addEventListener('click', (event) => {
        // closest()를 지원하지 않는 대상은 처리하지 않음. 
        if (!(event.target instanceof Element)) {
            return;
        }
        // 가장 가까운 답글 버튼 찾기 
        const button = event.target.closest('[data-comment-reply]');

        if (button instanceof HTMLButtonElement) {
            open(button);
        }
    });

    // 대댓글 폼 안의 상단 닫기, 취소 버튼을 함께 처리.
    form.addEventListener('click', (event) => {
        if (
            event.target instanceof Element
            && event.target.closest('[data-comment-reply-cancel]')
            && canDiscardDraft()
        ) {
            close();
        }
    });

    // 본문을 입력할 때 이전 오류를 제거하고 글자 수와 버튼 상태를 갱신
    bodyInput.addEventListener('input', () => {
        setError();
        syncInput();
    });

    // Escape키로 폼을 닫을 수 있게 함
    // : 요청 중에는 닫지 않고, 작성 중인 본문이 있으면 폐기 확인을 받는다.
    bodyInput.addEventListener('keydown', (event) => {
        if (
            event.key === 'Escape'
            && !isSubmitting
            && canDiscardDraft()
        ) {
            close();
        }
    });

    // 브라우저의 기본 form 제출과 전체 페이지 이동을 막고, 비동기 대댓글 등록 함수로 처리 
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        submit();
    });

    /*
     * 댓글 목록을 교체하는 동작 전에 작성 중인 초안을 보호 
     * : 페이지 이동, 댓글 삭제, 목록 재새도, 원댓글 등록은 댓글 목록 HTML을 교체하는데, 
     * 열린 대댓글 폼도 함께 사라질 수 있으므로 캡쳐 단계에서 초안 폐기 여부를 먼저 확인
     * 
     * @param {Event} event 목록 교체를 발생시키려는 원본 이벤트
     * @return {void}
     */
    const guardDraftBeforeListChange = (event) => {
        // 활성 답글 대상이 없거나 폼이 현재 DOM에 연결되어 있지 않으면, 
        // 보호할 초안이 없으므로 이벤트를 그대로 진행.
        if (!activeButton || !form.isConnected) {
            return;
        }
        // 원래 이벤트의 기본 동작과 같은 요소의 다른 이벤트 리스너 실행을 모두 중단.
        if (!canDiscardDraft()) {
            event.preventDefault();
            event.stopImmediatePropagation();

            return;
        }

        // 폼을 먼저 정리하고 원래 동작을 계속함.
        close();
    };

    /**
     * 목록 교체 동작을 일반 버블링 리스너보다 먼저 확인하기 위해 
     * 캡쳐 단계에서 이벤트를 처리함. 
     * 
     * 보호대상) 
     * - 댓글 페이지네이션 링크
     * - 댓글 삭제 버튼
     * - 댓글 목록 재시도 버튼
     */
    section.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const replacesList = event.target.closest(
            '[data-comment-pagination] a, [data-comment-delete], [data-comment-retry]',
        );

        if (replacesList) {
            guardDraftBeforeListChange(event);
        }
    }, { capture: true });

    // 원댓글 등록 성공도 댓글 목록을 첫 페이지 HTML로 교체
    section.addEventListener('submit', (event) => {
        if (
            event.target instanceof Element
            && event.target.matches('[data-comment-form]')
        ) {
            guardDraftBeforeListChange(event);
        }
    }, { capture: true });
};
