<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * tips.content를 에디터 HTML 원문 저장용 긴 문자열 컬럼으로 변경한다.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE tips MODIFY content LONGTEXT NOT NULL COMMENT '에디터 HTML 본문'");
    }

    /**
     * 변경 전 JSON 컬럼 형태로 되돌린다.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE tips MODIFY content JSON NOT NULL COMMENT '에디터 원본 본문 JSON'");
    }
};
