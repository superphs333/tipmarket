/**
 * 로그인 사용자의 원댓글 등록 기능을 초기화
 *
 * @param {{
 *     section: Element,
 *     storeUrl?: string,
 *     loadComments: (options?: {
 *         focusCommentId?: number|string|null
 *     }) => Promise<void>
 * }} options 등록 폼과 목록 갱신에 필요한 값
 * @return {void}
 */
export const initializeCommentCreation = ({
    section,
    storeUrl,
    loadComments,
}) => {
    /**
     * 댓글 등록 폼에서 사용할 DOM 요소
     *
     * 비회원 화면에는 폼 관련 요소가 없으므로 null가능
     */
    const form = section.querySelector('[data-comment-form]');
    const bodyInput = form?.querySelector('[data-comment-body]');
    const submitButton = form?.querySelector('[data-comment-submit]');
    const errorElement = form?.querySelector('[data-comment-error]');

    // 댓글 등록 요청의 중복 실행을 방지
    let isSubmitting = false;

    /**
     * 댓글 작성 오류 메시지를 표시
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
     * 기존 댓글 작성 오류 메시지를 제거
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

    // 로그인 사용자에게만 존재하는 댓글 작성 폼을 초기화
    if (
        !form
        || !(bodyInput instanceof HTMLTextAreaElement)
        || !(submitButton instanceof HTMLButtonElement)
        || !storeUrl
    ) {
        return;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        // 등록 요청이 진행 중이면 추가 제출을 무시
        if (isSubmitting) {
            return;
        }

        clearFormError();

        // 공백만 입력한 경우 서버 요청 전에 바로 안내
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
             * FormData에는 body와 @csrf가 만든 _token이 포함됨
             * Content-Type은 브라우저가 자동으로 설정하므로 직접 지정하지 않음
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

            // 성공과 검증 실패 응답을 모두 JSON으로 읽기
            const payload = await response
                .json()
                .catch(() => ({}));

            if (!response.ok) {
                // FormRequest의 body 검증 메시지를 우선 표시
                if (
                    response.status === 422
                    && Array.isArray(payload.errors?.body)
                ) {
                    showFormError(payload.errors.body[0]);

                    return;
                }

                // 로그인 세션 또는 CSRF 토큰 만료 상황
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

            // DB 등록이 성공한 뒤에만 입력창을 초기화
            form.reset();

            /**
             * 최신순이므로 새 댓글은 항상 첫 페이지에 있음
             * 기본 indexUrl로 목록을 다시 불러와 목록과 댓글 수를 동기화
             */
            await loadComments({
                focusCommentId: payload.comment_id ?? null,
            });
        } catch {
            showFormError(
                '네트워크 오류가 발생했습니다. 잠시 후 다시 시도해주세요.',
            );
        } finally {
            // 성공과 실패 여부에 관계없이 제출 상태를 복구
            isSubmitting = false;
            submitButton.disabled = false;
        }
    });
};
