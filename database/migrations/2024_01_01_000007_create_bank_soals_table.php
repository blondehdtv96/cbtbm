<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_soals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mapel_id')->constrained('mapels')->onDelete('cascade');
            $table->foreignId('guru_id')->constrained('gurus')->onDelete('cascade');
            $table->enum('tipe_soal', ['pg', 'essay', 'pg_kompleks', 'menjodohkan']);
            $table->enum('tingkat_kesulitan', ['mudah', 'sedang', 'sulit']);
            $table->integer('bobot_nilai')->default(1);
            $table->text('pertanyaan');
            $table->string('gambar_soal')->nullable();
            $table->text('pembahasan')->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->string('kategori')->nullable();
            $table->string('tag')->nullable();
            $table->integer('digunakan_count')->default(0);
            $table->timestamps();

            $table->index(['mapel_id', 'guru_id', 'tipe_soal']);
            $table->index('tingkat_kesulitan');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_soals');
    }
};
