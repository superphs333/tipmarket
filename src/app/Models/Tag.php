<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * 팁에 연결되는 세부 키워드 태그.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $usage_count
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'slug',
    'description',
    'usage_count',
    'is_active',
])]

class Tag extends Model
{
    protected function casts() : array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // 이 태그가 연결된 팁 목록
    public function tips(): BelongsToMany
    {
        return $this->belongsToMany(Tip::class)->withTimestamps();
    }

    // 현재 태그를 사용자에게 노출할 수 있는지 확인
    public function isActive(): bool
    {
        return $this->is_active;
    }
}
