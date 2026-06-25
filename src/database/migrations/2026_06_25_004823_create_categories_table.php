<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            // 상위 카테고리. 초기에는 단일 depth로 써도 되고, 나중에 하위 카테고리 확장 가능.
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->string('name', 80)->comment('카테고리 표시명');
            $table->string('slug', 100)->unique()->comment('URL/식별용 슬러그');
            $table->string('description', 255)->nullable()->comment('카테고리 설명');

            $table->unsignedSmallInteger('sort_order')->default(0)->comment('노출 정렬 순서');
            $table->boolean('is_active')->default(true)->comment('카테고리 사용 여부');

            $table->timestamps();

            $table->index(['parent_id', 'sort_order']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * 카테고리 테이블 제거.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};