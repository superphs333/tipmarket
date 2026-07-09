<?php

namespace App\Http\Controllers;

use App\Actions\Tips\SaveTip;
use App\Http\Requests\Tips\SaveTipRequest;
use App\Models\Tip;
use App\Services\Tips\TipViewService;
use App\View\Actions\TipActionSet;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TipController extends Controller
{
    public function create(): View
    {
        return view('tips.create', [
            'tip' => new Tip([
                'title' => '',
                'content' => '',
                'status' => Tip::STATUS_DRAFT,
                'audience' => Tip::AUDIENCE_PRIVATE,
            ]),
        ]);
    }

    public function store(SaveTipRequest $request, SaveTip $saveTip): RedirectResponse
    {
        $tip = $saveTip(
            author: $request->user(),
            tip: new Tip,
            data: $request->validated(),
            thumbnail: $request->file('thumbnail'),
            deleteThumbnail: $request->boolean('delete_thumbnail'),
        );

        return redirect()
            ->route('tips.show', $tip)
            ->with('status', '팁이 저장되었습니다.');
    }

    // 팁 상세 페이지 표시
    public function show(Request $request, Tip $tip, TipViewService $tipViewService): View
    {
        // 조회수 기록
        $tipViewService->record($tip, $request);

        $tip->load([
            'user.profileAvatar',
            'thumbnail',
            'category',
            'tags',
        ]);

        return view('tips.show', [
            'tip' => $tip,
            'tipActions' => TipActionSet::show($tip),
        ]);
    }

    public function edit(Tip $tip): View
    {
        $tip->loadMissing('thumbnail');

        return view('tips.edit', [
            'tip' => $tip,
        ]);
    }

    public function update(SaveTipRequest $request, Tip $tip, SaveTip $saveTip): RedirectResponse
    {
        $tip = $saveTip(
            author: $request->user(),
            tip: $tip,
            data: $request->validated(),
            thumbnail: $request->file('thumbnail'),
            deleteThumbnail: $request->boolean('delete_thumbnail'),
        );

        return redirect()
            ->route('tips.show', $tip)
            ->with('status', '팁이 수정되었습니다.');
    }

    public function destroy(Tip $tip): RedirectResponse
    {
        $tip->delete();

        return redirect()
            ->route('home')
            ->with('status', '팁이 삭제되었습니다.');
    }
}
