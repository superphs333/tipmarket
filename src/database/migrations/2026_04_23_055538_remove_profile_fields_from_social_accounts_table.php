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
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropColumn(['email', 'nickname', 'raw_profile']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->string('email')->nullable()->after('provider_user_id');
            $table->string('nickname')->nullable()->after('email');
            $table->json('raw_profile')->nullable()->after('nickname');
        });
    }
};
