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
        Schema::create('soal_gambar_library', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename')->unique();
            $table->string('stored_path');
            $table->unsignedInteger('size');
            $table->string('mime_type', 100);
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soal_gambar_library');
    }
};
