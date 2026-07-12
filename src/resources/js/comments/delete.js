import { getCsrfToken } from './http.js';

/**
 * 로그인 사용자의 댓글 삭제 기능을 초기화
 *
 * @param {{
 *     section: Element,
 *     loadComments: () => Promise<void>
 * }} options 삭제 요청과 목록 갱신에 필요한 값
 * @return {void}
 */
export const initializeCommentDeletion = ({
    section,
    loadComments,
}) => {
    // 같은 댓글에 삭제 요청이 중복 전송되지 않도록 처리 중인 ID를 보관
    const deletingCommentIds = new Set();

    /**
     * 댓글 삭제 요청 및 목록 갱신
     *
     * @param {HTMLButtonElement} deleteButton 삭제 버튼
     * @param {string} commentId 삭제할 댓글 ID
     * @param {string} deleteUrl 댓글 삭제 URL
     * @return {Promise<void>}
     */
    const deleteComment = async (deleteButton, commentId, deleteUrl) => {
        deletingCommentIds.add(commentId);
        deleteButton.disabled = true;

        try {
            const response = await fetch(deleteUrl, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`댓글 삭제 실패: ${response.status}`);
            }

            await loadComments();
        } catch {
            window.alert('댓글을 삭제하지 못했습니다. 잠시 후 다시 시도해주세요.');
            deleteButton.disabled = false;
        } finally {
            deletingCommentIds.delete(commentId);
        }
    };

    /**
     * 비동기로 교체되는 댓글 삭제 버튼을 처리
     *
     * 목록 내부 요소에 직접 이벤트를 등록하지 않고,
     * 교체되지 않는 section에서 이벤트를 위임수신
     */
    section.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const deleteButton = event.target.closest('[data-comment-delete]');

        if (!(deleteButton instanceof HTMLButtonElement)) {
            return;
        }

        const commentItem = deleteButton.closest('[data-comment-id]');
        const commentId = commentItem?.dataset.commentId;
        const deleteUrl = deleteButton.dataset.commentDeleteUrl;

        if (
            !commentId
            || !deleteUrl
            || deletingCommentIds.has(commentId)
            || !window.confirm('댓글을 삭제하시겠습니까?')
        ) {
            return;
        }

        deleteComment(deleteButton, commentId, deleteUrl);
    });
};
