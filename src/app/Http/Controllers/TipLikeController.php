<?php

namespace App\Http\Controllers;

use App\Actions\Tips\ToggleTipLike;
use App\Models\Tip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TipLikeController extends Controller
{
    /**
     * 팁 좋아요 상태 변경
     */
    public function toggle(Request $request, Tip $tip, ToggleTipLike $toggleTipLike): JsonResponse|RedirectResponse
    {
        $liked = $toggleTipLike($tip, $request->user());

        $tip->refresh();

        if ($request->expectsJson()) {
            return response()->json([
                'name' => 'like',
                'active' => $liked,
                'count' => $tip->like_count,
                'label' => $liked ? '좋아요 취소' : '좋아요',
                'visible_label' => '좋아요',
            ]);
        }

        return back()->with(
            'status',
            $liked ? '좋아요를 눌렀습니다.' : '좋아요를 취소했습니다.',
        );
    }
}
