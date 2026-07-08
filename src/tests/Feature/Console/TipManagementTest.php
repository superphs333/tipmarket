<?php

use App\Enums\MediaCollection;
use App\Models\Category;
use App\Models\Media;
use App\Models\Role;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

function createConsoleUserWithRole(string $roleName): User
{
    $user = User::factory()->create();
    $role = Role::query()->create([
        'name' => $roleName,
        'label' => $roleName,
        'description' => $roleName,
    ]);

    $user->roles()->attach($role);

    return $user;
}

test('guests are redirected from the console tips page', function () {
    $this->get(route('console.tips.index'))
        ->assertRedirect(route('login'));
});

test('non console users cannot visit the console tips page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('console.tips.index'))
        ->assertForbidden();
});

test('support users can enter the console but cannot manage tips', function () {
    $support = createConsoleUserWithRole(Role::SUPPORT);

    $this->actingAs($support)
        ->get(route('console.dashboard'))
        ->assertOk()
        ->assertDontSee('TIPS');

    $this->actingAs($support)
        ->get(route('console.tips.index'))
        ->assertForbidden();

    $tip = Tip::query()->create([
        'user_id' => $support->id,
        'title' => '지원 담당자 팁',
        'content' => '<p>지원 담당자 팁 본문</p>',
        'status' => Tip::STATUS_DRAFT,
        'audience' => Tip::AUDIENCE_PRIVATE,
    ]);

    $this->actingAs($support)
        ->get(route('console.tips.create'))
        ->assertForbidden();

    $this->actingAs($support)
        ->get(route('console.tips.edit', $tip))
        ->assertForbidden();
});

test('content managers can manage tips', function () {
    $contentManager = createConsoleUserWithRole(Role::CONTENT_MANAGER);

    $this->actingAs($contentManager)
        ->get(route('console.dashboard'))
        ->assertOk()
        ->assertSee('TIPS');

    $this->actingAs($contentManager)
        ->get(route('console.tips.index'))
        ->assertOk()
        ->assertSee('TIPS');
});

test('admin users can manage tips', function () {
    $admin = createConsoleUserWithRole(Role::ADMIN);

    $this->actingAs($admin)
        ->get(route('console.tips.index'))
        ->assertOk()
        ->assertSee('TIPS');
});

test('tip creation and update policy follows general ownership rules', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $admin = createConsoleUserWithRole(Role::ADMIN);

    $tip = Tip::query()->create([
        'user_id' => $owner->id,
        'title' => '작성자 팁',
        'content' => '<p>작성자 팁 본문</p>',
        'status' => Tip::STATUS_DRAFT,
        'audience' => Tip::AUDIENCE_PRIVATE,
    ]);

    expect($owner->can('create', Tip::class))->toBeTrue()
        ->and($owner->can('update', $tip))->toBeTrue()
        ->and($owner->can('delete', $tip))->toBeTrue()
        ->and($otherUser->can('update', $tip))->toBeFalse()
        ->and($otherUser->can('delete', $tip))->toBeFalse()
        ->and($admin->can('update', $tip))->toBeTrue()
        ->and($admin->can('delete', $tip))->toBeTrue();
});

test('tip managers can see the tip search and list area', function () {
    $contentManager = createConsoleUserWithRole(Role::CONTENT_MANAGER);

    $this->actingAs($contentManager)
        ->get(route('console.tips.index'))
        ->assertOk()
        ->assertSee('카테고리')
        ->assertSee('노출')
        ->assertSee('상태')
        ->assertSee('태그')
        ->assertSee('기간')
        ->assertSee('검색어')
        ->assertSee('팁 목록')
        ->assertSee('초기화')
        ->assertSee('검색');
});

test('tip managers can see the tip summary and creation actions', function () {
    $contentManager = createConsoleUserWithRole(Role::CONTENT_MANAGER);

    Carbon::setTestNow('2026-05-03 09:00:00');

    Tip::query()->create([
        'user_id' => $contentManager->id,
        'title' => '첫 번째 팁',
        'content' => '<p>첫 번째 팁 본문</p>',
        'status' => Tip::STATUS_DRAFT,
    ]);

    Tip::query()->create([
        'user_id' => $contentManager->id,
        'title' => '두 번째 팁',
        'content' => '<p>두 번째 팁 본문</p>',
        'status' => Tip::STATUS_PUBLISHED,
    ]);

    $this->actingAs($contentManager)
        ->get(route('console.tips.index'))
        ->assertOk()
        ->assertSee('Tips 관리')
        ->assertSee('총 2개')
        ->assertSee('최근 수정:')
        ->assertSee('2026-05-03')
        ->assertSee('AI로 팁 추가')
        ->assertSee('Tip 추가')
        ->assertSee(route('console.tips.create'), false);
});

