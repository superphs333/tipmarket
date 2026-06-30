<?php

namespace App\Actions\Tips;

use App\Data\Tips\TipDraftData;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * AI가 생성한 TipDraftData 목록을 실제 Tip draft 레코드들로 저장한다.
 *
 * 팁 1개 저장의 세부 로직은 CreateTip에 맡기고,
 * 여기서는 AI가 만든 여러 초안을 한 번에 저장하는 흐름만 조율한다.
 */
final class CreateAiGeneratedTips
{
    public function __construct(
        private readonly CreateTip $createTip,
    ) {}

    /**
     * AI 생성 초안 여러 개를 draft 팁으로 저장한다.
     *
     * @param  array<int, TipDraftData>  $drafts
     * @return array<int, Tip>
     */
    public function __invoke(User $author, array $drafts): array
    {
        return DB::transaction(function () use ($author, $drafts): array {
            return collect($drafts)
                ->map(fn (TipDraftData $draft): Tip => ($this->createTip)($author, $draft))
                ->values()
                ->all();
        });
    }
}
