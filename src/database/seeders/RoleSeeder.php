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
        Role::query()->updateOrCreate(
            ['name' => Role::ADMIN],
            [
                'label' => '관리자',
                'description' => '관리자 기능 접근 및 운영 권한을 가진 사용자',
            ]
        );
    }
}
