<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE tips MODIFY status ENUM('draft', 'published') NOT NULL DEFAULT 'draft' COMMENT '팁 상태: draft, published'");
        DB::statement("ALTER TABLE tips MODIFY audience ENUM('public', 'premium', 'private') NOT NULL DEFAULT 'private' COMMENT '팁 접근 대상: public, premium, private'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE tips MODIFY status VARCHAR(30) NOT NULL DEFAULT 'draft' COMMENT '팁 상태: draft, published'");
        DB::statement("ALTER TABLE tips MODIFY audience VARCHAR(30) NOT NULL DEFAULT 'private' COMMENT '팁 접근 대상: public, premium, private'");
    }
};
