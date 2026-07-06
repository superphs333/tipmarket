<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 댓글 허용 옵션 기능을 제거하면서 tips 테이블의 관련 컬럼도 함께 제거한다.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('tips', 'allow_comments')) {
            return;
        }

        Schema::table('tips', function (Blueprint $table) {
            $table->dropColumn('allow_comments');
        });
    }

    /**
     * 롤백 시 기존 기본 동작과 맞추기 위해 댓글 허용 상태를 true로 복구한다.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tips', 'allow_comments')) {
            return;
        }

        Schema::table('tips', function (Blueprint $table) {
            $table->boolean('allow_comments')
                ->default(true)
                ->after('status')
                ->comment('댓글 허용 여부');
        });
    }
};
