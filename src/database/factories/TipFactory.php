<?php

namespace Database\Factories;

use App\Models\Tip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 테스트용 팁 데이터를 생성하는 factory.
 *
 * 조회수 테스트에서는 공개 팁, 작성자 팁, 비공개 팁을 반복해서 만들어야 하므로
 * 테스트 내부에서 매번 Tip::create()를 직접 호출하기보다 factory로 기본값을 표준화한다.
 *
 * @extends Factory<Tip>
 */
class TipFactory extends Factory
{
    protected $model = Tip::class;

    /**
     * 기본 팁 상태를 정의한다.
     *
     * 기본값은 공개 발행 글로 둔다. 테스트에서 draft/private/premium 같은 상태가 필요하면
     * 각 테스트의 create([...]) 인자로 명시적으로 덮어쓴다.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => null,
            'title' => fake()->sentence(6),
            'content' => fake()->paragraphs(3, true),
            'status' => Tip::STATUS_PUBLISHED,
            'audience' => Tip::AUDIENCE_PUBLIC,
            'view_count' => 0,
            'like_count' => 0,
            'bookmark_count' => 0,
            'comment_count' => 0,
        ];
    }
}
