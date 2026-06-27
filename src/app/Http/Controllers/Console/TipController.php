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
}
