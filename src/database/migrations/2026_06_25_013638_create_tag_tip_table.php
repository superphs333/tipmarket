<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tag_tip', function (Blueprint $table) {
            $table->id();

            // 태그가 삭제되면 연결 정보도 같이 삭제.
            $table->foreignId('tag_id')
                ->constrained()
                ->cascadeOnDelete();

            // 팁이 삭제되면 연결 정보도 같이 삭제.
            $table->foreignId('tip_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            // 같은 팁에 같은 태그가 중복 연결되지 않게 막는다.
            $table->unique(['tag_id', 'tip_id']);

            // 특정 팁의 태그 목록 조회 최적화.
            $table->index(['tip_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tag_tip');
    }
};
