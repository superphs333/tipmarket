<?php

namespace App\Http\Controllers;

use App\Actions\Tips\SaveTip;
use App\Http\Requests\Tips\SaveTipRequest;
use App\Models\Tip;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TipController extends Controller
{
    public function show(Tip $tip): View
    {
        $tip->load([
            'user.profileAvatar',
            'thumbnail',
            'category',
            'tags',
        ]);

        return view('tips.show', [
            'tip' => $tip,
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
