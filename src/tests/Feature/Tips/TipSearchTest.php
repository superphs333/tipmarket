<?php

use App\Enums\MediaCollection;
use App\Models\Category;
use App\Models\Media;
use App\Models\Tag;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('the search page renders actual public published tips and their related data', function () {
    Storage::fake('public');

    $author = User::factory()->create(['name' => '정리생활연구소']);
    $category = createSearchCategory('청소', 'cleaning');
    $tag = createSearchTag('욕실', 'bathroom');

    $visibleTip = createSearchTip($author, [
        'category_id' => $category->id,
        'title' => '욕실 청소 순서',
        'content' => '<p>욕실 <strong>물때</strong>를 빠르게 제거합니다.</p>',
        'comment_count' => 8,
        'like_count' => 32,
        'bookmark_count' => 14,
    ]);
    $visibleTip->tags()->attach($tag);
    $visibleTip->media()->create([
        'disk' => 'public',
        'path' => 'tips/search/thumbnail.webp',
        'collection' => MediaCollection::TipThumbnail->value,
        'original_name' => 'thumbnail.webp',
        'mime_type' => 'image/webp',
        'size' => 1024,
        'width' => 640,
        'height' => 480,
        'uploaded_by_id' => $author->id,
        'status' => Media::STATUS_ATTACHED,
        'visibility' => Media::VISIBILITY_PUBLIC,
    ]);

    createSearchTip($author, [
        'title' => '숨겨진 청소 임시저장',
        'status' => Tip::STATUS_DRAFT,
    ]);
    createSearchTip($author, [
        'title' => '숨겨진 청소 비공개',
        'audience' => Tip::AUDIENCE_PRIVATE,
    ]);

    $this->get(route('tips.search', ['query' => '청소']))
        ->assertOk()
        ->assertSee('1개의 게시글')
        ->assertSee('욕실 청소 순서')
        ->assertSee('정리생활연구소')
        ->assertSee('욕실 물때를 빠르게 제거합니다.')
        ->assertSee('#욕실')
        ->assertSee('댓글 8')
        ->assertSee(route('tips.show', $visibleTip), escape: false)
        ->assertSee(Storage::disk('public')->url('tips/search/thumbnail.webp'), escape: false)
        ->assertDontSee('숨겨진 청소 임시저장')
        ->assertDontSee('숨겨진 청소 비공개');
});

test('the search page filters tips by category and every selected tag', function () {
    $author = User::factory()->create();
    $cleaning = createSearchCategory('청소', 'cleaning');
    $cooking = createSearchCategory('요리', 'cooking');
    $bathroom = createSearchTag('욕실', 'bathroom');
    $beginner = createSearchTag('초보자', 'beginner');

    $matchingTip = createSearchTip($author, [
        'category_id' => $cleaning->id,
        'title' => '조건에 맞는 팁',
    ]);
    $matchingTip->tags()->attach([$bathroom->id, $beginner->id]);

    $missingTagTip = createSearchTip($author, [
        'category_id' => $cleaning->id,
        'title' => '태그가 부족한 팁',
    ]);
    $missingTagTip->tags()->attach($bathroom);

    $otherCategoryTip = createSearchTip($author, [
        'category_id' => $cooking->id,
        'title' => '다른 카테고리 팁',
    ]);
    $otherCategoryTip->tags()->attach([$bathroom->id, $beginner->id]);

    $this->get(route('tips.search', [
        'category' => $cleaning->id,
        'tag_names' => ['욕실', '초보자'],
    ]))
        ->assertOk()
        ->assertSee('조건에 맞는 팁')
        ->assertDontSee('태그가 부족한 팁')
        ->assertDontSee('다른 카테고리 팁');
});

