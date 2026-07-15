<?php

namespace App\Actions\Comments;

use App\Models\Comment;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * 작성자의 댓글을 삭제 상태로 변경하고 관련 카운터를 보정한다.
 */
final class DeleteTipComment
{
    /**
     * 댓글 구조를 보존하는 상태 삭제를 수행한다.
     *
     * @param  Comment  $comment  삭제 대상 원댓글 또는 대댓글
     * @param  User  $actor  삭제를 요청한 로그인 사용자
     */
    public function __invoke(Comment $comment, User $actor): void
    {
        DB::transaction(function () use ($comment, $actor): void {
            $targetComment = Comment::query()
                ->lockForUpdate()
                ->findOrFail($comment->id);

            abort_unless($actor->id === $targetComment->user_id, 403);

            $rootComment = $this->findLockedRootComment($targetComment);

            $tip = Tip::query()
                ->lockForUpdate()
                ->findOrFail($targetComment->tip_id);

            if ($targetComment->isActive()) {
                $targetComment->update([
                    'status' => Comment::STATUS_DELETED,
                ]);
            }

            if ($rootComment !== null) {
                $this->recountRootReplies($rootComment);
            }

            $this->recountTipComments($tip);
        });
    }

    /**
     * 대댓글이면 동일 팁에 속한 정상 원댓글을 잠가 반환한다.
     */
    private function findLockedRootComment(Comment $comment): ?Comment
    {
        if ($comment->parent_id === null) {
            return null;
        }

        $rootComment = Comment::query()
            ->lockForUpdate()
            ->findOrFail($comment->parent_id);

        abort_unless(
            $rootComment->isRoot()
            && $rootComment->tip_id === $comment->tip_id,
            409,
        );

        return $rootComment;
    }

    /**
     * 원댓글의 활성 대댓글 수 캐시를 실제 데이터로 보
     */
    private function recountRootReplies(Comment $rootComment): void
    {
        $rootComment->update([
            'reply_count' => Comment::query()
                ->where('parent_id', $rootComment->id)
                ->where('status', Comment::STATUS_ACTIVE)
                ->count(),
        ]);
    }

    /**
     * 팁에 속한 원댓글과 대댓글의 활성 개수 캐시를 실제 데이터로 보정한다.
     */
    private function recountTipComments(Tip $tip): void
    {
        $tip->update([
            'comment_count' => Comment::query()
                ->where('tip_id', $tip->id)
                ->where('status', Comment::STATUS_ACTIVE)
                ->count(),
        ]);
    }
}
