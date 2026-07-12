import { initializeCommentCreation } from './comments/create.js';
import { initializeCommentDeletion } from './comments/delete.js';
import { initializeCommentEditing } from './comments/edit.js';
import { initializeCommentList } from './comments/list.js';

/**
 * 팁 상세 화면의 댓글 기능을 초기화
 *
 * 각 기능의 구체적인 DOM 처리와 요청은 기능별 모듈에 두고,
 * 이 진입점에서는 공통 요소를 확인한 뒤 모듈을 조립
 *
 * [초기화 순서]
 * 1. Blade가 전달한 댓글 목록/등록 URL 확인
 * 2. 댓글 목록이 출력될 컨테이너 확인
 * 3. 목록 모듈 초기화 및 loadComments 획득
 * 4. 등록/수정/삭제 모듈 초기화
 * 5. 최초 댓글 목록 조회
 *
 * @param {Element} section 댓글 섹션 (section data-tip-comments 속성을 가진 댓글 전체 영역)
 * @return {void}
 */
const initializeTipComments = (section) => {
    /**
     * Blade가 data 속성으로 전달한 API URL
     *
     */
    const indexUrl = section.dataset.commentsIndexUrl; // 비회원도 사용할 수 있는 댓글 목록 조회 URL
    const storeUrl = section.dataset.commentsStoreUrl; // 로그인 사용자에게만 전달되는 등록 URL

    // 서버가 렌더링한 댓글 목록 HTML이 들어가는 영역
    const listContainer = section.querySelector(
        '[data-comment-list-container]',
    );

    // 목록 조회에 필요한 URL이나 컨테이너가 없으면 초기화를 중단
    if (!indexUrl || !listContainer) {
        return;
    }

    /**
     * 댓글 목록 모듈을 초기화
     */
    const { loadComments } = initializeCommentList({
        section, // 페이지네이션 및 재시도 클릭 이벤트 위임에 사용
        indexUrl, // 기본 댓글 목록 조회 주소
        listContainer, // 조회한 댓글 HTML을 출력할 영역
    });

    /**
     * 댓글 등록 기능 초기화
     */
    initializeCommentCreation({
        section,
        storeUrl,
        loadComments,
    });

    /**
     * 댓글 인라인 수정 기능 초기화
     */
    initializeCommentEditing({ section });

    /**
     * 댓글 삭제 기능을 초기화
     */
    initializeCommentDeletion({
        section,
        loadComments,
    });

    // 상세 화면 진입 시 최신 댓글 첫 페이지를 최초 조회
    loadComments();
};

// 문서에 존재하는 댓글 영역을 각각 초기화
document
    .querySelectorAll('[data-tip-comments]')
    .forEach(initializeTipComments);
