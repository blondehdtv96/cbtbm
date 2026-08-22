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
        Schema::table('jawaban_siswas', function (Blueprint $table) {
            $table->string('jawaban_file')->nullable()->after('jawaban_dipilih');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jawaban_siswas', function (Blueprint $table) {
            $table->dropColumn('jawaban_file');
        });
    }
};
