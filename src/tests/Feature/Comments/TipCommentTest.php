<?php

use App\Models\Comment;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Support\Carbon;

test('guest can view comments for a public tip', function () {
    $tip = Tip::factory()->create(['comment_count' => 1]);
    $comment = Comment::factory()->for($tip)->create([
        'body' => '비회원에게 보이는 댓글',
    ]);

    $this->get(route('tips.comments.index', $tip))
        ->assertOk()
        ->assertSee('비회원에게 보이는 댓글')
        ->assertSee('comment-'.$comment->id, false)
        ->assertSee('data-comment-count-value="1"', false);
});

test('guest cannot create a comment', function () {
    $tip = Tip::factory()->create();

    $this->postJson(route('tips.comments.store', $tip), [
        'body' => '비회원 댓글',
    ])->assertUnauthorized();

    $this->assertDatabaseCount('comments', 0);
    expect($tip->fresh()->comment_count)->toBe(0);
});

test('guest cannot delete a comment', function () {
    $author = User::factory()->create();
    $tip = Tip::factory()->create(['comment_count' => 1]);
    $comment = Comment::factory()->for($tip)->for($author)->create();

    $this->deleteJson(route('comments.destroy', $comment))
        ->assertUnauthorized();

    expect($comment->fresh()->status)->toBe(Comment::STATUS_ACTIVE)
        ->and($tip->fresh()->comment_count)->toBe(1);
});

test('guest cannot update a comment', function () {
    $author = User::factory()->create();
    $comment = Comment::factory()->for($author)->create([
        'body' => '기존 댓글',
    ]);

    $this->patchJson(route('comments.update', $comment), [
        'body' => '비회원 수정 시도',
    ])->assertUnauthorized();

    expect($comment->fresh()->body)->toBe('기존 댓글');
});

test('comment author can delete an active comment and recount active comments', function () {
    $author = User::factory()->create();
    $tip = Tip::factory()->create(['comment_count' => 99]);
    $comment = Comment::factory()->for($tip)->for($author)->create();

    Comment::factory()->for($tip)->create();
    Comment::factory()->for($tip)->create([
        'status' => Comment::STATUS_HIDDEN,
    ]);

    $this
        ->actingAs($author)
        ->deleteJson(route('comments.destroy', $comment))
        ->assertNoContent();

    expect($comment->fresh()->status)->toBe(Comment::STATUS_DELETED)
        ->and($tip->fresh()->comment_count)->toBe(1);
});

test('user cannot delete another users comment', function () {
    $author = User::factory()->create();
    $otherUser = User::factory()->create();
    $tip = Tip::factory()->create(['comment_count' => 1]);
    $comment = Comment::factory()->for($tip)->for($author)->create();

    $this
        ->actingAs($otherUser)
        ->deleteJson(route('comments.destroy', $comment))
        ->assertForbidden();

    expect($comment->fresh()->status)->toBe(Comment::STATUS_ACTIVE)
        ->and($tip->fresh()->comment_count)->toBe(1);
});

test('comment author can update an active comment without changing comment count', function () {
    $author = User::factory()->create();
    $tip = Tip::factory()->create(['comment_count' => 1]);
    $comment = Comment::factory()->for($tip)->for($author)->create([
        'body' => '수정 전 댓글',
    ]);

    $this
        ->actingAs($author)
        ->patchJson(route('comments.update', $comment), [
            'body' => '  수정된 댓글  ',
        ])
        ->assertOk()
        ->assertJson([
            'comment_id' => $comment->id,
            'body' => '수정된 댓글',
        ]);

    expect($comment->fresh()->body)->toBe('수정된 댓글')
        ->and($tip->fresh()->comment_count)->toBe(1);
});

test('user cannot update another users comment', function () {
    $author = User::factory()->create();
    $otherUser = User::factory()->create();
    $comment = Comment::factory()->for($author)->create([
        'body' => '원래 댓글',
    ]);

    $this
        ->actingAs($otherUser)
        ->patchJson(route('comments.update', $comment), [
            'body' => '권한 없는 수정',
        ])
        ->assertForbidden();

    expect($comment->fresh()->body)->toBe('원래 댓글');
});

