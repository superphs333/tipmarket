<?php

namespace App\Actions\Tips;

use App\Enums\MediaCollection;
use App\Models\Media;
use App\Models\Tip;
use App\Models\User;
use App\Services\Media\MediaStorageService;
use DOMDocument;
use DOMElement;

/**
 * 팁 본문에 삽입된 에디터 이미지를 Tip 모델에 연결하고,
 * 최종 본문에서 빠진 이미지를 정리한다.
 */
final class AttachTipBodyImages
{
    public function __construct(
        private readonly MediaStorageService $mediaStorage,
    ) {}

    /**
     * 본문 이미지 media 상태를 최종 content기준으로 동기화 
     * 
     * [처리기준]
     * - content 안에 data-media-id로 남아 있는 이미지  : Tip 에 연결
     * - 작성/수정 중 업로드됐지만 content에서 빠진 이미지 : 삭제 
     * - 기존 Tip에 연결되어 있었지만 수정 후 content에서 빠진 이미지 : 삭제
     * 
     * @param array<int, int|string> $uploadedBodyImageIds : 작성/수정 중 성공한 본문 이미지 전체 
     */
    public function __invoke(
        Tip $tip,
        User $uploadedBy,
        string $content,
        array $uploadedBodyImageIds = [],
    ): void
    {
        // 작성/수정 중 업로드 성공한 본문 이미지 전체
        $uploadedBodyImageIds = $this->normalizeIds($uploadedBodyImageIds);
        // 최종 content html에 실제로 남아있는 media id를 추출.
        $usedBodyImageIds = $this->extractBodyImageIds($content);  
        
        // 최종 분문에 남아 있는 temporary 이미지를 Tip에 연결
        Media::query()
            ->whereIn('id', $usedBodyImageIds)
            ->where('collection', MediaCollection::TipBody->value)
            ->where('uploaded_by_id', $uploadedBy->id)
            ->where('status', Media::STATUS_TEMPORARY) // 아직 어떤 모델에도 연결되지 않은 임시 파일만 연결
            ->whereNull('owner_type')
            ->whereNull('owner_id')
            ->get()
            ->each(function (Media $media) use ($tip) : void {
                $media->forceFill([
                    // morph 관계에서 사용할 owner_type값
                    'owner_type' => $tip->getMorphClass(),
                    // 현재 저장/수정 중인 Tip의 기본키
                    'owner_id' => $tip->getKey(),
                    // 실제 사용 중인 이미지이므로 attached 상태로 바꿈
                    'status' => Media::STATUS_ATTACHED,
                ])->save();
            });

        // 작성/수정 중 업로드됐지만, 최종 본문에서 빠진 이미지를 찾기
        $unusedUploadedIds = array_values(array_diff($uploadedBodyImageIds, $usedBodyImageIds));
        if($unusedUploadedIds !== []){
            Media::query()
                ->whereIn('id', $unusedUploadedIds)
                ->where('collection', MediaCollection::TipBody->value)
                // 현재 사용자가 업로드한 것만 삭제 (다른 사용자의 media id를 input에 조작해도 삭제되지 않도록)
                ->where('uploaded_by_id', $uploadedBy->id)
                ->where('status', Media::STATUS_TEMPORARY)
                ->whereNull('owner_type')
                ->whereNull('owner_id')
                ->get()
                ->each(function (Media $media) : void {
                    $this->mediaStorage->delete($media);
                });

        }

        // 기존에 이 Tip에 연결되어 있었지만, 수정 후 본문에서 빠진 이미지 삭제 
        Media::query()
            ->where('owner_type', $tip->getMorphClass())
            ->where('owner_id', $tip->getKey())
            ->where('collection', MediaCollection::TipBody->value)
            ->whereNotIn('id', $usedBodyImageIds) // 본문에 남아 있는 이미지는 유지
            ->get()
            ->each(function (Media $media) : void {
                $this->mediaStorage->delete($media);
            });
    }

    /**
     * 요청으로 들어온 media id 목록을 정수 배열로 정리
     * 
     * @param array<int, int|string> $ids
     * @return array<int, int>
     */
    private function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->filter(fn (int|string $id): bool => is_numeric($id))
            ->map(fn (int|string $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * content HTML 안에 남아 있는 본문 이미지 media id를 추출
     * 
     * @return array<int, int>
     */
    private function extractBodyImageIds(string $content): array
    {
        if (trim($content) === '') {
            return [];
        }

        // HTML 문자열을 파싱하기 위한 DOMDocument
        $document = new DOMDocument('1.0', 'UTF-8');
            // 두번째 인자 'UTF-8' => 한글 본문이 깨짖 않게 하기 위한 인코딩 지정
        
        // 내부 오류 수집을 켜고, warning이 화면/로그를 어지럽히지 않게 하기 위해
        $previousUseInternalErrors = libxml_use_internal_errors(true);

        // content를 div로 감싸사 하나의 루트 노드처럼 파싱.
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div>'.$content.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        
        // 파싱 중 수집된 libxml 오류를 비운다
        libxml_clear_errors();

        // 이 메서드 호출 전의 libxml 오류 처리 설정으로 되돌림
        libxml_use_internal_errors($previousUseInternalErrors);

        // 추출한 media id를 임시로 담을 배열 
        $ids = [];

        foreach ($document->getElementsByTagName('img') as $image) {
            if (! $image instanceof DOMElement) {
                continue;
            }

            // 서버가 본문 이미지 식별에 사용하는 속성
            $mediaId = $image->getAttribute('data-media-id');

            // data-media-id가 숫자 형태 아니면, 본문 이미지로 보지 않음. 
            if ($mediaId === '' || ! is_numeric($mediaId)) {
                continue;
            }

            // 숫자 문자열을 정수로 변환해서 비교 기준을 통일 
            $ids[] = (int) $mediaId;
        }

        return array_values(array_unique(array_filter(
            $ids,
            fn (int $id): bool => $id > 0,
        )));
    }
}
