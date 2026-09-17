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
        Schema::table('peserta_ujians', function (Blueprint $table) {
            $table->boolean('violation_flag')->default(false)->after('soal_order');
            $table->string('violation_type')->nullable()->after('violation_flag');
            $table->text('violation_detail')->nullable()->after('violation_type');
            $table->timestamp('violated_at')->nullable()->after('violation_detail');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peserta_ujians', function (Blueprint $table) {
            $table->dropColumn(['violation_flag', 'violation_type', 'violation_detail', 'violated_at']);
        });
    }
};
