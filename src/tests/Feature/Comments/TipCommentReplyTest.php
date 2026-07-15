<?php

use App\Models\Comment;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Support\Carbon;

test('guest cannot create a reply', function () {
    $tip = Tip::factory()->create(['comment_count' => 1]);
    $rootComment = Comment::factory()->for($tip)->create();

    $this->postJson(route('comments.replies.store', $rootComment), [
        'body' => '비회원 대댓글',
    ])->assertUnauthorized();

    $this->assertDatabaseCount('comments', 1);
    expect($rootComment->fresh()->reply_count)->toBe(0)
        ->and($tip->fresh()->comment_count)->toBe(1);
});

test('logged in user can create a reply on a root comment', function () {
    $author = User::factory()->unverified()->create();
    $tip = Tip::factory()->create(['comment_count' => 1]);
    $rootComment = Comment::factory()->for($tip)->create();

    $response = $this
        ->actingAs($author)
        ->postJson(route('comments.replies.store', $rootComment), [
            'body' => '  원댓글에 작성한 답글  ',
        ]);

    $response
        ->assertCreated()
        ->assertJson([
            'parent_id' => $rootComment->id,
            'reply_to_id' => $rootComment->id,
        ]);

    $reply = Comment::query()->findOrFail($response->json('comment_id'));

    expect($reply->tip_id)->toBe($tip->id)
        ->and($reply->user_id)->toBe($author->id)
        ->and($reply->parent_id)->toBe($rootComment->id)
        ->and($reply->reply_to_id)->toBe($rootComment->id)
        ->and($reply->depth)->toBe(1)
        ->and($reply->body)->toBe('원댓글에 작성한 답글')
        ->and($reply->status)->toBe(Comment::STATUS_ACTIVE)
        ->and($rootComment->fresh()->reply_count)->toBe(1)
        ->and($tip->fresh()->comment_count)->toBe(2);
});

test('replying to a reply keeps one depth and records the actual target', function () {
    $author = User::factory()->create();
    $tip = Tip::factory()->create(['comment_count' => 2]);
    $rootComment = Comment::factory()->for($tip)->create([
        'reply_count' => 1,
    ]);
    $firstReply = Comment::factory()->reply($rootComment)->create();

    $response = $this
        ->actingAs($author)
        ->postJson(route('comments.replies.store', $firstReply), [
            'body' => '대댓글에 작성한 답글',
        ])
        ->assertCreated()
        ->assertJson([
            'parent_id' => $rootComment->id,
            'reply_to_id' => $firstReply->id,
        ]);

    $reply = Comment::query()->findOrFail($response->json('comment_id'));

    expect($reply->parent_id)->toBe($rootComment->id)
        ->and($reply->reply_to_id)->toBe($firstReply->id)
        ->and($reply->depth)->toBe(1)
        ->and($rootComment->fresh()->reply_count)->toBe(2)
        ->and($tip->fresh()->comment_count)->toBe(3);
});

