import { getCsrfToken } from './http.js';

/**
 * 로그인 사용자의 댓글 인라인 수정 기능을 초기화
 *
 * @param {{section: Element}} options 댓글 수정 버튼을 포함하는 댓글 섹션
 * @return {void}
 */
export const initializeCommentEditing = ({ section }) => {
    const editTemplate = section.querySelector(
        '[data-comment-edit-template]',
    );

    /**
     * 버튼이나 댓글 요소를 기준으로 편집에 공통으로 필요한 DOM을 조회한다.
     *
     * @param {Element} source : 댓글 내부에 있는 기준 DOM 요소 (editButton, saveButton, commentItem,..)
     * @return {{
     *     commentItem: Element|null,
     *     bodyElement: Element|null,
     *     bodyTextElement: Element|null,
     *     actionsElement: Element|null
     * }}
     */
    const getCommentElements = (source) => {
        const commentItem = source.matches('[data-comment-id]')
            ? source
            : source.closest('[data-comment-id]');

        return {
            commentItem,
            // 현재 댓글의 전체 본문 영역
            bodyElement: commentItem?.querySelector(
                '[data-comment-body]',
            ) ?? null,
            // 전체 본문 안에서 실제 수정 대상 텍스트
            bodyTextElement: commentItem?.querySelector(
                '[data-comment-body-text]',
            ) ?? null,
            // 현재 댓글의 답글|수정|삭제 버튼 영역
            actionsElement: commentItem?.querySelector(
                '[data-comment-actions]',
            ) ?? null,
        };
    };

    /**
     * 댓글 수정 화면을 닫고 원래 댓글 화면으로 복구
     *
     * @param {Element} commentItem 수정 중인 댓글 <li> 요소
     * @return {void}
     */
    const closeCommentEditor = (commentItem) => {
        const { bodyElement, actionsElement } = getCommentElements(commentItem);

        const editorElement = commentItem.querySelector(
            '[data-comment-editor]',
        );

        // 템플릿에서 복제했던 수정 입력창과 버튼 전체를 제거
        editorElement?.remove();

        // 기존 댓글 본문을 다시 화면에 표시
        if (bodyElement) {
            bodyElement.hidden = false;
        }

        // 수정 중 숨겼던 댓글 액션 영역을 다시 표시
        if (actionsElement) {
            actionsElement.hidden = false;
        }
    };

    /**
     * 댓글 수정 UI 생성
     *
     * @param {string} updateUrl 댓글 수정 URL
     * @param {string} body 기존 댓글 본문
     * @return {{
     *     editorElement: HTMLElement,
     *     editInput: HTMLTextAreaElement
     * }} 생성된 댓글 편집 요소
     */
    const createCommentEditor = (updateUrl, body) => {
        if (!(editTemplate instanceof HTMLTemplateElement)) {
            throw new Error('댓글 수정 템플릿을 찾을 수 없습니다.');
        }

        /*
         * 고정 UI는 Blade 템플릿에서 복제하고, 댓글마다 달라지는
         * 수정 URL과 기존 본문만 JavaScript에서 안전하게 반영한다.
         */
        const editorElement = editTemplate.content
            .firstElementChild
            ?.cloneNode(true);

        if (!(editorElement instanceof HTMLElement)) {
            throw new Error('댓글 수정 영역을 생성하지 못했습니다.');
        }

        editorElement.dataset.commentUpdateUrl = updateUrl;

        // 생성한 편집 영역 안에서 실제 입력창을 탐색
        const editInput = editorElement.querySelector(
            '[data-comment-edit-input]',
        );

        if (!(editInput instanceof HTMLTextAreaElement)) {
            throw new Error('댓글 수정 입력창을 생성하지 못했습니다.');
        }

        // value로 기존 본문을 넣어 댓글 내용이 HTML로 해석되지 않게 한다.
        editInput.value = body;

        return {
            editorElement,
            editInput,
        };
    };

    /**
     * 선택한 댓글을 인라인 수정 화면으로 변경
     *
     * @param {Element} editButton 사용자가 클릭한 수정 버튼
     * @return {void}
     */
    const openCommentEditor = (editButton) => {
        const {
            commentItem,
            bodyElement,
            bodyTextElement,
            actionsElement,
        } = getCommentElements(editButton);
        const updateUrl = editButton.dataset.commentUpdateUrl;

        // 편집에 필요한 값이 없으면 중단
        if (
            !commentItem
            || !updateUrl
            || !bodyElement
            || !bodyTextElement
        ) {
            return;
        }

        // 실제 편집창 DOM을 기준으로 동시 수정 여부 확인
        if (section.querySelector('[data-comment-editor]')) {
            window.alert('수정 중인 댓글을 먼저 저장하거나 취소해주세요.');

            return;
        }

        // 기존 본문과 수정 URL을 이용한 편집 UI 생성
        const { editorElement, editInput } = createCommentEditor(
            updateUrl,
            bodyTextElement.textContent ?? '',
        );

        // 기존 본문 바로 다음 위치에 편집 영역을 추가
        bodyElement.insertAdjacentElement('afterend', editorElement);

        // 수정 중에는 기존 본문과 댓글 액션 영역을 숨김
        bodyElement.hidden = true;

        if (actionsElement) {
            actionsElement.hidden = true;
        }

        // 생성 단계에서 보장된 textarea에 포커스를 배치
        editInput.focus();
        editInput.setSelectionRange(
            editInput.value.length,
            editInput.value.length,
        );
    };

    /**
     * 수정 입력창의 본문을 서버에 저장
     *
     * @param {Element} saveButton 사용자가 클릭한 저장 버튼
     * @return {Promise<void>}
     */
    const saveCommentEdit = async (saveButton) => {
        const {
            commentItem,
            bodyElement,
            bodyTextElement,
        } = getCommentElements(saveButton);
        const editorElement = saveButton.closest('[data-comment-editor]');
        const editInput = editorElement?.querySelector(
            '[data-comment-edit-input]',
        );
        const updateUrl = editorElement?.dataset.commentUpdateUrl;

        // 저장과 화면 반영에 필요한 요소가 없으면 중단
        if (
            !commentItem
            || !editorElement
            || !bodyElement
            || !bodyTextElement
            || !updateUrl
            || !(editInput instanceof HTMLTextAreaElement)
        ) {
            return;
        }

        // 서버의 SaveTipCommentRequest와 같이 앞뒤 공백을 제거
        const body = editInput.value.trim();

        if (body === '') {
            window.alert('댓글 내용을 입력해주세요.');
            editInput.focus();

            return;
        }

        // 저장 버튼을 비활성화해 같은 요청의 중복 전송을 방지
        saveButton.disabled = true;

        try {
            // 로그인 세션과 CSRF 토큰을 포함한 댓글 수정 요청 전송
            const response = await fetch(updateUrl, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ body }),
            });

            // 성공과 검증 실패 응답을 모두 JSON으로 읽기
            const payload = await response
                .json()
                .catch(() => ({}));

            if (!response.ok) {
                // FormRequest의 본문 검증 메시지가 있으면 우선 표시
                throw new Error(
                    payload.errors?.body?.[0]
                    || '댓글을 수정하지 못했습니다.',
                );
            }

            // 수정된 본문을 textContent로 반영해 HTML 실행을 방지
            bodyTextElement.textContent = typeof payload.body === 'string'
                ? payload.body
                : body;

            // 저장 성공 후 편집창 제거 및 기존 화면 복구
            closeCommentEditor(commentItem);
        } catch (error) {
            const message = error instanceof Error
                ? error.message
                : '댓글을 수정하지 못했습니다.';

            window.alert(message);

            // 저장 재시도를 위한 편집창과 입력값 유지
            saveButton.disabled = false;
        }
    };

    /**
     * 비동기로 교체되는 댓글 수정 버튼을 처리
     *
     * 목록 내부 요소에 직접 이벤트를 등록하지 않고,
     * 교체되지 않는 section에서 이벤트를 위임수신
     */
    section.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        // 댓글 수정 버튼 클릭 시 해당 댓글 자리에 편집창 표시
        const editButton = event.target.closest('[data-comment-edit]');

        if (editButton) {
            openCommentEditor(editButton);

            return;
        }

        // 취소 버튼 클릭 시 저장 없이 기존 댓글 화면 복구
        const cancelButton = event.target.closest(
            '[data-comment-edit-cancel]',
        );

        if (cancelButton) {
            const commentItem = cancelButton.closest('[data-comment-id]');

            if (commentItem) {
                closeCommentEditor(commentItem);
            }

            return;
        }

        // 저장 버튼을 누르면 수정한 본문을 서버에 전송
        const saveButton = event.target.closest('[data-comment-edit-save]');

        if (saveButton) {
            saveCommentEdit(saveButton);
        }
    });
};
