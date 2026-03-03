<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ujian_bank_soals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujian_id')->constrained('ujians')->onDelete('cascade');
            $table->foreignId('bank_soal_id')->constrained('bank_soals')->onDelete('cascade');
            $table->integer('nomor_urut')->default(0);
            $table->timestamps();

            $table->unique(['ujian_id', 'bank_soal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ujian_bank_soals');
    }
};
