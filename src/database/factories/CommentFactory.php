<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 테스트용 댓글 데이터를 생성한다.
 *
 * 기본 상태는 원댓글이며, reply() 상태로 원댓글 또는 대댓글을 대상으로 한
 * 한 단계 대댓글 데이터를 만들 수 있다.
 *
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tip_id' => Tip::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'reply_to_id' => null,
            'depth' => 0,
            'body' => fake()->sentence(),
            'status' => Comment::STATUS_ACTIVE,
            'like_count' => 0,
            'reply_count' => 0,
        ];
    }

    /**
     * 지정한 원댓글 아래에 대댓글을 생성하는 상태를 적용한다.
     *
     * replyTo를 생략하면 원댓글에 직접 작성한 답글이 된다. 다른 대댓글을
     * 전달해도 parent_id는 원댓글로 유지해 depth를 한 단계로 평탄화한다.
     */
    public function reply(Comment $rootComment, ?Comment $replyTo = null): static
    {
        $targetComment = $replyTo ?? $rootComment;

        return $this->state(fn (): array => [
            'tip_id' => $rootComment->tip_id,
            'parent_id' => $rootComment->id,
            'reply_to_id' => $targetComment->id,
            'depth' => 1,
        ]);
    }
}
