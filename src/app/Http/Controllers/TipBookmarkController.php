<?php

namespace App\Http\Controllers;

use App\Actions\Tips\ToggleTipBookmark;
use App\Models\Tip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TipBookmarkController extends Controller
{
    /**
     * 팁 북마크 상태 변경
     */
    public function toggle(Request $request, Tip $tip, ToggleTipBookmark $toggleTipBookmark): JsonResponse|RedirectResponse
    {
        $bookmarked = $toggleTipBookmark($tip, $request->user());

        $tip->refresh();

        if ($request->expectsJson()) {
            return response()->json([
                'name' => 'bookmark',
                'active' => $bookmarked,
                'count' => $tip->bookmark_count,
                'label' => $bookmarked ? '북마크 취소' : '북마크',
                'visible_label' => '북마크',
            ]);
        }

        return back()->with(
            'status',
            $bookmarked ? '북마크했습니다.' : '북마크를 취소했습니다.',
        );
    }
}
