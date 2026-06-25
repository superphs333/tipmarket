<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property array<string, mixed> $content
 * @property string $status
 * @property int|null $category_id
 * @property Carbon|null $published_at
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
    'published_at',
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
    public const STATUS_HIDDEN = 'hidden';
    public const STATUS_ARCHIVED = 'archived'; 

    protected function casts() : array
    {
        return [
            'content' => 'array',
            'published_at' => 'datetime',
            'allow_comments' => 'boolean',
        ];
    }

    // 팁 작성자
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 공개 팁인지 확인
    public function isPublished() : bool
    {
        return $this->status === self::STATUS_PUBLISHED && $this->published_at !== null;
    }

    // 팁이 속한 카테고리
    public function category() : BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
