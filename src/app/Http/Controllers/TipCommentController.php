<?php

namespace App\Http\Controllers;

use App\Actions\Comments\CreateTipComment;
use App\Http\Requests\Comments\StoreTipCommentRequest;
use App\Models\Tip;
use App\Models\User;
use App\Queries\Comments\TipCommentQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

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
        StoreTipCommentRequest $request,
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
}
