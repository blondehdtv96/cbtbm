<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kelas');
            $table->foreignId('jurusan_id')->constrained('jurusans')->onDelete('cascade');
            $table->enum('tingkat', ['10', '11', '12']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['jurusan_id', 'tingkat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