test('tip managers can use the shared create and edit form screens', function () {
    $contentManager = createConsoleUserWithRole(Role::CONTENT_MANAGER);

    $tip = Tip::query()->create([
        'user_id' => $contentManager->id,
        'title' => '수정할 팁',
        'content' => '<p>수정할 팁 본문</p>',
        'status' => Tip::STATUS_DRAFT,
        'audience' => Tip::AUDIENCE_PRIVATE,
    ]);

    $this->actingAs($contentManager)
        ->get(route('console.tips.create'))
        ->assertOk()
        ->assertSee('Tip 추가')
        ->assertSee('새 팁을 작성합니다.')
        ->assertSee('등록 저장');

    $this->actingAs($contentManager)
        ->get(route('console.tips.edit', $tip))
        ->assertOk()
        ->assertSee('Tip 수정')
        ->assertSee('수정할 팁')
        ->assertSee('수정 저장');
});

test('authenticated users can create tips from the front page link', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee(route('tips.create'), false)
        ->assertSee('팁 작성');

    $this->actingAs($user)
        ->get(route('tips.create'))
        ->assertOk()
        ->assertSee('팁 작성')
        ->assertSee(route('tips.store'), false)
        ->assertSee('등록 저장');

    $this->actingAs($user)
        ->post(route('tips.store'), [
            'title' => '프론트에서 작성한 팁',
            'content' => '<p>프론트 작성 화면에서 저장합니다.</p>',
            'category_id' => '',
            'status' => Tip::STATUS_DRAFT,
            'audience' => Tip::AUDIENCE_PRIVATE,
        ])
        ->assertRedirect()
        ->assertSessionHas('status', '팁이 저장되었습니다.');

    $tip = Tip::query()->where('title', '프론트에서 작성한 팁')->firstOrFail();

    expect($tip->user_id)->toBe($user->id)
        ->and($tip->status)->toBe(Tip::STATUS_DRAFT)
        ->and($tip->audience)->toBe(Tip::AUDIENCE_PRIVATE);
});

test('tip content keeps summernote image size styles while removing unsafe styles', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('tips.store'), [
            'title' => '이미지 크기 저장 팁',
            'content' => '<p>본문</p><img src="/body.jpg" alt="본문 이미지" style="width: 48%; height: auto; position: fixed;" data-media-id="123">',
            'category_id' => '',
            'status' => Tip::STATUS_DRAFT,
            'audience' => Tip::AUDIENCE_PRIVATE,
        ])
        ->assertRedirect();

    $tip = Tip::query()->where('title', '이미지 크기 저장 팁')->firstOrFail();

    expect($tip->content)->toContain('style="width:48%;height:auto;"')
        ->and($tip->content)->toContain('data-media-id="123"')
        ->and($tip->content)->not->toContain('position');
});

test('tip owners can see edit and delete actions on the tip detail page', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $tip = Tip::query()->create([
        'user_id' => $owner->id,
        'title' => '상세 액션 팁',
        'content' => '<p>상세에서 수정 삭제 버튼을 확인합니다.</p>',
        'status' => Tip::STATUS_PUBLISHED,
        'audience' => Tip::AUDIENCE_PUBLIC,
    ]);

    $this->get(route('tips.show', $tip))
        ->assertOk()
        ->assertDontSee(route('tips.edit', $tip), false)
        ->assertDontSee(route('tips.destroy', $tip), false);

    $this->actingAs($otherUser)
        ->get(route('tips.show', $tip))
        ->assertOk()
        ->assertDontSee(route('tips.edit', $tip), false)
        ->assertDontSee(route('tips.destroy', $tip), false);

    $this->actingAs($owner)
        ->get(route('tips.show', $tip))
        ->assertOk()
        ->assertSee(route('tips.edit', $tip), false)
        ->assertSee(route('tips.destroy', $tip), false)
        ->assertSee('팁 삭제');
});

