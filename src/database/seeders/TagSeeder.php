<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * TipMarket 기본 태그 더미 데이터를 생성한다.
     */
    public function run(): void
    {
        $tags = [
            ['name' => '욕실', 'slug' => 'bathroom', 'description' => '욕실 관리와 청소 관련 팁'],
            ['name' => '곰팡이', 'slug' => 'mold', 'description' => '곰팡이 제거와 예방 팁'],
            ['name' => '냄새제거', 'slug' => 'odor-removal', 'description' => '생활 공간 냄새 제거 팁'],
            ['name' => '베이킹소다', 'slug' => 'baking-soda', 'description' => '베이킹소다 활용 팁'],
            ['name' => '식초', 'slug' => 'vinegar', 'description' => '식초를 활용한 생활 팁'],
            ['name' => '전자레인지', 'slug' => 'microwave', 'description' => '전자레인지 관리와 활용 팁'],
            ['name' => '냉장고', 'slug' => 'refrigerator', 'description' => '냉장고 정리와 보관 팁'],
            ['name' => '절약', 'slug' => 'saving-tip', 'description' => '생활비 절약 팁'],
            ['name' => '정리정돈', 'slug' => 'organizing', 'description' => '공간 정리와 수납 팁'],
            ['name' => '초보자', 'slug' => 'beginner', 'description' => '처음 따라 하기 쉬운 팁'],
        ];

        foreach ($tags as $tag) {
            Tag::query()->updateOrCreate(
                ['slug' => $tag['slug']],
                [
                    'name' => $tag['name'],
                    'description' => $tag['description'],
                    'usage_count' => 0,
                    'is_active' => true,
                ],
            );
        }
    }
}