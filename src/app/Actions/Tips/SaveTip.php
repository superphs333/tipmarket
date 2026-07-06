<?php

namespace App\Actions\Tips;

use App\Actions\Tags\FindOrCreateTags;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * 팁 작성/수정 폼 입력을 실제 Tip 레코드와 태그 연결에 반영한다.
 *
 * 이 Action은 콘솔과 프론트 컨트롤러가 함께 사용할 수 있도록
 * HTTP 요청/응답, redirect, 권한 판단을 알지 않는다.
 *
 * 책임 범위:
 * - 새 팁이면 작성자 user_id 지정
 * - 검증된 폼 입력을 tips 테이블에 저장
 * - 태그명을 실제 tag id 목록으로 변환
 * - tag_tip pivot 연결을 최신 입력 기준으로 동기화
 */
final class SaveTip
{
    public function __construct(
        private readonly FindOrCreateTags $findOrCreateTags,
        private readonly AttachTipThumbnail $attachTipThumbnail,
        private readonly AttachTipBodyImages $attachTipBodyImages,
    ) {}

    /**
     * 검증된 팁 폼 데이터를 저장하고 저장된 Tip 모델을 반환한다.
     *
     * @param  array{
     *     title: string,
     *     content: string,
     *     category_id?: int|string|null,
     *     status: string,
     *     audience: string,
     *     tag_names?: array<int, string>|null,
     *     uploaded_body_image_ids?: array<int, int|string>|null
     * }  $data
     */
    public function __invoke(
        User $author,
        Tip $tip,
        array $data,
        ?UploadedFile $thumbnail = null,
        bool $deleteThumbnail = false,
    ): Tip {
        return DB::transaction(function () use ($author, $tip, $data, $thumbnail, $deleteThumbnail): Tip {
            // 새 팁은 아직 작성자가 없으므로 저장 직전에 현재 요청 사용자를 소유자로 지정한다.
            // 기존 팁 수정 시에는 작성자를 덮어쓰지 않아 소유권 변경을 방지한다.
            if (! $tip->exists) {
                $tip->user_id = $author->id;
            }

            $tip->fill([
                'category_id' => $this->normalizeCategoryId($data['category_id'] ?? null),
                'title' => $data['title'],
                'content' => $data['content'],
                'status' => $data['status'],
                'audience' => $data['audience'],
            ]);

            $tip->save();

            // 본문 이미지
            ($this->attachTipBodyImages)(
                tip: $tip,
                uploadedBy: $author,
                content: $data['content'],
                uploadedBodyImageIds: $data['uploaded_body_image_ids'] ?? [],
            );

            // 썸네일
            ($this->attachTipThumbnail)(
                tip: $tip,
                uploadedBy: $author,
                thumbnail: $thumbnail,
                deleteThumbnail: $deleteThumbnail,
            );

            $tagIds = ($this->findOrCreateTags)(
                tagIds: [],
                tagNames: $data['tag_names'] ?? [],
            );

            $tip->tags()->sync($tagIds);

            return $tip->refresh();
        });
    }

    /**
     * 폼과 테스트에서 넘어올 수 있는 카테고리 값을 DB 저장용 nullable integer로 정리한다.
     *
     * Request에서도 같은 정규화를 수행하지만, 이 Action은 콘솔/프론트 컨트롤러가 공유하므로
     * 호출자가 검증된 배열을 직접 구성해 넘기는 경우까지 방어한다.
     */
    private function normalizeCategoryId(int|string|null $categoryId): ?int
    {
        if ($categoryId === null || $categoryId === '') {
            return null;
        }

        return (int) $categoryId;
    }
}
