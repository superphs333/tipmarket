<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 테스트용 원댓글 데이터를 생성한다.
 *
 * 현재 댓글 추가 기능은 원댓글만 지원하므로 기본 계층 값을
 * parent_id=null, reply_to_id=null, depth=0으로 고정한다.
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
}
