<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jawaban_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_ujian_id')->constrained('peserta_ujians')->onDelete('cascade');
            $table->foreignId('bank_soal_id')->constrained('bank_soals')->onDelete('cascade');
            $table->text('jawaban_dipilih')->nullable();
            $table->boolean('is_ragu')->default(false);
            $table->decimal('nilai', 5, 2)->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestamps();

            $table->unique(['peserta_ujian_id', 'bank_soal_id']);
            $table->index('peserta_ujian_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jawaban_siswas');
    }
};
