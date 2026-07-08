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
use App\Models\User;

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

    /**
     * 화면에서 사용할 팁 상태 option 목록.
     *
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => '임시저장',
            self::STATUS_PUBLISHED => '발행',
        ];
    }

    /**
     * 화면에서 사용할 팁 노출 option 목록.
     *
     * @return array<string, string>
     */
    public static function audienceOptions(): array
    {
        return [
            self::AUDIENCE_PUBLIC => '전체공개',
            // self::AUDIENCE_PREMIUM => '프리미엄',
            self::AUDIENCE_PRIVATE => '비공개',
        ];
    }

    // 전달된 사용자가 이 팁의 작성자인지 확인
    public function isOwnedBy(User $user) : bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * 공개 여부 관련
     */
    // 발행 상태인지 호가인 
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
    // 전체 공개 대상인지 확인
    public function isPublic() : bool
    {
        return $this->audience === self::AUDIENCE_PUBLIC;
    }
    // 프리미엄 대상인지 확인
    public function isPremium() : bool
    {
        return $this->audience === self::AUDIENCE_PREMIUM;
    }
    // 비공개 대상인지 확인
    public function isPrivate() : bool
    {
        return $this->audience === self::AUDIENCE_PRIVATE;
    }

    // 팁 작성자
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
