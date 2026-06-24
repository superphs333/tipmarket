<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 역할 테이블과 사용자-역할 연결 테이블을 생성
 *
 * - 모든 가입자는 기본적으로 일반 사용자로 봄, 따라서 일반 사용자 역할은 별도 행으로 저장하지 않고, 관리자처럼 추가 권한이 필요한 경우에만 role_user에 연결함.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();

            // 코드에서 참조하는 역할 키 ex) admin
            $table->string('name', 50)->unique();

            // 관리자 화면이나 내부 도구에서 보여줄 역할 표시명
            $table->string('label', 100);

            // 역할의 목적과 권한 범위를 설명
            $table->string('description')->nullable();

            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            // 역할을 부여받은 사용자
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // 사용자에게 부여된 추가 역할이다.
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            // 역할 부여 시점과 수정 시점 남긴다.
            $table->timestamps();
            // 같은 사용자에게 같은 역할이 중복 부여되지 않도록 막음
            $table->primary(['user_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
