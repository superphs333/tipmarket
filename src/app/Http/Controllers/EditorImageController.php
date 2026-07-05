<?php

namespace App\Http\Controllers;

use App\Enums\MediaCollection;
use App\Http\Requests\UploadEditorImageRequest;
use App\Services\Media\MediaStorageService;
use Illuminate\Http\JsonResponse;

class EditorImageController extends Controller
{
    /**
     * Summernote 에디터에서 선택한 이미지를 임시 미디어로 업로드
     *
     * [처리흐름]
     * 1. FormRequest에서 이미지 파일 검증
     * 2. MediaStorageService로 r2/s3/ 저장소에 업로드
     * 3. media 테이블에는 temporary 상태로 저장
     * 4. 에디터가 본문에 삽입할 public URL 반환
     */
    public function __invoke(
        UploadEditorImageRequest $request,
        MediaStorageService $mediaStorage,
    ): JsonResponse {
        $file = $request->file('image');

        $media = $mediaStorage->store(
            file: $file,
            collection: MediaCollection::TipBody,
            uploadedBy: $request->user(),
            owner: null,
            metadata: [
                'source' => 'summernote',
            ],
        );

        return response()->json([
            'id' => $media->id,
            'url' => $mediaStorage->url($media),
            'alt' => $media->original_name,
        ]);
    }
}
