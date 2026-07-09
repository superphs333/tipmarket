<?php

namespace App\Actions\Tips;

use App\Models\Tip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ToggleTipBookmark
{
    /**
     * 북마크 상태 토글
     *
     * @return bool true => 북마크, false => 북마크 취소 상태
     */
    public function __invoke(Tip $tip, User $user): bool
    {
        return DB::transaction(function () use ($tip, $user): bool {
            $changes = $tip->bookmarkedUsers()->toggle($user->id);

            if ($changes['attached'] !== []) {
                $tip->increment('bookmark_count');

                return true;
            }

            // 북마크 취소시 0아래로 내려가는 것 방어
            Tip::query()
                ->whereKey($tip->id)
                ->where('bookmark_count', '>', 0)
                ->decrement('bookmark_count');

            return false;
        });
    }
}
