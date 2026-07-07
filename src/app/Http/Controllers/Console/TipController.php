<?php

namespace App\Http\Controllers\Console;

use App\Actions\Tips\SaveTip;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tips\SaveTipRequest;
use App\Models\Tip;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Stevebauman\Purify\Facades\Purify;

class TipController extends Controller
{
    public function __invoke(): View
    {
        $latestUpdatedAt = Tip::query()
            ->latest('updated_at')
            ->value('updated_at');

        return view('console.tips.index', [
            'tipsTotal' => Tip::query()->count(),
            'latestTipUpdatedDate' => $latestUpdatedAt
                ? Carbon::parse($latestUpdatedAt)->toDateString()
                : null,
        ]);
    }

    public function create(): View
    {
        return $this->editView(new Tip([
            'title' => '',
            'content' => '',
            'status' => Tip::STATUS_DRAFT,
            'audience' => Tip::AUDIENCE_PRIVATE,
        ]), 'create');
    }

    public function edit(Tip $tip): View
    {
        $tip->loadMissing('thumbnail');

        return $this->editView($tip, 'edit');
    }

    private function editView(Tip $tip, string $mode): View
    {
        $isCreate = $mode === 'create';

        return view('console.tips.edit', [
            'tip' => $tip,
            'mode' => $mode,
            'isCreate' => $isCreate,
            'pageTitle' => $isCreate ? 'Tip 추가' : 'Tip 수정',
            'pageDescription' => $isCreate ? '새 팁을 작성합니다.' : null,
        ]);
    }

    /**
     * 콘솔에서 새 팁 저장
     */
    public function store(SaveTipRequest $request, SaveTip $saveTip): RedirectResponse
    {
        $data = $this->sanitizeTipData($request->validated());

        $tip = $saveTip(
            author: $request->user(),
            tip: new Tip,
            data: $data,
            thumbnail: $request->file('thumbnail'),
            deleteThumbnail: $request->boolean('delete_thumbnail'),
        );

        return redirect()
            ->route('console.tips.edit', $tip)
            ->with('status', '팁이 저장되었습니다.');
    }

    /**
     * 콘솔에서 기존 팁을 수정
     */
    public function update(SaveTipRequest $request, Tip $tip, SaveTip $saveTip): RedirectResponse
    {
        $data = $this->sanitizeTipData($request->validated());

        $tip = $saveTip(
            author: $request->user(),
            tip: $tip,
            data: $data,
            thumbnail: $request->file('thumbnail'),
            deleteThumbnail: $request->boolean('delete_thumbnail'),
        );

        return redirect()
            ->route('console.tips.edit', $tip)
            ->with('status', '팁이 수정되었습니다.');
    }

    /**
     * 검증된 팁 저장 데이터를 DB 저장 전에 정화한다.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeTipData(array $data): array
    {
        $data['content'] = Purify::config('tip_content')->clean((string) $data['content']);

        return $data;
    }
}
