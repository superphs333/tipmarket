<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * TipMarket 기본 카테고리 더미 데이터를 생성한다.
     *
     * Seeder는 개발/초기 운영에 필요한 기본 데이터를 DB에 넣는 클래스다.
     * 이 메서드는 아래 명령으로 실행할 수 있다.
     *
     * php artisan db:seed --class=CategorySeeder
     */
    public function run(): void
    {
        // 여러 카테고리를 한 번에 다루기 위해 배열로 정의한다.
        // 각 내부 배열의 key는 categories 테이블 컬럼명과 맞춘다.
        $categories = [
            [
                'name' => '생활',
                'slug' => 'life',
                'description' => '일상에서 바로 활용할 수 있는 생활 팁',
                'sort_order' => 10,
            ],
            [
                'name' => '청소',
                'slug' => 'cleaning',
                'description' => '집안 청소, 정리, 오염 제거 관련 팁',
                'sort_order' => 20,
            ],
            [
                'name' => '요리',
                'slug' => 'cooking',
                'description' => '재료 손질, 보관, 조리 노하우',
                'sort_order' => 30,
            ],
            [
                'name' => '절약',
                'slug' => 'saving',
                'description' => '고정비, 장보기, 소비 습관 절약 팁',
                'sort_order' => 40,
            ],
            [
                'name' => '수리',
                'slug' => 'repair',
                'description' => '간단한 고장 해결과 유지보수 팁',
                'sort_order' => 50,
            ],
            [
                'name' => '디지털',
                'slug' => 'digital',
                'description' => '앱, 기기, 온라인 서비스 활용 팁',
                'sort_order' => 60,
            ],
            [
                'name' => '건강',
                'slug' => 'health',
                'description' => '생활 습관과 건강 관리 팁',
                'sort_order' => 70,
            ],
            [
                'name' => '육아',
                'slug' => 'parenting',
                'description' => '아이 돌봄과 가족 생활 팁',
                'sort_order' => 80,
            ],
            [
                'name' => '반려생활',
                'slug' => 'pet-life',
                'description' => '반려동물과 함께 사는 생활 팁',
                'sort_order' => 90,
            ],
            [
                'name' => '기타',
                'slug' => 'etc',
                'description' => '다른 카테고리에 속하지 않는 팁',
                'sort_order' => 999,
            ],
        ];

        // foreach는 배열의 값을 하나씩 꺼내 반복한다.
        // 여기서는 $categories 안의 각 카테고리 배열이 $category 변수에 담긴다.
        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                // 기존 데이터인지 찾는 조건
                ['slug' => $category['slug']],

                // 생성하거나 갱신할 실제 컬럼 값이다.
                [
                    'parent_id' => null,
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
