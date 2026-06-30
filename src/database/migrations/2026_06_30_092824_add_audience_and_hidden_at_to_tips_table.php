<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tips', function (Blueprint $table) {
            $table->string('audience', 30)
                ->default('private')
                ->after('status')
                ->comment('팁 접근 대상: public, premium, private');
            $table->timestamp('hidden_at')
                ->nullable()
                ->after('published_at')
                ->comment('운영자 숨김 처리 시각');

            $table->index(['status', 'audience', 'published_at']);
        });

        DB::table('tips')
            ->where('status', 'published')
            ->update(['audience' => 'public']);

        DB::table('tips')
            ->where('status', 'hidden')
            ->update([
                'status' => 'published',
                'audience' => 'private',
                'hidden_at' => now(),
            ]);

        DB::table('tips')
            ->where('status', 'archived')
            ->update([
                'status' => 'published',
                'audience' => 'private',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tips')
            ->whereNotNull('hidden_at')
            ->update(['status' => 'hidden']);

        Schema::table('tips', function (Blueprint $table) {
            $table->dropIndex(['status', 'audience', 'published_at']);
            $table->dropColumn(['audience', 'hidden_at']);
        });
    }
};