test('deleted and hidden comments cannot be updated', function (string $status) {
    $author = User::factory()->create();
    $comment = Comment::factory()->for($author)->create([
        'body' => '비활성 댓글',
        'status' => $status,
    ]);

    $this
        ->actingAs($author)
        ->patchJson(route('comments.update', $comment), [
            'body' => '수정 시도',
        ])
        ->assertConflict();

    expect($comment->fresh()->body)->toBe('비활성 댓글');
})->with([
    'deleted' => Comment::STATUS_DELETED,
    'hidden' => Comment::STATUS_HIDDEN,
]);

test('comment update validation rejects invalid body', function (mixed $body) {
    $author = User::factory()->create();
    $comment = Comment::factory()->for($author)->create([
        'body' => '기존 댓글',
    ]);

    $this
        ->actingAs($author)
        ->patchJson(route('comments.update', $comment), [
            'body' => $body,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('body');

    expect($comment->fresh()->body)->toBe('기존 댓글');
})->with([
    'empty string' => [''],
    'whitespace only' => ['   '],
    'more than 1000 characters' => [str_repeat('가', 1001)],
    'non string body' => [['댓글']],
]);

test('logged in user can create a root comment on own tip', function () {
    $author = User::factory()->unverified()->create();
    $tip = Tip::factory()->for($author)->create();

    $response = $this
        ->actingAs($author)
        ->postJson(route('tips.comments.store', $tip), [
            'body' => "  첫 번째 댓글\n입니다.  ",
        ]);

    $response
        ->assertCreated()
        ->assertJsonStructure(['comment_id'])
        ->assertJsonMissing(['page']);

    $comment = Comment::query()->sole();

    expect($comment->tip_id)->toBe($tip->id)
        ->and($comment->user_id)->toBe($author->id)
        ->and($comment->parent_id)->toBeNull()
        ->and($comment->reply_to_id)->toBeNull()
        ->and($comment->depth)->toBe(0)
        ->and($comment->body)->toBe("첫 번째 댓글\n입니다.")
        ->and($comment->status)->toBe(Comment::STATUS_ACTIVE)
        ->and($tip->fresh()->comment_count)->toBe(1);
});

test('comment creation validation rejects invalid body', function (mixed $body) {
    $user = User::factory()->create();
    $tip = Tip::factory()->create();

    $this
        ->actingAs($user)
        ->postJson(route('tips.comments.store', $tip), [
            'body' => $body,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('body');

    $this->assertDatabaseCount('comments', 0);
    expect($tip->fresh()->comment_count)->toBe(0);
})->with([
    'empty string' => [''],
    'whitespace only' => ['   '],
    'more than 1000 characters' => [str_repeat('가', 1001)],
    'non string body' => [['댓글']],
]);

test('root comments are paginated 20 per page in newest order', function () {
    $tip = Tip::factory()->create(['comment_count' => 21]);
    $author = User::factory()->create();
    $startedAt = Carbon::parse('2026-01-01 00:00:00');

    foreach (range(1, 21) as $number) {
        Comment::factory()
            ->for($tip)
            ->for($author)
            ->create([
                'body' => sprintf('body-%03d', $number),
                'created_at' => $startedAt->copy()->addSeconds($number),
                'updated_at' => $startedAt->copy()->addSeconds($number),
            ]);
    }

    $this->get(route('tips.comments.index', $tip))
        ->assertOk()
        ->assertSeeInOrder(['body-021', 'body-020', 'body-019'])
        ->assertSee('body-002')
        ->assertDontSee('body-001');

    $this->get(route('tips.comments.index', ['tip' => $tip, 'page' => 2]))
        ->assertOk()
        ->assertSee('body-001')
        ->assertDontSee('body-021');
});

test('deleted and hidden comments display status text instead of original content', function () {
    $tip = Tip::factory()->create();
    $author = User::factory()->create(['name' => '숨겨질 작성자']);

    Comment::factory()->for($tip)->for($author)->create([
        'body' => '삭제 전 본문',
        'status' => Comment::STATUS_DELETED,
    ]);

    Comment::factory()->for($tip)->for($author)->create([
        'body' => '숨김 전 본문',
        'status' => Comment::STATUS_HIDDEN,
    ]);

    $this->get(route('tips.comments.index', $tip))
        ->assertOk()
        ->assertSee('삭제된 댓글입니다.')
        ->assertSee('관리자에 의해 숨겨진 댓글입니다.')
        ->assertDontSee('삭제 전 본문')
        ->assertDontSee('숨김 전 본문')
        ->assertDontSee('숨겨질 작성자');
});
