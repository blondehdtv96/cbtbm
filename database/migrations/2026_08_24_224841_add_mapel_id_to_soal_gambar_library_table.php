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
        Schema::table('soal_gambar_library', function (Blueprint $table) {
            $table->dropUnique(['original_filename']);
            $table->foreignId('mapel_id')->nullable()->after('id')->constrained('mapels')->onDelete('cascade');
            $table->unique(['mapel_id', 'original_filename']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('soal_gambar_library', function (Blueprint $table) {
            $table->dropUnique(['mapel_id', 'original_filename']);
            $table->dropForeign(['mapel_id']);
            $table->dropColumn('mapel_id');
            $table->unique('original_filename');
        });
    }
};
