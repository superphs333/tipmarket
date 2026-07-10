<?php

namespace App\Actions\Comments;

use App\Models\Comment;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * 로그인 사용자의 원댓글을 생성
 */
final class CreateTipComment
{
    /**
     * 팁에 새로운 원댓글을 등록
     *
     * @param  Tip  $tip  댓글을 등록할 팁
     * @param  User  $author  댓글 작성자
     * @param  string  $body  검증과 정규화가 끝난 댓글 본문
     * @return Comment 생성된 원댓글
     */
    public function __invoke(Tip $tip, User $author, string $body): Comment
    {
        return DB::transaction(function () use ($tip, $author, $body): Comment {
            /** @var Comment $comment */
            $comment = $tip->comments()->create([
                'user_id' => $author->id,
                'parent_id' => null,
                'reply_to_id' => null,
                'depth' => 0,
                'body' => $body,
                'status' => Comment::STATUS_ACTIVE,
                'like_count' => 0,
                'reply_count' => 0,
            ]);

            $tip->increment('comment_count');

            return $comment;
        });
    }
}
