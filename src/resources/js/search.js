/**
 * 검색 모달에서 사용하는 DOM 선택자 
 */
const SEARCH_FORM_SELECTOR = '[data-search-form]';
const SEARCH_INPUT_SELECTOR = '[data-search-input]';
const SEARCH_SORT_FORM_SELECTOR = '[data-search-sort-form]';
const SEARCH_SORT_SELECT_SELECTOR = '[data-search-sort-select]';


/**
 * 사용자가 입력한 검색어를 URL에 전달할 수 있는 형태로 정리 
 * : 검색어 앞뒤의 불필요한 공백 제거, 단어 사이 연속으로 입력된 공백을 하나로 합치기
 * 
 * @param {string} value 사용자가 입력한 원본 검색어. 
 * @returns {string} 공백이 정리된 검색어. 
 */
function normalizeSearchQuery(value) {
    return value
        .trim()
        .replace(/\s+/g, ' ');
}

/**
 * 검색 폼의 submit 이벤트 처리
 * 
 * @param {SubmitEvent} event 브라우저에서 발생한 form submit 이벤트.
 * @returns {void}
 */
function handleSearchSubmit(event) {
    const eventTarget = event.target;

    // document 전체에서 submit을 감지하므로 다른 form의 submit은 건드리지 않는다.
    if (! (eventTarget instanceof HTMLFormElement)) {
        return;
    }
    if (! eventTarget.matches(SEARCH_FORM_SELECTOR)) {
        return;
    }

    const form = eventTarget;
    const input = form.querySelector(SEARCH_INPUT_SELECTOR);

    if (! (input instanceof HTMLInputElement)) {
        return;
    }

    const query = normalizeSearchQuery(input.value);

    if(query === ''){
        event.preventDefault();

        input.setCustomValidity('검색어를 입력해주세요.');
        input.reportValidity();
        input.focus();

        return;
    }

    // 오류 상태 제거
    input.setCustomValidity('');
    input.value = query;
    event.preventDefault();

    const searchUrl = new URL(form.action, window.location.origin);
    const parameterName = input.name || 'query';
    searchUrl.searchParams.set(parameterName, query);

    // 최종 형태:
    // /tips/search?query=청소
    window.location.assign(searchUrl.toString());

}

/**
 * 사용자가 검색어를 다시 입력하면 기존 오류 상태를 제거한다.
 *
 * 빈 검색으로 표시된 브라우저 validation 메시지가 정상적인 검색어를
 * 입력한 뒤에도 남아 있지 않도록 input 이벤트에서 초기화한다.
 *
 * @param {InputEvent} event 검색 input에서 발생한 입력 이벤트.
 * @returns {void}
 */
function handleSearchInput(event) {
    const eventTarget = event.target;

    // 다른 input 이벤트는 검색 기능과 관계가 없으므로 처리하지 않는다.
    if (! (eventTarget instanceof HTMLInputElement)) {
        return;
    }

    if (! eventTarget.matches(SEARCH_INPUT_SELECTOR)) {
        return;
    }

    eventTarget.setCustomValidity('');
}

/**
 * 프론트 검색 결과의 정렬값 변경을 처리한다.
 *
 * 선택한 정렬값과 정렬 폼의 hidden 검색 조건을 GET 방식으로 전달한다.
 * requestSubmit()을 사용해 브라우저의 기본 폼 제출 흐름을 유지한다.
 *
 * @param {Event} event 정렬 select에서 발생한 change 이벤트.
 * @returns {void}
 */
function handleSearchSortChange(event) {
    const eventTarget = event.target;

    // document 전체의 change 이벤트 중 검색 결과 정렬 select만 처리한다.
    if (! (eventTarget instanceof HTMLSelectElement)) {
        return;
    }

    if (! eventTarget.matches(SEARCH_SORT_SELECT_SELECTOR)) {
        return;
    }

    const form = eventTarget.closest(SEARCH_SORT_FORM_SELECTOR);

    if (! (form instanceof HTMLFormElement)) {
        return;
    }

    form.requestSubmit();
}

document.addEventListener('submit', handleSearchSubmit);
document.addEventListener('input', handleSearchInput);
document.addEventListener('change', handleSearchSortChange);
