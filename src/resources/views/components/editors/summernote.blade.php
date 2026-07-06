@once
    @vite('resources/js/components/summernote-editor.js')
@endonce

<div
    {{-- textarea와 hidden input 영역을 같은 에디터 단위로 묶는다. --}}
    data-summernote-wrapper
>
    <textarea
        {{-- label의 for 속성, js 선택, 브라우저 접근성에 사용 --}}
        id="{{ $id }}"
        {{-- form submit 전달 시 서버로 전달되는 필드명 --}}
        name="{{ $name }}"
        {{-- js에서 이 textarea를 summernote 적용 대상으로 찾기 위한 표식 --}}
        data-summernote-editor
        data-placeholder="{{ $placeholder }}"
        data-height="{{ $height }}"
        {{-- 에디터 이미지 업로드 endpoint --}}
        data-upload-url="{{ route('editor.images.store') }}"
        {{-- 업로드 성공한 media id를 hidden input으로 만들 때 사용할 input name --}}
        data-uploaded-image-input-name="uploaded_body_image_ids[]"
        {{-- 컴포넌트 사용 시 추가로 넘긴 class, required, data-* 같은 HTML 속성을 textarea에 붙인다. --}}
        {{ $attributes->class('tip-edit-control tip-edit-textarea') }}
    >{{ old($name, $value) }}</textarea>

    <div
        {{-- JS가 업로드 성공한 media id를 hidden input으로 누적하는 영역 --}}
        data-uploaded-image-inputs
    ></div>
</div>
