<?php

namespace App\Actions\Comments;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * 작성자가 소유한 활성 댓글의 본문을 수정한다.
 */
final class UpdateTipComment
{
    /**
     * 최신 댓글 레코드를 잠근 뒤 권한과 상태를 확인하고 본문을 변경한다.
     *
     * @param  Comment  $comment  수정 대상 댓글
     * @param  User  $actor  수정을 요청한 로그인 사용자
     * @param  string  $body  검증과 정규화가 끝난 본문
     * @return Comment 수정된 댓글
     */
    public function __invoke(Comment $comment, User $actor, string $body): Comment
    {
        return DB::transaction(function () use ($comment, $actor, $body): Comment {
            $targetComment = Comment::query()
                ->lockForUpdate()
                ->findOrFail($comment->id);

            abort_unless($actor->id === $targetComment->user_id, 403);
            abort_unless($targetComment->isActive(), 409);

            $targetComment->update([
                'body' => $body,
            ]);

            return $targetComment;
        });
    }
}
