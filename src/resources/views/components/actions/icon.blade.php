{{-- 
    [액션 아이콘 컴포넌트]
    : 액션 이름에 맞는 SVG 아이콘을 출력 
    , 반보되는 액션 아이콘을 한 파일에서 관리하기 위한 공통 컴포넌트.

    입력)
        - name : 출력할 아이콘 이름 
            ex) bookmark, like, comment, share
        - active : 활성 상태 여부
            ex) 좋아요/북마크가 눌린 상태면 true
    출력)
        - actions-item__icon wrapper
        - name에 해당하는 SVG

    렌더링 규칙)
        - active=true => fill="currentColor"로 채워짐
        - active= false => fil="none"으로 비어 보임
        - comment, share등은 단순 outline 아이콘 
--}}
@props([
    'name', // 출력할 아이콘 이름
    'active' => false, // 아이콘 활성 상태 
])

<span class="actions-item__icon" aria-hidden="true">
    @switch($name)
        {{-- 북마크 --}}
        @case('bookmark')
            <svg width="22" height="22" viewBox="0 0 24 24" fill="{{ $active ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2">
                <path d="M6 3h12a1 1 0 0 1 1 1v17l-7-4-7 4V4a1 1 0 0 1 1-1z" />
            </svg>
            @break

        {{-- 좋아요 --}}
        @case('like')
            <svg width="22" height="22" viewBox="0 0 24 24" fill="{{ $active ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
            @break

        {{-- 댓글 --}}
        @case('comment')
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z" />
            </svg>
            @break

        {{-- 공유 --}}
        @case('share')
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M7 17 17 7" />
                <path d="M8 7h9v9" />
            </svg>
            @break
    @endswitch
</span>
