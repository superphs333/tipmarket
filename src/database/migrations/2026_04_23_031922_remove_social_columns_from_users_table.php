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
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['social_id']);
            $table->dropColumn(['social_id', 'provider', 'social_meta']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('social_id')->nullable()->unique()->after('id')->comment('Social 계정 ID');
            $table->string('provider', 20)->default('email')->after('password')->comment('가입 방식 (email, google, kakao 등)');
            $table->text('social_meta')->nullable()->after('provider')->comment('소셜 로그인 메타');
        });
    }

};