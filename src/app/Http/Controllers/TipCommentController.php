<?php

namespace App\Http\Controllers;

use App\Actions\Comments\CreateTipComment;
use App\Http\Requests\Comments\SaveTipCommentRequest;
use App\Models\Comment;
use App\Models\Tip;
use App\Models\User;
use App\Queries\Comments\TipCommentQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

final class TipCommentController extends Controller
{
    // 원댓글 목록 HTML
    public function index(
        Tip $tip,
        TipCommentQuery $commentQuery,
    ): View {
        return view('tips.comments.index', [
            'tip' => $tip,
            'comments' => $commentQuery->paginate($tip),
        ]);
    }

    // 로그인 사용자의 원댓글 등록
    public function store(
        SaveTipCommentRequest $request,
        Tip $tip,
        CreateTipComment $createTipComment,
    ): JsonResponse {
        /** @var User $author */
        $author = $request->user();

        /** @var array{body: string} $validated */
        $validated = $request->validated();

        $comment = $createTipComment(
            tip: $tip,
            author: $author,
            body: $validated['body'],
        );

        return response()->json([
            'comment_id' => $comment->id,
        ], 201);
    }

    /**
     * 작성자가 자신의 활성 댓글을 삭제 상태로 변경한다.
     *
     * 댓글 레코드는 구조 유지를 위해 남겨 두고, 활성 댓글 수를 다시 집계해
     * 팁의 comment_count 캐시가 실제 데이터와 일치하도록 보정한다.
     */
    public function destroy(Request $request, Comment $comment): Response
    {
        // UI를 우회한 직접 요청도 차단할 수 있도록 서버에서 작성자를 확인한다.
        abort_unless(
            $request->user()?->id === $comment->user_id,
            403,
        );

        DB::transaction(function () use ($comment): void {
            /** @var Comment $targetComment */
            $targetComment = Comment::query()
                ->lockForUpdate()
                ->findOrFail($comment->id);

            /** @var Tip $tip */
            $tip = Tip::query()
                ->lockForUpdate()
                ->findOrFail($targetComment->tip_id);

            // 이미 삭제되거나 숨겨진 댓글은 상태를 다시 변경하지 않는다.
            if ($targetComment->isActive()) {
                $targetComment->update([
                    'status' => Comment::STATUS_DELETED,
                ]);
            }

            // 원댓글과 대댓글을 포함해 현재 남아 있는 활성 댓글 수를 다시 계산한다.
            $activeCommentCount = Comment::query()
                ->where('tip_id', $tip->id)
                ->where('status', Comment::STATUS_ACTIVE)
                ->count();

            // 기존 캐시가 어긋나 있어도 실제 활성 댓글 수로 함께 보정한다.
            $tip->update([
                'comment_count' => $activeCommentCount,
            ]);
        });

        return response()->noContent();
    }
}
