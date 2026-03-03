<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::table('ujians', function (Blueprint $table) {
            $table->foreignId('sesi_ujian_id')->nullable()->after('guru_id')->constrained('sesi_ujians')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ujians', function (Blueprint $table) {
            $table->dropForeign(['sesi_ujian_id']);
            $table->dropColumn('sesi_ujian_id');
        });
    }
};
