<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'label', 'description'])]
class Role extends Model
{
    // 관리자 역할 키
    public const ADMIN = 'admin';

    public const CONTENT_MANAGER = 'content_manager';

    public const MODERATOR = 'moderator';

    public const SUPPORT = 'support';

    /**
     * 운영 콘솔 자체에 들어올 수 있는 역할 목록
     *
     * 세부 메뉴와 액션 권한은 라우트/정책에서 별도로 좁힌다.
     *
     * @return list<string>
     */
    public static function consoleAccessRoles(): array
    {
        return [
            self::ADMIN,
            self::CONTENT_MANAGER,
            self::MODERATOR,
            self::SUPPORT,
        ];
    }

    /**
     * 팁 운영 메뉴와 목록에 접근할 수 있는 역할 목록
     *
     * @return list<string>
     */
    public static function tipManagementRoles(): array
    {
        return [
            self::ADMIN,
            self::CONTENT_MANAGER,
        ];
    }

    /**
     * 이 역할을 가진 사용자 목록
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
