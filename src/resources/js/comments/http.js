/**
 * Laravel CSRF 토큰 조회
 *
 * @return {string} CSRF 토큰 또는 빈 문자열
 */
export const getCsrfToken = () => (
    document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? ''
);
