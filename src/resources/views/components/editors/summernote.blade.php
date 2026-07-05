@once
    @vite('resources/js/components/summernote-editor.js')
@endonce

<textarea
    {{-- label의 for 속성, js 선택, 브라우저 접근성에 사용 --}}
    id = "{{ $id }}"
    {{-- form submit 전달 시 서버로 전달되는 필드명 --}}
    name = "{{ $name }}"
    {{-- js에서 이 textarea를 summernote 적용 대상으로 찾기 위한 표식 --}}
    data-summernote-editor
    data-placeholder="{{ $placeholder }}"
    data-height="{{ $height }}"
    {{-- 컴포넌트 사용시 추가로 넘긴 html 속성 받기 --}}
    {{ $attributes->class('tip-edit-control tip-edit-textarea') }}
>{{ old($name, $value) }}</textarea>