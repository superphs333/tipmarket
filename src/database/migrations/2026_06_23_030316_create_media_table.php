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
        Schema::create('media', function (Blueprint $table) {
            $table->id()->comment('미디어 고유 식별자');

            $table->string('disk')->default('r2')->comment('파일을 저장한 Laravel filesystem disk 이름');
            $table->string('path')->unique()->comment('disk 내부 파일 저장 경로');
            $table->string('collection')->comment('파일 사용 용도 구분값');

            $table->string('original_name')->nullable()->comment('사용자가 업로드한 원본 파일명');
            $table->string('mime_type')->comment('파일 MIME 타입');
            $table->unsignedBigInteger('size')->comment('파일 크기(byte)');
            $table->unsignedInteger('width')->nullable()->comment('이미지 너비(px)');
            $table->unsignedInteger('height')->nullable()->comment('이미지 높이(px)');

            $table->string('owner_type')->nullable()->comment('파일이 연결된 모델 클래스 또는 morph alias');
            $table->unsignedBigInteger('owner_id')->nullable()->comment('파일이 연결된 모델의 기본키');
            $table->foreignId('uploaded_by_id')
                ->nullable()
                ->comment('파일을 업로드한 사용자 ID')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status')->default('temporary')->comment('파일 연결 상태');
            $table->string('visibility')->default('public')->comment('파일 공개 범위');
            $table->json('metadata')->nullable()->comment('추가 메타데이터 JSON');

            $table->timestamp('created_at')->nullable()->comment('레코드 생성 시각');
            $table->timestamp('updated_at')->nullable()->comment('레코드 수정 시각');
            $table->softDeletes()->comment('소프트 삭제 시각');

            // 특정 모델(User, Tip 등)에 연결된 파일을 용도별로 빠르게 조회하기 위한 인덱스
            $table->index(['owner_type', 'owner_id', 'collection']);
            // 사용자별 업로드 파일 목록과 용도별 관리 화면 조회를 위한 인덱스
            $table->index(['uploaded_by_id', 'collection']);
            // 임시 파일 정리, 삭제 대기 파일 처리처럼 상태와 생성 시각 기준의 배치 작업을 위한 인덱스
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
