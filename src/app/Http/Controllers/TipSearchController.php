<?php

namespace App\Http\Controllers;

use App\Http\Requests\TipSearchRequest;
use App\Models\Category;
use App\Models\Tip;
use App\Queries\Tips\TipListQuery;
use Illuminate\Contracts\View\View;

/**
 * 프론트 Tip 검색 페이지 처리
 */
class TipSearchController extends Controller
{
    public function __invoke(TipSearchRequest $request): View
    {
        $filters = $request->validated();

        $categories = Category::query()
            ->forSelect()
            ->get();

        $tips = TipListQuery::make()
            ->paginate([
                'category_id' => $filters['category'] ?? null,
                'tag_names' => $filters['tag_names'] ?? [],
                'keyword' => $filters['query'] ?? null,
                'status' => Tip::STATUS_PUBLISHED,
                'audience' => Tip::AUDIENCE_PUBLIC,
                'sort' => $filters['sort'] ?? 'latest',
            ], 12)
            ->withQueryString();

        return view('tips.search', [
            'categories' => $categories,
            'tips' => $tips,
        ]);
    }
}
