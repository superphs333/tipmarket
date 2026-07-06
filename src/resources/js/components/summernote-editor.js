import $ from 'jquery';
import 'summernote/dist/summernote-lite.css';
import 'summernote/dist/summernote-lite.js';

window.$ = window.jQuery = $;

function initSummernoteEditors(){
    $('[data-summernote-editor]').each(function(){
        const $editor = $(this);

        // 이미 summernote 적용된 요소라면 다시 초기화 하지 않음
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
            /**
             * 이미지 업로드
             */
            callbacks : {
                // summernote에서 이미지가 선택되면 호출됨.
                // @param {FileList|File[]} files 사용자가 선택하거나 드래그한 이미지 파일 목록 
                onImageUpload: async function (files) {
                    const failedFileNames = [];

                    for(const file of files){
                        try{
                            const uploaded = await uploadEditorImage(file, $editor);
                            // 업로드 성공한 media id를 hidden input에 누적
                            appendUploadedImageInput($editor, uploaded.id);
                            $editor.summernote('insertImage',uploaded.url, function($images){
                                // 화면 표시와 접근성을 위한 alt 
                                $images.attr('alt',uploaded.alt || '');
                                // 글 저장 시 실제 사용된 media를 찾기 위한 서버 측 식별자
                                $images.attr('data-media-id', uploaded.id);
                            });
                        }catch(error){
                            console.error(error);
                            
                            failedFileNames.push(file.name || '이름 없는 파일')
                        }
                    }

                    if(failedFileNames.length>0){
                        alert([
                            '일부 이미지 업로드에 실패했습니다.',
                            '',
                            ...failedFileNames.map((name)=>`- ${name}`)
                        ].join('\n'));
                    }
                }
            }
        });
    });
}

/**
 * CSRF 토큰 추출
 * : Laravel의 VerifyCrfToken 미들웨어를 통과하려면 X-CSRF-TOKEN 헤더에 현재 페이지의 csrf-toekn값을 넣어야 함
 * 
 * @returns {string}
 */
function csrfToken(){
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

/**
 * 업로드 성공한 본문 이미지 media id를 hidden input으로 추가
 * 
 * @param {JQuery<HTMLElement>} $editor Summernote가 연결된 textarea
 * @param {number|string} mediaId 서버가 반환한 media id
 */
function appendUploadedImageInput($editor, mediaId){
    // 한 화면에 에디터가 여러개 생겨도 서로 hidden input이 섞이지 않기 위함
    const wrapper = $editor.closest('[data-summernote-wrapper]')[0];

    if (!wrapper) {
        throw new Error('Summernote wrapper is missing.');
    }

    // 업로드된 이미지 id hidden input들을 담을 컨테이너를 찾기
    const inputContainer = wrapper.querySelector('[data-uploaded-image-inputs]');

    if(!inputContainer){
        throw new Error('Uploaded image input container is missing.');
    }

    /**
     * hidden input name을 textarea의 data속성에서 읽기 
     * 
     * - 기본값은 uploaded_body_image_ids[]
     * +) 나중에 다른 에디터에서 다른 이름을 써야 하면 Blade에서 data속성만 바꾸면 됨
     */
    const inputName = $editor.data('uploaded-image-input-name') || 'uploaded_body_image_ids[]';

    // 문자열로 통일
    const value = String(mediaId);

    // 같은 media id가 이미 hidden input으로 들어가 있는지 확인.
    const alreadyExists = Array.from(inputContainer.querySelectorAll('input[type="hidden"]'))
        .some((input) => input.value === value);
    if(alreadyExists) return;

    // 실제 서버로 전송될 input을 만든다. 
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = inputName;
    input.value = value;

    inputContainer.appendChild(input);
}

/**
 * Summernote에서 선택한 이미지 파일 1개를 서버에 업로드
 */
async function uploadEditorImage(file, $editor){
    const uploadUrl = $editor.data('upload-url');
    if(!uploadUrl) throw new Error('Editor image upload URL is missing');

    const formData = new FormData();
    formData.append('image', file);

    const response  = await fetch(uploadUrl, {
        method : 'POST',
        headers : {
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json',
        },
        body : formData,
    });

    if(!response.ok) throw new Error('Image upload failed.');

    return response.json();
}



// 일반 페이지 최초 로드 시 실행
document.addEventListener('DOMContentLoaded', initSummernoteEditors);

document.addEventListener('livewire:navigated', initSummernoteEditors);