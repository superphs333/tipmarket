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
        if (! Schema::hasColumn('tips', 'hidden_at')) {
            return;
        }

        Schema::table('tips', function (Blueprint $table) {
            $table->dropColumn('hidden_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tips', 'hidden_at')) {
            return;
        }

        Schema::table('tips', function (Blueprint $table) {
            $table->timestamp('hidden_at')
                ->nullable()
                ->after('audience')
                ->comment('운영자 숨김 처리 시각');
        });
    }
};
