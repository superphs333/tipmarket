<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 댓글과 대댓글의 기본 관계와 상태 판단을 담당한다.
 *
 * 실제 작성, 삭제 처리, 카운터 갱신처럼 여러 모델이 함께 바뀌는 흐름은
 * Comment 모델에 넣지 않고 Action 또는 Service에서 트랜잭션으로 처리한다.
 *
 * @property int $id
 * @property int $tip_id
 * @property int $user_id
 * @property int|null $parent_id
 * @property int|null $reply_to_id
 * @property int $depth
 * @property string $body
 * @property string $status
 * @property int $like_count
 * @property int $reply_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'tip_id',
    'user_id',
    'parent_id',
    'reply_to_id',
    'depth',
    'body',
    'status',
    'like_count',
    'reply_count',
])]
class Comment extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DELETED = 'deleted';

    public const STATUS_HIDDEN = 'hidden';

    /**
     * 댓글 상태로 허용하는 값 목록.
     *
     * @var array<int, string>
     */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_DELETED,
        self::STATUS_HIDDEN,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'depth' => 'integer',
            'like_count' => 'integer',
            'reply_count' => 'integer',
        ];
    }

    // 댓글이 달린 팁
    public function tip(): BelongsTo
    {
        return $this->belongsTo(Tip::class);
    }

    // 댓글 작성자
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 대댓글인 경우 연결된 원댓글
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    // 이 댓글에 달린 대댓글 목록
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('created_at');
    }

    // 실제 답글 대상 댓글. 멘션/답글 표시용으로 parent와 다를 수 있다.
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    // 이 댓글을 reply_to 대상으로 삼은 댓글 목록
    public function replyTargets(): HasMany
    {
        return $this->hasMany(self::class, 'reply_to_id');
    }

    // 원댓글인지 확인
    public function isRoot(): bool
    {
        return $this->parent_id === null && $this->depth === 0;
    }

    // 대댓글인지 확인
    public function isReply(): bool
    {
        return $this->parent_id !== null || $this->depth > 0;
    }

    // 화면에 정상 노출 가능한 활성 댓글인지 확인
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    // 작성자 삭제 처리된 댓글인지 확인
    public function isDeleted(): bool
    {
        return $this->status === self::STATUS_DELETED;
    }

    // 관리자 또는 정책에 의해 숨김 처리된 댓글인지 확인
    public function isHidden(): bool
    {
        return $this->status === self::STATUS_HIDDEN;
    }

    // 전달된 상태 값이 허용된 댓글 상태인지 확인
    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::STATUSES, true);
    }
}