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
        Schema::table('bank_soals', function (Blueprint $table) {
            if (Schema::hasColumn('bank_soals', 'tingkat_kesulitan')) {
                $table->dropColumn('tingkat_kesulitan');
            }
            if (Schema::hasColumn('bank_soals', 'pembahasan')) {
                $table->dropColumn('pembahasan');
            }
            if (Schema::hasColumn('bank_soals', 'kategori')) {
                $table->dropColumn('kategori');
            }
            if (Schema::hasColumn('bank_soals', 'tag')) {
                $table->dropColumn('tag');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_soals', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_soals', 'tingkat_kesulitan')) {
                $table->enum('tingkat_kesulitan', ['mudah', 'sedang', 'sulit'])->nullable();
            }
            if (!Schema::hasColumn('bank_soals', 'pembahasan')) {
                $table->text('pembahasan')->nullable();
            }
            if (!Schema::hasColumn('bank_soals', 'kategori')) {
                $table->string('kategori')->nullable();
            }
            if (!Schema::hasColumn('bank_soals', 'tag')) {
                $table->string('tag')->nullable();
            }
        });
    }
};
