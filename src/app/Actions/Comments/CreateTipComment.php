<?php

namespace App\Actions\Comments;

use App\Models\Comment;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * 댓글 생성
 *
 */
final class CreateTipComment
{
    /**
     * 원댓글 또는 대댓글을 등록한다.
     *
     * @return Comment 생성된 원댓글 또는 대댓글
     *
     * @throws ConflictHttpException 답글 대상의 상태나 계층이 올바르지 않은 경우
     */
    public function __invoke(
        Tip $tip, // 댓글을 등록할 팁 
        User $author, // 댓글 작성자
        string $body, // 검증과 정규화가 끝난 본문
        ?Comment $replyTo = null, // 실제 답글 대상
    ): Comment {
        return DB::transaction(function () use ($tip, $author, $body, $replyTo): Comment {
            if ($replyTo === null) {
                $lockedTip = Tip::query()
                    ->lockForUpdate()
                    ->findOrFail($tip->id);

                return $this->createRootComment($lockedTip, $author, $body);
            }

            $lockedReplyTo = Comment::query()
                ->lockForUpdate()
                ->findOrFail($replyTo->id);

            if (! $lockedReplyTo->isActive()) {
                throw new ConflictHttpException(
                    '삭제되거나 숨겨진 댓글에는 답글을 등록할 수 없습니다.',
                );
            }

            $rootComment = $lockedReplyTo->isRoot()
                ? $lockedReplyTo
                : $this->findLockedRootComment($lockedReplyTo);


            $lockedTip = Tip::query()
                ->lockForUpdate()
                ->findOrFail($lockedReplyTo->tip_id);

            // Action을 직접 호출해 다른 팁을 연결하는 비정상 입력도 차단한다.
            if ($lockedTip->id !== $tip->id) {
                throw new ConflictHttpException(
                    '현재 팁에 속하지 않은 댓글에는 답글을 등록할 수 없습니다.',
                );
            }

            return $this->createReply(
                tip: $lockedTip,
                rootComment: $rootComment,
                replyTo: $lockedReplyTo,
                author: $author,
                body: $body,
            );
        });
    }

    /**
     * 원댓글을 생성하고 팁의 활성 댓글 수를 증가시킨다.
     */
    private function createRootComment(
        Tip $tip,
        User $author,
        string $body,
    ): Comment {
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
    }

    /**
     * 대댓글이 소속된 실제 원댓글을 잠금 조회한다.
     *
     * @throws ConflictHttpException 정상적인 한 단계 관계가 아닌 경우
     */
    private function findLockedRootComment(Comment $replyTo): Comment
    {
        if ($replyTo->parent_id === null || $replyTo->depth !== 1) {
            throw new ConflictHttpException(
                '답글 대상 댓글의 계층 정보가 올바르지 않습니다.',
            );
        }

        $rootComment = Comment::query()
            ->lockForUpdate()
            ->find($replyTo->parent_id);

        if (
            $rootComment === null
            || ! $rootComment->isRoot()
            || $rootComment->tip_id !== $replyTo->tip_id
        ) {
            throw new ConflictHttpException(
                '답글 대상 댓글의 원댓글 관계가 올바르지 않습니다.',
            );
        }

        if (! $rootComment->isActive()) {
            throw new ConflictHttpException(
                '삭제되거나 숨겨진 원댓글에는 답글을 등록할 수 없습니다.',
            );
        }

        return $rootComment;
    }

    /**
     * 원댓글 아래에 depth 1 대댓글을 생성하고 관련 카운터를 증가
     */
    private function createReply(
        Tip $tip,
        Comment $rootComment,
        Comment $replyTo,
        User $author,
        string $body,
    ): Comment {
        /** @var Comment $reply */
        $reply = $tip->comments()->create([
            'user_id' => $author->id,
            'parent_id' => $rootComment->id,
            'reply_to_id' => $replyTo->id,
            'depth' => 1,
            'body' => $body,
            'status' => Comment::STATUS_ACTIVE,
            'like_count' => 0,
            'reply_count' => 0,
        ]);

        $rootComment->increment('reply_count');
        $tip->increment('comment_count');

        return $reply;
    }
}