test('tip owners can use front edit and delete routes', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $tip = Tip::query()->create([
        'user_id' => $owner->id,
        'title' => '상세에서 수정할 팁',
        'content' => '<p>상세 수정 대상입니다.</p>',
        'status' => Tip::STATUS_PUBLISHED,
        'audience' => Tip::AUDIENCE_PUBLIC,
    ]);

    $this->actingAs($otherUser)
        ->get(route('tips.edit', $tip))
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('tips.edit', $tip))
        ->assertOk()
        ->assertSee('팁 수정')
        ->assertSee(route('tips.update', $tip), false);

    $this->actingAs($owner)
        ->put(route('tips.update', $tip), [
            'title' => '상세에서 수정 완료',
            'content' => '<p>수정된 본문입니다.</p>',
            'category_id' => '',
            'status' => Tip::STATUS_DRAFT,
            'audience' => Tip::AUDIENCE_PRIVATE,
        ])
        ->assertRedirect(route('tips.show', $tip))
        ->assertSessionHas('status', '팁이 수정되었습니다.');

    expect($tip->refresh()->title)->toBe('상세에서 수정 완료');

    $this->actingAs($otherUser)
        ->delete(route('tips.destroy', $tip))
        ->assertForbidden();

    $this->actingAs($owner)
        ->delete(route('tips.destroy', $tip))
        ->assertRedirect(route('home'))
        ->assertSessionHas('status', '팁이 삭제되었습니다.');

    $this->assertSoftDeleted($tip);
});

test('tip managers can create draft tips without a category', function () {
    $contentManager = createConsoleUserWithRole(Role::CONTENT_MANAGER);

    $this->actingAs($contentManager)
        ->post(route('console.tips.store'), [
            'title' => '임시저장 팁',
            'content' => '<p>카테고리 없이 임시저장합니다.</p>',
            'category_id' => '',
            'status' => Tip::STATUS_DRAFT,
            'audience' => Tip::AUDIENCE_PRIVATE,
            'tag_names' => ['정리', '생활팁'],
        ])
        ->assertRedirect();

    $tip = Tip::query()->where('title', '임시저장 팁')->firstOrFail();

    expect($tip->user_id)->toBe($contentManager->id)
        ->and($tip->category_id)->toBeNull()
        ->and($tip->tags()->pluck('name')->all())->toBe(['정리', '생활팁']);
});

test('published tips require a category', function () {
    $contentManager = createConsoleUserWithRole(Role::CONTENT_MANAGER);

    $this->actingAs($contentManager)
        ->from(route('console.tips.create'))
        ->post(route('console.tips.store'), [
            'title' => '발행할 팁',
            'content' => '<p>카테고리 없이 발행하려는 팁입니다.</p>',
            'category_id' => '',
            'status' => Tip::STATUS_PUBLISHED,
            'audience' => Tip::AUDIENCE_PUBLIC,
        ])
        ->assertRedirect(route('console.tips.create'))
        ->assertSessionHasErrors('category_id');

    expect(Tip::query()->where('title', '발행할 팁')->exists())->toBeFalse();
});