test('reply creation validation rejects invalid body', function (mixed $body) {
    $author = User::factory()->create();
    $tip = Tip::factory()->create(['comment_count' => 1]);
    $rootComment = Comment::factory()->for($tip)->create();

    $this
        ->actingAs($author)
        ->postJson(route('comments.replies.store', $rootComment), [
            'body' => $body,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('body');

    $this->assertDatabaseCount('comments', 1);
    expect($rootComment->fresh()->reply_count)->toBe(0)
        ->and($tip->fresh()->comment_count)->toBe(1);
})->with([
    'empty string' => [''],
    'whitespace only' => ['   '],
    'more than 1000 characters' => [str_repeat('가', 1001)],
    'non string body' => [['대댓글']],
]);

test('user cannot reply to deleted or hidden target comment', function (string $status) {
    $author = User::factory()->create();
    $tip = Tip::factory()->create();
    $targetComment = Comment::factory()->for($tip)->create([
        'status' => $status,
    ]);

    $this
        ->actingAs($author)
        ->postJson(route('comments.replies.store', $targetComment), [
            'body' => '등록하면 안 되는 답글',
        ])
        ->assertConflict();

    $this->assertDatabaseCount('comments', 1);
    expect($targetComment->fresh()->reply_count)->toBe(0)
        ->and($tip->fresh()->comment_count)->toBe(0);
})->with([
    'deleted' => Comment::STATUS_DELETED,
    'hidden' => Comment::STATUS_HIDDEN,
]);

test('user cannot reply through an active reply whose root is inactive', function (string $status) {
    $author = User::factory()->create();
    $tip = Tip::factory()->create(['comment_count' => 1]);
    $rootComment = Comment::factory()->for($tip)->create([
        'status' => $status,
        'reply_count' => 1,
    ]);
    $targetReply = Comment::factory()->reply($rootComment)->create();

    $this
        ->actingAs($author)
        ->postJson(route('comments.replies.store', $targetReply), [
            'body' => '비활성 원댓글에 추가할 답글',
        ])
        ->assertConflict();

    $this->assertDatabaseCount('comments', 2);
    expect($rootComment->fresh()->reply_count)->toBe(1)
        ->and($tip->fresh()->comment_count)->toBe(1);
})->with([
    'deleted root' => Comment::STATUS_DELETED,
    'hidden root' => Comment::STATUS_HIDDEN,
]);

test('user cannot reply to malformed nested comment hierarchy', function () {
    $author = User::factory()->create();
    $tip = Tip::factory()->create(['comment_count' => 2]);
    $rootComment = Comment::factory()->for($tip)->create([
        'reply_count' => 1,
    ]);
    $malformedReply = Comment::factory()->reply($rootComment)->create([
        'depth' => 2,
    ]);

    $this
        ->actingAs($author)
        ->postJson(route('comments.replies.store', $malformedReply), [
            'body' => '잘못된 계층에 작성할 답글',
        ])
        ->assertConflict();

    $this->assertDatabaseCount('comments', 2);
    expect($rootComment->fresh()->reply_count)->toBe(1)
        ->and($tip->fresh()->comment_count)->toBe(2);
});

test('user cannot create a reply on an inaccessible draft tip', function () {
    $tipOwner = User::factory()->create();
    $otherUser = User::factory()->create();
    $tip = Tip::factory()->for($tipOwner)->create([
        'status' => Tip::STATUS_DRAFT,
        'comment_count' => 1,
    ]);
    $rootComment = Comment::factory()->for($tip)->create();

    $this
        ->actingAs($otherUser)
        ->postJson(route('comments.replies.store', $rootComment), [
            'body' => '권한 없는 답글',
        ])
        ->assertNotFound();

    $this->assertDatabaseCount('comments', 1);
    expect($rootComment->fresh()->reply_count)->toBe(0)
        ->and($tip->fresh()->comment_count)->toBe(1);
});

test('reply author can update an active reply without changing counters', function () {
    $author = User::factory()->create();
    $tip = Tip::factory()->create(['comment_count' => 2]);
    $rootComment = Comment::factory()->for($tip)->create([
        'reply_count' => 1,
    ]);
    $reply = Comment::factory()->for($author)->reply($rootComment)->create([
        'body' => '수정 전 대댓글',
    ]);

    $this
        ->actingAs($author)
        ->patchJson(route('comments.update', $reply), [
            'body' => '  수정된 대댓글  ',
        ])
        ->assertOk()
        ->assertJson([
            'comment_id' => $reply->id,
            'body' => '수정된 대댓글',
        ]);

    expect($reply->fresh()->body)->toBe('수정된 대댓글')
        ->and($rootComment->fresh()->reply_count)->toBe(1)
        ->and($tip->fresh()->comment_count)->toBe(2);
});

test('reply author can delete a reply and recount both caches', function () {
    $author = User::factory()->create();
    $tip = Tip::factory()->create(['comment_count' => 99]);
    $rootComment = Comment::factory()->for($tip)->create([
        'reply_count' => 99,
    ]);
    $reply = Comment::factory()->for($author)->reply($rootComment)->create();
    Comment::factory()->reply($rootComment)->create();
    Comment::factory()->reply($rootComment)->create([
        'status' => Comment::STATUS_HIDDEN,
    ]);

    $this
        ->actingAs($author)
        ->deleteJson(route('comments.destroy', $reply))
        ->assertNoContent();

    expect($reply->fresh()->status)->toBe(Comment::STATUS_DELETED)
        ->and($rootComment->fresh()->reply_count)->toBe(1)
        ->and($tip->fresh()->comment_count)->toBe(2);
});

test('comment list renders replies in order and shows active reply target mention', function () {
    $viewer = User::factory()->create();
    $rootAuthor = User::factory()->create(['name' => '원댓글작성자']);
    $firstReplyAuthor = User::factory()->create(['name' => '첫답글작성자']);
    $secondReplyAuthor = User::factory()->create(['name' => '후속답글작성자']);
    $tip = Tip::factory()->create(['comment_count' => 3]);
    $rootComment = Comment::factory()->for($tip)->for($rootAuthor)->create([
        'body' => '원댓글 본문',
        'reply_count' => 2,
    ]);
    $firstReply = Comment::factory()
        ->for($firstReplyAuthor)
        ->reply($rootComment)
        ->create([
            'body' => '첫 번째 답글',
            'created_at' => Carbon::parse('2026-01-01 00:00:01'),
        ]);
    $secondReply = Comment::factory()
        ->for($secondReplyAuthor)
        ->reply($rootComment, $firstReply)
        ->create([
            'body' => '두 번째 답글',
            'created_at' => Carbon::parse('2026-01-01 00:00:02'),
        ]);

    $this
        ->actingAs($viewer)
        ->get(route('tips.comments.index', $tip))
        ->assertOk()
        ->assertSeeInOrder(['첫 번째 답글', '두 번째 답글'])
        ->assertSee('@첫답글작성자')
        ->assertSee('comment-'.$firstReply->id, false)
        ->assertSee('comment-'.$secondReply->id, false)
        ->assertSee(route('comments.replies.store', $secondReply), false);
});

test('deleted root keeps existing replies but hides every reply action', function () {
    $viewer = User::factory()->create();
    $replyAuthor = User::factory()->create();
    $tip = Tip::factory()->create(['comment_count' => 1]);
    $rootComment = Comment::factory()->for($tip)->create([
        'status' => Comment::STATUS_DELETED,
        'reply_count' => 1,
    ]);
    $reply = Comment::factory()->for($replyAuthor)->reply($rootComment)->create([
        'body' => '삭제된 원댓글 아래의 기존 답글',
    ]);

    $this
        ->actingAs($viewer)
        ->get(route('tips.comments.index', $tip))
        ->assertOk()
        ->assertSee('삭제된 댓글입니다.')
        ->assertSee('삭제된 원댓글 아래의 기존 답글')
        ->assertDontSee(route('comments.replies.store', $rootComment), false)
        ->assertDontSee(route('comments.replies.store', $reply), false);
});

test('deleted reply target author is not exposed through a mention', function () {
    $tip = Tip::factory()->create(['comment_count' => 2]);
    $rootComment = Comment::factory()->for($tip)->create([
        'reply_count' => 2,
    ]);
    $deletedTarget = Comment::factory()
        ->for(User::factory()->create(['name' => '숨겨질답글작성자']))
        ->reply($rootComment)
        ->create(['status' => Comment::STATUS_DELETED]);
    Comment::factory()->reply($rootComment, $deletedTarget)->create([
        'body' => '삭제된 답글을 대상으로 했던 후속 답글',
    ]);

    $this->get(route('tips.comments.index', $tip))
        ->assertOk()
        ->assertSee('삭제된 답글을 대상으로 했던 후속 답글')
        ->assertDontSee('숨겨질답글작성자');
});
