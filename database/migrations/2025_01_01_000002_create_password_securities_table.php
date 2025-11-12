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
        $tableName = config('password-security.tables.password_securities', 'password_securities');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->morphs('securable');                    // securable_type, securable_id
            $table->timestamp('password_changed_at')->nullable();
            $table->timestamp('password_expires_at')->nullable();
            $table->boolean('password_must_change')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('deactivated_at')->nullable();
            $table->string('deactivation_reason')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            // 인덱스
            $table->index('password_expires_at');
            $table->index('is_active');
            $table->index('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('password-security.tables.password_securities', 'password_securities');
        Schema::dropIfExists($tableName);
    }
};

