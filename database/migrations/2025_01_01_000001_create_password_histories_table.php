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
        $tableName = config('password-security.tables.password_histories', 'password_histories');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->morphs('securable');                    // securable_type, securable_id
            $table->string('password_hash');                // 해시된 패스워드
            $table->timestamp('changed_at');                // 변경 일시
            $table->unsignedBigInteger('changed_by')->nullable(); // 변경한 사용자 (관리자가 변경한 경우)
            $table->string('ip_address', 45)->nullable();   // 변경 시 IP 주소
            $table->text('user_agent')->nullable();         // 변경 시 User Agent
            $table->timestamps();

            // 인덱스
            $table->index('changed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('password-security.tables.password_histories', 'password_histories');
        Schema::dropIfExists($tableName);
    }
};

