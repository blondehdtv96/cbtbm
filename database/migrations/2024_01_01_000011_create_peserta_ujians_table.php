<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peserta_ujians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujian_id')->constrained('ujians')->onDelete('cascade');
            $table->foreignId('siswa_id')->constrained('siswas')->onDelete('cascade');
            $table->dateTime('waktu_mulai')->nullable();
            $table->dateTime('waktu_selesai')->nullable();
            $table->decimal('nilai', 5, 2)->nullable();
            $table->enum('status', ['belum', 'sedang', 'selesai'])->default('belum');
            $table->text('soal_order')->nullable();
            $table->timestamps();

            $table->unique(['ujian_id', 'siswa_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peserta_ujians');
    }
};
