<?php

namespace App\Actions\Tips;

use App\Models\Tip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ToggleTipLike
{
    /**
     * 좋아요 상태 토글
     * 
     * @return bool true => 좋아요, false => 좋아요 취소 상태
     */
    public function __invoke(Tip $tip, User $user) : bool
    {
        return DB::transaction(function () use($tip, $user) : bool {
            $changes = $tip->likedUsers()->toggle($user->id);

            if($changes['attached'] !== []){
                $tip->increment('like_count');
                return true;
            }

            // 좋아요 취소시 0아래로 내려가는 것 방어
            Tip::query()
                ->whereKey($tip->id)
                ->where('like_count', '>', 0)
                ->decrement('like_count');
        
            return false;
        })
    }
}