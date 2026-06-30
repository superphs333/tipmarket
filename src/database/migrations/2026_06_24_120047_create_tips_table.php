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
        Schema::create('tips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->comment('팁 작성자 ID')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title', 160)->comment('팁 제목');
            $table->longText('content')->comment('에디터 HTML 본문');
            $table->string('status', 30)->default('draft')->comment('팁 상태: draft, published');
            $table->timestamp('published_at')->nullable()->comment('팁 공개 발행 시각');
            $table->boolean('allow_comments')->default(true)->comment('댓글 허용 여부');

            $table->unsignedInteger('view_count')->default(0)->comment('조회 수 캐시');
            $table->unsignedInteger('like_count')->default(0)->comment('좋아요 수 캐시');
            $table->unsignedInteger('bookmark_count')->default(0)->comment('북마크 수 캐시');
            $table->unsignedInteger('comment_count')->default(0)->comment('댓글 수 캐시');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tips');
    }
};
