<?php

namespace App\Actions\Tips;

use App\Actions\Tags\FindOrCreateTags;
use App\Data\Tips\TipDraftData;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * TipDraftData 1개를 실제 tips 레코드로 저장하는 공통 Action.
 *
 * AI 생성, 관리자 직접 작성, CSV/import처럼 입력 출처가 달라도
 * 저장 직전에는 TipDraftData 형태로 맞춘 뒤 이 Action을 호출한다.
 */
final class CreateTip
{
    public function __construct(
        private readonly FindOrCreateTags $findOrCreateTags,
    ) {}

    /**
     * 팁 초안 1개를 draft 상태로 저장하고 기존/신규 태그를 연결한다.
     */
    public function __invoke(User $author, TipDraftData $draft): Tip
    {
        return DB::transaction(function () use ($author, $draft): Tip {
            $tip = Tip::query()->create([
                'user_id' => $author->id,
                'category_id' => $draft->categoryId,
                'title' => $draft->title,
                'content' => $draft->content,
                'status' => Tip::STATUS_DRAFT,
                'allow_comments' => true,
            ]);

            // tagIds는 기존 태그, tagNames는 AI나 폼이 제안한 신규 태그명 후보다.
            // FindOrCreateTags가 둘을 합쳐 실제 연결 가능한 tag id 목록으로 변환한다.
            $tagIds = ($this->findOrCreateTags)(
                tagIds: $draft->tagIds,
                tagNames: $draft->tagNames,
            );

            if ($tagIds !== []) {
                $tip->tags()->sync($tagIds);
            }

            return $tip;
        });
    }
}
