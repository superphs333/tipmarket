<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Tip;
use App\Queries\Tips\TipListQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * 프론트 Tip 검색 페이지 처리
 */
class TipSearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $categories = Category::query()
            ->forSelect()
            ->get();

        $tips = TipListQuery::make()
            ->paginate([
                'category_id' => $request->query('category'),
                'tag_names' => $request->query('tag_names', []),
                'keyword' => $request->query('query'),
                'status' => Tip::STATUS_PUBLISHED,
                'audience' => Tip::AUDIENCE_PUBLIC,
            ], 12)
            ->withQueryString();

        return view('tips.search', [
            'categories' => $categories,
            'tips' => $tips,
        ]);
    }
}
