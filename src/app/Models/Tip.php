<?php

namespace App\Models;

use App\Enums\MediaCollection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $content
 * @property string $status
 * @property string $audience
 * @property int|null $category_id
 * @property int $view_count
 * @property int $like_count
 * @property int $bookmark_count
 * @property int $comment_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'user_id',
    'category_id',
    'title',
    'content',
    'status',
    'audience',
    'view_count',
    'like_count',
    'bookmark_count',
    'comment_count',
])]
class Tip extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const AUDIENCE_PUBLIC = 'public';

    public const AUDIENCE_PREMIUM = 'premium';

    public const AUDIENCE_PRIVATE = 'private';

    /**
     * 팁 상태로 혀용하는 값 목록
     *
     * @var array<int, string>
     */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
    ];

    /**
     * 팁 노출 대상으로 허용하는 값 목록
     *
     * @var array<int, string>
     */
    public const AUDIENCES = [
        self::AUDIENCE_PUBLIC,
        self::AUDIENCE_PREMIUM,
        self::AUDIENCE_PRIVATE,
    ];

    // 팁 작성자
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 공개 팁인지 확인
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    // 팁이 속한 카테고리
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // 팁에 연결된 태그 목록
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    // 전달된 상태 값이 허용된 팁 상태인지 확인
    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::STATUSES, true);
    }

    // 전달된 노출 값이 허용된 audience 인지 확인
    public static function isValidAudience(string $audience): bool
    {
        return in_array($audience, self::AUDIENCES, true);
    }

    // 팁에 연결된 모든 미디어 파일
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'owner');
    }

    // 현재 팁 썸네일 이미지
    public function thumbnail(): MorphOne
    {
        return $this->morphOne(Media::class, 'owner')
            ->where('collection', MediaCollection::TipThumbnail->value)
            ->latestOfMany();
    }

    // 팁 본문에 삽입된 이미지 목록
    public function bodyImages() : MorphMany
    {
        return $this->morphMany(Media::class, 'owner')
            ->where('collection', MediaCollection::TipBody->value);
    }


}
