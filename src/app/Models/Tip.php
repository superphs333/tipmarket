<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 * @property bool $allow_comments
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
    'allow_comments',
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

    protected function casts(): array
    {
        return [
            'allow_comments' => 'boolean',
        ];
    }

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
}
