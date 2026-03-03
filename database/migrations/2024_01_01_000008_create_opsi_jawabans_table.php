<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opsi_jawabans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_soal_id')->constrained('bank_soals')->onDelete('cascade');
            $table->string('opsi_label', 5);
            $table->text('isi_opsi');
            $table->string('gambar_opsi')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->index('bank_soal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opsi_jawabans');
    }
};
