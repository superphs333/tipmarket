<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TipPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(Role::tipManagementRoles());
    }

    /**
     * Determine whether the user can manage tips in the console.
     */
    public function manageInConsole(User $user): bool
    {
        return $user->hasAnyRole(Role::tipManagementRoles());
    }

    /**
     * 사용자가 팁 상세 페이지를 볼 수 있는지 판단
     * - draft/private => 내부 상태이므로, 존재 여부 숨기기 위해 404
     * - premium => 존재는 알리고 403으로 막기.
     */
    public function view(?User $user, Tip $tip): Response
    {
        // 작성자와 팁 관리 권한자는 공개 상태와 무관하게 상세를 볼 수 있음
        if ($user !== null && ($tip->isOwnedBy($user) || $user->hasAnyRole(Role::tipManagementRoles()))) {
            return Response::allow();
        }

        if (! $tip->isPublished() || $tip->isPrivate()) {
            return Response::denyAsNotFound();
        }

        if ($tip->isPublic()) {
            return Response::allow();
        }

        if ($tip->isPremium()) {
            return Response::deny('프리미엄 접근 권한이 필요합니다.');
        }

        // 정의되지 않은 audience 값은 안전하게 숨김
        return Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tip $tip): bool
    {
        return $tip->user_id === $user->id
            || $user->hasAnyRole(Role::tipManagementRoles());
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tip $tip): bool
    {
        return $tip->user_id === $user->id
            || $user->hasAnyRole(Role::tipManagementRoles());
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Tip $tip): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Tip $tip): bool
    {
        return false;
    }
}