test('tip managers can store a thumbnail when creating a tip', function () {
    Storage::fake('public');
    config()->set('media.disk', 'public');

    $contentManager = createConsoleUserWithRole(Role::CONTENT_MANAGER);
    $category = Category::query()->create([
        'name' => '생활',
        'slug' => 'life',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $this->actingAs($contentManager)
        ->post(route('console.tips.store'), [
            'title' => '썸네일 팁',
            'content' => '<p>썸네일을 저장합니다.</p>',
            'category_id' => $category->id,
            'status' => Tip::STATUS_PUBLISHED,
            'audience' => Tip::AUDIENCE_PUBLIC,
            'thumbnail' => UploadedFile::fake()->image('thumbnail.jpg', 1200, 628),
        ])
        ->assertRedirect();

    $tip = Tip::query()->where('title', '썸네일 팁')->firstOrFail();
    $thumbnail = Media::query()
        ->where('owner_type', $tip->getMorphClass())
        ->where('owner_id', $tip->id)
        ->where('collection', MediaCollection::TipThumbnail->value)
        ->firstOrFail();

    expect($thumbnail->uploaded_by_id)->toBe($contentManager->id)
        ->and($thumbnail->status)->toBe(Media::STATUS_ATTACHED);

    Storage::disk('public')->assertExists($thumbnail->path);
});

test('tip managers can replace an existing thumbnail when updating a tip', function () {
    Storage::fake('public');
    config()->set('media.disk', 'public');

    $contentManager = createConsoleUserWithRole(Role::CONTENT_MANAGER);
    $tip = Tip::query()->create([
        'user_id' => $contentManager->id,
        'title' => '교체 전 팁',
        'content' => '<p>교체 전 본문</p>',
        'status' => Tip::STATUS_DRAFT,
        'audience' => Tip::AUDIENCE_PRIVATE,
    ]);

    Storage::disk('public')->put('tips/'.$tip->id.'/thumbnail/old.jpg', 'old');
    $oldThumbnail = Media::query()->create([
        'disk' => 'public',
        'path' => 'tips/'.$tip->id.'/thumbnail/old.jpg',
        'collection' => MediaCollection::TipThumbnail->value,
        'original_name' => 'old.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 3,
        'owner_type' => $tip->getMorphClass(),
        'owner_id' => $tip->id,
        'uploaded_by_id' => $contentManager->id,
        'status' => Media::STATUS_ATTACHED,
        'visibility' => Media::VISIBILITY_PUBLIC,
    ]);

    $this->actingAs($contentManager)
        ->put(route('console.tips.update', $tip), [
            'title' => '교체 후 팁',
            'content' => '<p>교체 후 본문</p>',
            'category_id' => '',
            'status' => Tip::STATUS_DRAFT,
            'audience' => Tip::AUDIENCE_PRIVATE,
            'thumbnail' => UploadedFile::fake()->image('new.jpg', 1200, 628),
        ])
        ->assertRedirect(route('console.tips.edit', $tip));

    expect($oldThumbnail->fresh()->trashed())->toBeTrue()
        ->and($tip->refresh()->title)->toBe('교체 후 팁');

    Storage::disk('public')->assertMissing('tips/'.$tip->id.'/thumbnail/old.jpg');

    $newThumbnail = Media::query()
        ->where('owner_type', $tip->getMorphClass())
        ->where('owner_id', $tip->id)
        ->where('collection', MediaCollection::TipThumbnail->value)
        ->firstOrFail();

    Storage::disk('public')->assertExists($newThumbnail->path);
});

test('tip managers can delete an existing thumbnail when updating a tip', function () {
    Storage::fake('public');
    config()->set('media.disk', 'public');

    $contentManager = createConsoleUserWithRole(Role::CONTENT_MANAGER);
    $tip = Tip::query()->create([
        'user_id' => $contentManager->id,
        'title' => '삭제할 썸네일 팁',
        'content' => '<p>본문</p>',
        'status' => Tip::STATUS_DRAFT,
        'audience' => Tip::AUDIENCE_PRIVATE,
    ]);

    Storage::disk('public')->put('tips/'.$tip->id.'/thumbnail/delete.jpg', 'delete');
    $thumbnail = Media::query()->create([
        'disk' => 'public',
        'path' => 'tips/'.$tip->id.'/thumbnail/delete.jpg',
        'collection' => MediaCollection::TipThumbnail->value,
        'original_name' => 'delete.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 6,
        'owner_type' => $tip->getMorphClass(),
        'owner_id' => $tip->id,
        'uploaded_by_id' => $contentManager->id,
        'status' => Media::STATUS_ATTACHED,
        'visibility' => Media::VISIBILITY_PUBLIC,
    ]);

    $this->actingAs($contentManager)
        ->put(route('console.tips.update', $tip), [
            'title' => '삭제 후 팁',
            'content' => '<p>본문 수정</p>',
            'category_id' => '',
            'status' => Tip::STATUS_DRAFT,
            'audience' => Tip::AUDIENCE_PRIVATE,
            'delete_thumbnail' => '1',
        ])
        ->assertRedirect(route('console.tips.edit', $tip));

    expect($thumbnail->fresh()->trashed())->toBeTrue()
        ->and(Media::query()
            ->where('owner_type', $tip->getMorphClass())
            ->where('owner_id', $tip->id)
            ->where('collection', MediaCollection::TipThumbnail->value)
            ->exists())->toBeFalse();

    Storage::disk('public')->assertMissing('tips/'.$tip->id.'/thumbnail/delete.jpg');
});

test('authenticated users can upload a temporary tip body image', function () {
    Storage::fake('public');
    config()->set('media.disk', 'public');

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('editor.images.store'), [
            'image' => UploadedFile::fake()->image('body.jpg', 800, 600),
        ])
        ->assertOk()
        ->assertJsonStructure([
            'id',
            'url',
            'alt',
        ]);

    $media = Media::query()->findOrFail($response->json('id'));

    expect($media->collection)->toBe(MediaCollection::TipBody->value)
        ->and($media->uploaded_by_id)->toBe($user->id)
        ->and($media->status)->toBe(Media::STATUS_TEMPORARY)
        ->and($media->owner_type)->toBeNull()
        ->and($media->owner_id)->toBeNull()
        ->and($media->original_name)->toBe('body.jpg');

    Storage::disk('public')->assertExists($media->path);
});

test('tip managers can attach uploaded body images when creating a tip', function () {
    Storage::fake('public');
    config()->set('media.disk', 'public');

    $contentManager = createConsoleUserWithRole(Role::CONTENT_MANAGER);

    Storage::disk('public')->put('media/temporary/tips/body/body.jpg', 'body');
    $bodyImage = Media::query()->create([
        'disk' => 'public',
        'path' => 'media/temporary/tips/body/body.jpg',
        'collection' => MediaCollection::TipBody->value,
        'original_name' => 'body.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 4,
        'uploaded_by_id' => $contentManager->id,
        'status' => Media::STATUS_TEMPORARY,
        'visibility' => Media::VISIBILITY_PUBLIC,
    ]);

    $this->actingAs($contentManager)
        ->post(route('console.tips.store'), [
            'title' => '본문 이미지 팁',
            'content' => '<p>본문</p><img src="/body.jpg" data-media-id="'.$bodyImage->id.'">',
            'category_id' => '',
            'status' => Tip::STATUS_DRAFT,
            'audience' => Tip::AUDIENCE_PRIVATE,
            'uploaded_body_image_ids' => [$bodyImage->id],
        ])
        ->assertRedirect();

    $tip = Tip::query()->where('title', '본문 이미지 팁')->firstOrFail();

    expect($bodyImage->refresh()->owner_type)->toBe($tip->getMorphClass())
        ->and($bodyImage->owner_id)->toBe($tip->id)
        ->and($bodyImage->status)->toBe(Media::STATUS_ATTACHED);

    Storage::disk('public')->assertExists($bodyImage->path);
});

test('tip managers can delete uploaded body images removed before saving', function () {
    Storage::fake('public');
    config()->set('media.disk', 'public');

    $contentManager = createConsoleUserWithRole(Role::CONTENT_MANAGER);

    Storage::disk('public')->put('media/temporary/tips/body/kept.jpg', 'kept');
    Storage::disk('public')->put('media/temporary/tips/body/removed.jpg', 'removed');

    $keptImage = Media::query()->create([
        'disk' => 'public',
        'path' => 'media/temporary/tips/body/kept.jpg',
        'collection' => MediaCollection::TipBody->value,
        'original_name' => 'kept.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 4,
        'uploaded_by_id' => $contentManager->id,
        'status' => Media::STATUS_TEMPORARY,
        'visibility' => Media::VISIBILITY_PUBLIC,
    ]);

    $removedImage = Media::query()->create([
        'disk' => 'public',
        'path' => 'media/temporary/tips/body/removed.jpg',
        'collection' => MediaCollection::TipBody->value,
        'original_name' => 'removed.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 7,
        'uploaded_by_id' => $contentManager->id,
        'status' => Media::STATUS_TEMPORARY,
        'visibility' => Media::VISIBILITY_PUBLIC,
    ]);

    $this->actingAs($contentManager)
        ->post(route('console.tips.store'), [
            'title' => '본문 이미지 삭제 팁',
            'content' => '<p>본문</p><img src="/kept.jpg" data-media-id="'.$keptImage->id.'">',
            'category_id' => '',
            'status' => Tip::STATUS_DRAFT,
            'audience' => Tip::AUDIENCE_PRIVATE,
            'uploaded_body_image_ids' => [$keptImage->id, $removedImage->id],
        ])
        ->assertRedirect();

    $tip = Tip::query()->where('title', '본문 이미지 삭제 팁')->firstOrFail();

    expect($keptImage->refresh()->owner_type)->toBe($tip->getMorphClass())
        ->and($keptImage->owner_id)->toBe($tip->id)
        ->and($keptImage->status)->toBe(Media::STATUS_ATTACHED)
        ->and($removedImage->fresh()->trashed())->toBeTrue();

    Storage::disk('public')->assertExists($keptImage->path);
    Storage::disk('public')->assertMissing('media/temporary/tips/body/removed.jpg');
});

test('tip managers can delete attached body images removed while updating', function () {
    Storage::fake('public');
    config()->set('media.disk', 'public');

    $contentManager = createConsoleUserWithRole(Role::CONTENT_MANAGER);
    $tip = Tip::query()->create([
        'user_id' => $contentManager->id,
        'title' => '수정 전 본문 이미지 팁',
        'content' => '<p>본문</p>',
        'status' => Tip::STATUS_DRAFT,
        'audience' => Tip::AUDIENCE_PRIVATE,
    ]);

    Storage::disk('public')->put('tips/'.$tip->id.'/body/kept.jpg', 'kept');
    Storage::disk('public')->put('tips/'.$tip->id.'/body/removed.jpg', 'removed');

    $keptImage = Media::query()->create([
        'disk' => 'public',
        'path' => 'tips/'.$tip->id.'/body/kept.jpg',
        'collection' => MediaCollection::TipBody->value,
        'original_name' => 'kept.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 4,
        'owner_type' => $tip->getMorphClass(),
        'owner_id' => $tip->id,
        'uploaded_by_id' => $contentManager->id,
        'status' => Media::STATUS_ATTACHED,
        'visibility' => Media::VISIBILITY_PUBLIC,
    ]);

    $removedImage = Media::query()->create([
        'disk' => 'public',
        'path' => 'tips/'.$tip->id.'/body/removed.jpg',
        'collection' => MediaCollection::TipBody->value,
        'original_name' => 'removed.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 7,
        'owner_type' => $tip->getMorphClass(),
        'owner_id' => $tip->id,
        'uploaded_by_id' => $contentManager->id,
        'status' => Media::STATUS_ATTACHED,
        'visibility' => Media::VISIBILITY_PUBLIC,
    ]);

    $this->actingAs($contentManager)
        ->put(route('console.tips.update', $tip), [
            'title' => '수정 후 본문 이미지 팁',
            'content' => '<p>본문 수정</p><img src="/kept.jpg" data-media-id="'.$keptImage->id.'">',
            'category_id' => '',
            'status' => Tip::STATUS_DRAFT,
            'audience' => Tip::AUDIENCE_PRIVATE,
        ])
        ->assertRedirect(route('console.tips.edit', $tip));

    expect($keptImage->fresh()->trashed())->toBeFalse()
        ->and($removedImage->fresh()->trashed())->toBeTrue();

    Storage::disk('public')->assertExists($keptImage->path);
    Storage::disk('public')->assertMissing('tips/'.$tip->id.'/body/removed.jpg');
});

test('tip body image sync ignores media uploaded by another user', function () {
    Storage::fake('public');
    config()->set('media.disk', 'public');

    $contentManager = createConsoleUserWithRole(Role::CONTENT_MANAGER);
    $otherUser = User::factory()->create();

    Storage::disk('public')->put('media/temporary/tips/body/other.jpg', 'other');
    $otherImage = Media::query()->create([
        'disk' => 'public',
        'path' => 'media/temporary/tips/body/other.jpg',
        'collection' => MediaCollection::TipBody->value,
        'original_name' => 'other.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 5,
        'uploaded_by_id' => $otherUser->id,
        'status' => Media::STATUS_TEMPORARY,
        'visibility' => Media::VISIBILITY_PUBLIC,
    ]);

    $this->actingAs($contentManager)
        ->post(route('console.tips.store'), [
            'title' => '타인 이미지 방어 팁',
            'content' => '<p>본문</p><img src="/other.jpg" data-media-id="'.$otherImage->id.'">',
            'category_id' => '',
            'status' => Tip::STATUS_DRAFT,
            'audience' => Tip::AUDIENCE_PRIVATE,
            'uploaded_body_image_ids' => [$otherImage->id],
        ])
        ->assertRedirect();

    expect($otherImage->refresh()->owner_type)->toBeNull()
        ->and($otherImage->owner_id)->toBeNull()
        ->and($otherImage->status)->toBe(Media::STATUS_TEMPORARY);

    Storage::disk('public')->assertExists($otherImage->path);
});
