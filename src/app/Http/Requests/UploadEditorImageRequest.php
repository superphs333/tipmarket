<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UploadEditorImageRequest extends FormRequest
{
    /**
     * 에디터 본문에 삽입할 이미지 업로드 요청을 검증한다.
     *
     * 이미지는 글 저장 전에 먼저 업로드되므로, 여기서는 파일 자체의 안전성만 확인한다.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'image' => [
                'required',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(5 * 1024),
            ],
        ];
    }
}
