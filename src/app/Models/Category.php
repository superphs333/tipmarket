<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 팁 분류용 카테고리.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'parent_id',
    'name',
    'slug',
    'description',
    'sort_order',
    'is_active',
])]
class Category extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // 상위 카테고리
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // 하위 카테고리 목록
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * 이 카테고리에 속한 팁 목록
     */
    public function tips(): HasMany
    {
        return $this->hasMany(Tip::class);
    }

    // 현재 카테고리를 사용자에게 노출할 수 있는지 확인
    public function isActive(): bool
    {
        return $this->is_active;
    }

    // 조건 : 활성화 여부
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // 카테고리 리스트
    public function scopeForSelect(Builder $query): Builder
    {
        return $query
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->select(['id', 'name']);
    }
}
