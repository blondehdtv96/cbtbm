<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siswas', function (Blueprint $table) {
            $table->id();
            $table->string('nis')->unique();
            $table->string('nama');
            $table->foreignId('kelas_id')->constrained('kelas')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('foto')->nullable();
            $table->string('telepon')->nullable();
            $table->timestamps();

            $table->index(['kelas_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswas');
    }
};
