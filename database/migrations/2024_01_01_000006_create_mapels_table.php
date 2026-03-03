<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mapels', function (Blueprint $table) {
            $table->id();
            $table->string('nama_mapel');
            $table->string('kode_mapel')->unique();
            $table->foreignId('jurusan_id')->nullable()->constrained('jurusans')->onDelete('set null');
            $table->boolean('is_umum')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('jurusan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapels');
    }
};
