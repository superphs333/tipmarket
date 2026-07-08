<?php

namespace App\Http\Controllers;

use App\Actions\Tips\ToggleTipLike;
use App\Models\Tip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TipLikeController extends Controller
{
    /**
     * 팁 좋아요 상태 변경
     */
    public function toggle(Request $request, Tip $tip, ToggleTipLike $toggleTipLike): RedirectResponse
    {
        $liked = $toggleTipLike($tip, $request->user());

        return back()->with(
            'status',
            $liked ? '좋아요를 눌렀습니다.' : '좋아요를 취소했습니다.',
        );
    }
}
