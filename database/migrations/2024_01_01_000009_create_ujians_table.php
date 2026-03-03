<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ujians', function (Blueprint $table) {
            $table->id();
            $table->string('nama_ujian');
            $table->enum('jenis_ujian', ['harian', 'uts', 'uas', 'praktik', 'tryout', 'anbk', 'ukk']);
            $table->foreignId('mapel_id')->constrained('mapels')->onDelete('cascade');
            $table->foreignId('guru_id')->constrained('gurus')->onDelete('cascade');
            $table->integer('durasi_menit');
            $table->dateTime('tanggal_mulai');
            $table->dateTime('tanggal_selesai');
            $table->enum('metode_soal', ['random', 'manual'])->default('random');
            $table->boolean('acak_opsi')->default(true);
            $table->integer('jumlah_soal');
            $table->enum('status', ['draft', 'publish', 'berlangsung', 'selesai'])->default('draft');
            $table->string('token')->unique()->nullable();
            $table->boolean('tampilkan_nilai')->default(true);
            $table->boolean('tampilkan_pembahasan')->default(false);
            $table->text('instruksi')->nullable();
            $table->timestamps();

            $table->index(['mapel_id', 'status']);
            $table->index('tanggal_mulai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ujians');
    }
};
