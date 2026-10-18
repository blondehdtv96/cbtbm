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
        // Lihat 2026_09_13_000000: kolomnya bisa sudah ada karena
        // `php artisan app:repair-schema` dijalankan lebih dulu di server yang
        // file migrasinya tertinggal, jadi tiap kolom dicek sendiri.
        Schema::table('peserta_ujians', function (Blueprint $table) {
            if (!Schema::hasColumn('peserta_ujians', 'violation_flag')) {
                $table->boolean('violation_flag')->default(false)->after('soal_order');
            }

            if (!Schema::hasColumn('peserta_ujians', 'violation_type')) {
                $table->string('violation_type')->nullable()->after('violation_flag');
            }

            if (!Schema::hasColumn('peserta_ujians', 'violation_detail')) {
                $table->text('violation_detail')->nullable()->after('violation_type');
            }

            if (!Schema::hasColumn('peserta_ujians', 'violated_at')) {
                $table->timestamp('violated_at')->nullable()->after('violation_detail');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peserta_ujians', function (Blueprint $table) {
            $table->dropColumn(array_values(array_filter(
                ['violation_flag', 'violation_type', 'violation_detail', 'violated_at'],
                static fn (string $column): bool => Schema::hasColumn('peserta_ujians', $column)
            )));
        });
    }
};
