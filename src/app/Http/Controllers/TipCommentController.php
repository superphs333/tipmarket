<?php

namespace App\Http\Controllers;

use App\Actions\Comments\CreateTipComment;
use App\Actions\Comments\DeleteTipComment;
use App\Actions\Comments\UpdateTipComment;
use App\Http\Requests\Comments\SaveTipCommentRequest;
use App\Models\Comment;
use App\Models\Tip;
use App\Models\User;
use App\Queries\Comments\TipCommentQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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


    public function destroy(
        Request $request,
        Comment $comment,
        DeleteTipComment $deleteTipComment,
    ): Response {
        /** @var User $actor */
        $actor = $request->user();

        $deleteTipComment(comment: $comment, actor: $actor);

        return response()->noContent();
    }

    // 작성자가 자신의 활성 댓글 본문 수정
    public function update(
        SaveTipCommentRequest $request,
        Comment $comment,
        UpdateTipComment $updateTipComment,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        /** @var array{body: string} $validated */
        $validated = $request->validated();

        $updatedComment = $updateTipComment(
            comment: $comment,
            actor: $actor,
            body: $validated['body'],
        );

        return response()->json([
            'comment_id' => $updatedComment->id,
            'body' => $updatedComment->body,
        ]);
    }

    /**
     * 로그인 사용자가 선택한 댓글에 대댓글을 등록
     *
     * @param  SaveTipCommentRequest  $request  인증 및 본문 검증이 완료된 요청
     * @param  Comment  $comment  route model binding으로 조회된 실제 답글 대상 댓글
     * @param  CreateTipComment  $createTipComment  댓글 생성 Action
     * @return JsonResponse 생성된 대댓글 식별자와 관계 정보
     */
    public function storeReply(
        SaveTipCommentRequest $request,
        Comment $comment,
        CreateTipComment $createTipComment,
    ): JsonResponse {
        $author = $request->user();
        $validated = $request->validated();
        $tip = $comment->tip()->firstOrFail();

        // 조회 권한이 없는 팁에 답글을 다는 것을 차단
        $this->authorize('view', $tip);

        $reply = $createTipComment(
            tip: $tip,
            author: $author,
            body: $validated['body'],
            replyTo: $comment,
        );

        return response()->json([
            'comment_id' => $reply->id,
            'parent_id' => $reply->parent_id,
            'reply_to_id' => $reply->reply_to_id,
        ], 201);
    }
}
