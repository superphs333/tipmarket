<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 팁에 단일 카테고리 연결 컬럼 추가.
     *
     * draft 상태에서는 카테고리 없이 저장될 수 있으므로 nullable로 둔다.
     * 발행 시점에는 FormRequest 또는 Action에서 category_id를 필수 검증한다.
     */
    public function up(): void
    {
        Schema::table('tips', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->comment('팁이 속한 카테고리 ID')
                ->constrained()
                ->restrictOnDelete();

            // 카테고리별 공개 팁 목록 조회 최적화.
            $table->index(['category_id', 'status']);
        });
    }

    /**
     * 팁-카테고리 연결 컬럼 제거.
     */
    public function down(): void
    {
        Schema::table('tips', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'status']);
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
