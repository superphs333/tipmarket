<?php

namespace App\Queries\Comments;

use App\Models\Comment;
use App\Models\Tip;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 팁 상세 화면에 표시할 원댓글 목록 조회
 */
final class TipCommentQuery
{
    // 한 페이지에 표시할 원댓글 수
    public const PER_PAGE = 20;

    // 컨테이너 주입 없이 간단하게 Query 객체를 생성한다.
    public static function make(): self
    {
        return new self;
    }

    /**
     * 지정한 팁의 원댓글을 최신순으로 페이지네이션한다.
     *
     * @return LengthAwarePaginator<int, Comment>
     */
    public function paginate(Tip $tip): LengthAwarePaginator
    {
        return $tip->rootComments()
            ->with([
                'user:id,name',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE);
    }
}