test('the search page applies each reaction sort option', function (string $sort, string $column) {
    $author = User::factory()->create();
    createSearchTip($author, [
        'title' => "{$sort} 낮은 팁",
        $column => 10,
    ]);
    createSearchTip($author, [
        'title' => "{$sort} 높은 팁",
        $column => 20,
    ]);

    $response = $this->get(route('tips.search', ['sort' => $sort]));

    $response
        ->assertOk()
        ->assertSeeInOrder(["{$sort} 높은 팁", "{$sort} 낮은 팁"]);

    expect($response->getContent())
        ->toMatch('/value="'.preg_quote($sort, '/').'"\s+selected/')
        ->toMatch('/type="hidden"\s+name="sort"\s+value="'.preg_quote($sort, '/').'"/');
})->with([
    '조회순' => ['popular', 'view_count'],
    '좋아요순' => ['likes', 'like_count'],
    '북마크순' => ['bookmarks', 'bookmark_count'],
]);

test('an invalid sort value safely falls back to the latest sort', function () {
    $author = User::factory()->create();
    $olderTip = createSearchTip($author, ['title' => '이전 수정 팁']);
    $newerTip = createSearchTip($author, ['title' => '최근 수정 팁']);

    Tip::query()->whereKey($olderTip)->update(['updated_at' => '2026-07-17 12:00:00']);
    Tip::query()->whereKey($newerTip)->update(['updated_at' => '2026-07-18 12:00:00']);

    $this->get(route('tips.search', ['sort' => 'invalid']))
        ->assertOk()
        ->assertSeeInOrder(['최근 수정 팁', '이전 수정 팁']);
});

test('the search page rejects malformed query parameter types', function () {
    $this->get(route('tips.search', [
        'query' => ['invalid'],
        'category' => ['invalid'],
        'tag_names' => [['invalid']],
        'sort' => ['invalid'],
    ]))
        ->assertRedirect()
        ->assertSessionHasErrors([
            'query',
            'category',
            'tag_names.0',
            'sort',
        ]);
});

test('the search page shows an empty state when no tip matches', function () {
    $this->get(route('tips.search', ['query' => '존재하지않는검색어']))
        ->assertOk()
        ->assertSee('0개의 게시글')
        ->assertSee('검색 결과가 없습니다.');
});

test('search pagination keeps the current filters and sort', function () {
    $author = User::factory()->create();

    foreach (range(1, 13) as $number) {
        createSearchTip($author, [
            'title' => $number === 1
                ? '페이지 검색 가장 낮은 팁'
                : "페이지 검색 순위 {$number}",
            'view_count' => $number,
        ]);
    }

    $firstPage = $this->get(route('tips.search', [
        'query' => '페이지 검색',
        'sort' => 'popular',
    ]));

    $firstPage
        ->assertOk()
        ->assertSee('13개의 게시글')
        ->assertSee('페이지 검색 순위 13')
        ->assertDontSee('페이지 검색 가장 낮은 팁')
        ->assertSee('query='.rawurlencode('페이지 검색'), escape: false)
        ->assertSee('sort=popular', escape: false)
        ->assertSee('page=2', escape: false);

    $this->get(route('tips.search', [
        'query' => '페이지 검색',
        'sort' => 'popular',
        'page' => 2,
    ]))
        ->assertOk()
        ->assertSee('페이지 검색 가장 낮은 팁');
});

/**
 * 프론트 검색 테스트에서 사용할 공개·발행 Tip을 생성한다.
 *
 * @param  array<string, mixed>  $attributes
 */
function createSearchTip(User $author, array $attributes = []): Tip
{
    return Tip::factory()
        ->for($author)
        ->create([
            'status' => Tip::STATUS_PUBLISHED,
            'audience' => Tip::AUDIENCE_PUBLIC,
            ...$attributes,
        ]);
}

/**
 * 검색 필터에 사용할 활성 카테고리를 생성한다.
 */
function createSearchCategory(string $name, string $slug): Category
{
    return Category::query()->create([
        'name' => $name,
        'slug' => $slug,
        'sort_order' => 0,
        'is_active' => true,
    ]);
}

/**
 * 검색 필터에 사용할 활성 태그를 생성한다.
 */
function createSearchTag(string $name, string $slug): Tag
{
    return Tag::query()->create([
        'name' => $name,
        'slug' => $slug,
        'usage_count' => 0,
        'is_active' => true,
    ]);
}
