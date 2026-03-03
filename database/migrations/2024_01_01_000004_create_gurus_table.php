<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gurus', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->unique()->nullable();
            $table->string('nama');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('telepon')->nullable();
            $table->string('foto')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gurus');
    }
};
