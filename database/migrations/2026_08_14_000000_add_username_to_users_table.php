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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('email');
        });

        // Backfill: siswa accounts login with NISN today, so default their
        // username to NISN to keep existing credentials working unchanged.
        DB::table('users')
            ->join('siswas', 'siswas.user_id', '=', 'users.id')
            ->whereNull('users.username')
            ->update(['users.username' => DB::raw('siswas.nisn')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
