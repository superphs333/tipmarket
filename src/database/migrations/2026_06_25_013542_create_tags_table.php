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
        Schema::create('tags', function (Blueprint $table) {
            $table->id();

            $table->string('name', 50)->comment('태그 표시명');
            $table->string('slug', 80)->unique()->comment('URL/검색용 태그 슬러그');
            $table->string('description', 255)->nullable()->comment('태그 설명');

            $table->unsignedInteger('usage_count')->default(0)->comment('태그 사용 횟수 캐시');
            $table->boolean('is_active')->default(true)->comment('태그 사용 여부');

            $table->timestamps();

            $table->index(['is_active', 'usage_count']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
