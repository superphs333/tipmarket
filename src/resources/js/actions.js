// Blade에서 출력한 CSRF meta값 가져오기
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

/**
 * 서버 응답값을 기준으로 버튼 UI를 갱신한다.
 *
 * @param {HTMLElement} button 클릭된 액션 버튼 요소.
 * @param {Object} payload 서버에서 내려온 JSON 응답.
 * @param {boolean} [payload.active] 현재 액션이 활성 상태인지 여부.
 * @param {string} [payload.label] aria-label에 넣을 접근성용 문구.
 * @param {string} [payload.visible_label] 화면에 표시할 label 문구.
 * @param {number|string} [payload.count] 화면에 표시할 카운트 값.
 * @returns {void} 
 */
function updateActionButton(button, payload) {
    // active값이 true/false로 내려온 경우에만 활성 상태 갱신
    if (typeof payload.active === 'boolean') {
        button.classList.toggle('is-active', payload.active);
        // 스크린리더가 토글 버튼의 현재 상태를 알 수 있게 한다.
        button.setAttribute('aria-pressed', payload.active ? 'true' : 'false');
    }
    // 접근성용 label이 내려오면 aria-label을 갱신
    if (payload.label) {
        // 예: 좋아요 -> 좋아요 취소
        button.setAttribute('aria-label', payload.label);
    }

    // 화면에 보이는 label이 내려오면 텍스트를 갱신한다.
    if (payload.visible_label) {
        // 버튼 내부의 label 영역을 찾는다.
        const label = button.querySelector('[data-action-label]');

        if (label) {
            label.textContent = payload.visible_label;
        }
    }

    // count 값이 응답에 포함되어 있으면 숫자를 갱신한다.
    if (typeof payload.count !== 'undefined') {
        const count = button.querySelector('[data-action-count]');

        if (count) {
            count.textContent = payload.count;
        }
    }
}

/**
 * 좋아요/북마크 처럼 서버 상태가 바뀌는 토글 액션을 처리한다. 
 * 
 * @param {HTMLButtonElement} button 클릭된 토글 버튼 요소.
 * 
 * @returns {Promise<void>} fetch 요청과 UI 갱신이 끝나면 resolve된다.
 */
async function toggleAction(button) {
    const endpoint = button.dataset.actionEndpoint; // 요청 url 
    const method = button.dataset.actionMethod || 'POST';

    if (! endpoint) {
        return;
    }

    button.disabled = true; // 요청 중 버튼 비활성화 
    button.classList.add('is-pending');

    try {
        const response = await fetch(endpoint, {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (! response.ok) {
            throw new Error(`Action request failed: ${response.status}`);
        }

        updateActionButton(button, await response.json());
    } catch (error) {
        console.error(error);
    } finally {
        button.disabled = false;
        button.classList.remove('is-pending');
    }
}

/**
 * 공유 액션을 처리한다.
 *
 * @param {HTMLElement} button 클릭된 공유 버튼 요소.
 * @returns {Promise<void>} 공유 또는 클립보드 복사가 끝나면 resolve된다.
 */
async function shareAction(button) {
    const url = button.dataset.actionUrl || window.location.href;
    const title = document.title;

    try {
        if (navigator.share) {
            await navigator.share({ title, url });
            return;
        }

        await navigator.clipboard.writeText(url);
        button.classList.add('is-copied');

        window.setTimeout(() => {
            button.classList.remove('is-copied');
        }, 1200);
    } catch (error) {
        console.error(error);
    }
}

/**
 * 액션 버튼 클릭을 한 곳에서 위임 처리한다.
 *
 * @param {MouseEvent} event 문서 전체에서 발생한 click 이벤트.
 * @returns {void}
 */
document.addEventListener('click', (event) => {
    // 클릭된 요소에서 가장 가까운 액션 요소를 찾는다.
    const button = event.target.closest('[data-action-behavior]');

    if (! button) {
        return;
    }
    // behavior가 toggle이면 좋아요/북마크 토글로 처리한다.
    if (button.dataset.actionBehavior === 'toggle') {
        toggleAction(button);
        return;
    }

    if (button.dataset.actionBehavior === 'share') {
        shareAction(button);
    }
});
