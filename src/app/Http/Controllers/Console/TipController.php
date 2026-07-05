<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\Tip;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

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
            'allow_comments' => true,
        ]), 'create');
    }

    public function edit(Tip $tip): View
    {
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
}
