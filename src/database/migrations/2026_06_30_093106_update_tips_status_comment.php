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

        DB::statement("ALTER TABLE tips MODIFY status VARCHAR(30) NOT NULL DEFAULT 'draft' COMMENT '팁 상태: draft, published'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE tips MODIFY status VARCHAR(30) NOT NULL DEFAULT 'draft' COMMENT '팁 상태: draft, published, hidden, archived'");
    }
};
