<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['superadmin', 'admin', 'guru', 'siswa'])->default('siswa')->after('password');
            $table->timestamp('last_login')->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('last_login');
            $table->integer('login_attempts')->default(0)->after('is_active');
            $table->timestamp('locked_until')->nullable()->after('login_attempts');
            $table->string('avatar')->nullable()->after('locked_until');

            $table->index('role');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'last_login', 'is_active', 'login_attempts', 'locked_until', 'avatar']);
        });
    }
};
