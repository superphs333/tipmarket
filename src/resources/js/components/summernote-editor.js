import $ from 'jquery';
import 'summernote/dist/summernote-lite.css';
import 'summernote/dist/summernote-lite.js';

window.$ = window.jQuery = $;

function initSummernoteEditors(){
    $('[data-summernote-editor]').each(function(){
        const $editor = $(this);

        // 이미 summernote 적용된 요소라면 다시 초기화 하지 않음 ~근데 이게 왜 조건이지?
        if($editor.data('summernote')) return;

        // 에디터 초기화
        $editor.summernote({
            height: Number($editor.data('height')) || 420,
            minHeight: 260,
            placeholder: $editor.data('placeholder') || '본문을 입력하세요.',
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture', 'table']],
                ['view', ['codeview']],
            ],
        });
    });
}

// 일반 페이지 최초 로드 시 실행
document.addEventListener('DOMContentLoaded', initSummernoteEditors);

document.addEventListener('livewire:navigated', initSummernoteEditors);