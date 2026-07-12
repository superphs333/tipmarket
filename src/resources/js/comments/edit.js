import { getCsrfToken } from './http.js';

/**
 * 로그인 사용자의 댓글 인라인 수정 기능을 초기화
 *
 * @param {{section: Element}} options 댓글 수정 버튼을 포함하는 댓글 섹션
 * @return {void}
 */
export const initializeCommentEditing = ({ section }) => {
    /**
     * 댓글 수정 화면을 닫고 원래 댓글 화면으로 복구
     *
     * @param {Element} commentItem 수정 중인 댓글 <li> 요소
     * @return {void}
     */
    const closeCommentEditor = (commentItem) => {
        // 현재 댓글 안에서 JavaScript가 생성한 편집 영역을 탐색
        const editorElement = commentItem.querySelector(
            '[data-comment-editor]',
        );

        // 현재 댓글의 기존 본문 요소를 탐색
        const bodyElement = commentItem.querySelector(
            '[data-comment-body]',
        );

        // 현재 댓글의 댓글·수정·삭제 버튼 영역을 탐색
        const actionsElement = commentItem.querySelector(
            '[data-comment-actions]',
        );

        // 생성했던 수정 입력창과 취소·저장 버튼 전체를 제거
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
     *     editorElement: HTMLDivElement,
     *     editInput: HTMLTextAreaElement
     * }} 생성된 댓글 편집 요소
     */
    const createCommentEditor = (updateUrl, body) => {
        // 수정 입력창과 버튼을 감쌀 편집 영역을 생성
        const editorElement = document.createElement('div');
        editorElement.className = 'tip-show__comment-editor';
        editorElement.dataset.commentEditor = '';
        editorElement.dataset.commentUpdateUrl = updateUrl;

        // 사용자 본문은 넣지 않고 고정된 편집 UI만 생성
        editorElement.innerHTML = `
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
        `;

        // 생성한 편집 영역 안에서 실제 입력창을 탐색
        const editInput = editorElement.querySelector(
            '[data-comment-edit-input]',
        );

        if (!(editInput instanceof HTMLTextAreaElement)) {
            throw new Error('댓글 수정 입력창 생성 실패');
        }

        // HTML 실행 방지를 위해 textContent로 읽은 기존 본문 입력
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
        // 수정 버튼에서 가장 가까운 댓글 <li data-comment-id>를 탐색
        const commentItem = editButton.closest(
            '[data-comment-id]',
        );
        const updateUrl = editButton.dataset.commentUpdateUrl;

        // 현재 댓글 안에서 기존 본문 요소를 탐색
        const bodyElement = commentItem?.querySelector(
            '[data-comment-body]',
        );

        // 현재 댓글 안에서 댓글·수정·삭제 버튼 영역을 탐색
        const actionsElement = commentItem?.querySelector(
            '[data-comment-actions]',
        );

        // 편집에 필요한 값이 없으면 중단
        if (
            !commentItem
            || !updateUrl
            || !bodyElement
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
            bodyElement.textContent ?? '',
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
        // 저장 버튼이 속한 댓글과 편집 영역을 탐색
        const commentItem = saveButton.closest('[data-comment-id]');
        const editorElement = saveButton.closest('[data-comment-editor]');
        const bodyElement = commentItem?.querySelector('[data-comment-body]');
        const editInput = editorElement?.querySelector(
            '[data-comment-edit-input]',
        );
        const updateUrl = editorElement?.dataset.commentUpdateUrl;

        // 저장과 화면 반영에 필요한 요소가 없으면 중단
        if (
            !commentItem
            || !editorElement
            || !bodyElement
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
            bodyElement.textContent = typeof payload.body === 'string'
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
