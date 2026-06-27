<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * 서비스에서 사용하는 추가 역할을 생성함.
     */
    public function run(): void
    {
        $roles = [
            Role::ADMIN => [
                'label' => '관리자',
                'description' => '모든 운영 콘솔 기능에 접근할 수 있는 최고 권한 역할',
            ],
            Role::CONTENT_MANAGER => [
                'label' => '콘텐츠 매니저',
                'description' => '팁과 콘텐츠 운영 기능에 접근할 수 있는 역할',
            ],
            Role::MODERATOR => [
                'label' => '운영자',
                'description' => '신고 처리와 커뮤니티 운영 기능에 접근할 수 있는 역할',
            ],
            Role::SUPPORT => [
                'label' => '고객 지원',
                'description' => '사용자 문의와 지원 기능에 접근할 수 있는 역할',
            ],
        ];

        foreach ($roles as $name => $role) {
            Role::query()->updateOrCreate(
                ['name' => $name],
                $role,
            );
        }
    }
}
