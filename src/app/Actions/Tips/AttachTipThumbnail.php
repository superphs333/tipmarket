<?php

namespace App\Actions\Tips;

use App\Enums\MediaCollection;
use App\Models\Media;
use App\Models\Tip;
use App\Models\User;
use App\Services\Media\MediaStorageService;
use Illuminate\Http\UploadedFile;

/**
 * Tip 썸네일 이미지를 저장, 교체, 삭제
 */
final class AttachTipThumbnail
{
    public function __construct(
        private readonly MediaStorageService $mediaStorage
    ) {}

    /**
     * 업로드된 썸네일 이미지를 Tip에 연결
     *
     * @param  UploadedFile|null  $thumbnail  SaveTipRequest에서 검증된 thumbnail 파일
     */
    public function __invoke(Tip $tip, User $uploadedBy, ?UploadedFile $thumbnail, bool $deleteThumbnail = false): ?Media
    {
        // 삭제 요청이 있거나, 새 파일이 올라온 경우 기존 썸네일 정리
        if ($deleteThumbnail || $thumbnail !== null) {
            $this->deleteExistingThumbnail($tip);
        }

        // 삭제만 요청한 경우
        if ($thumbnail === null) {
            return null;
        }

        // 새 썸네일 파일을 Tip 소유 media로 저장
        return $this->mediaStorage->store(
            file: $thumbnail,
            collection: MediaCollection::TipThumbnail,
            uploadedBy: $uploadedBy,
            owner: $tip,
        );
    }

    /**
     * Tip에 연결된 기존 썸네일 media를 모두 삭제
     */
    private function deleteExistingThumbnail(Tip $tip): void
    {
        Media::query()
            ->where('owner_type', $tip->getMorphClass())
            ->where('owner_id', $tip->getKey())
            ->where('collection', MediaCollection::TipThumbnail->value)
            ->get()
            ->each(function (Media $media): void {
                $this->mediaStorage->delete($media);
            });
    }
}
