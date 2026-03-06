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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('text'); // text, textarea, image, boolean, number, color
            $table->string('group')->default('general'); // general, appearance, email, exam, etc
            $table->string('label');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Insert default settings
        DB::table('system_settings')->insert([
            // General Settings
            [
                'key' => 'app_name',
                'value' => 'CBT SMK',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Nama Aplikasi',
                'description' => 'Nama aplikasi yang ditampilkan di seluruh sistem',
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'school_name',
                'value' => 'SMK Negeri 1',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Nama Sekolah',
                'description' => 'Nama sekolah lengkap',
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'school_address',
                'value' => 'Jl. Pendidikan No. 1',
                'type' => 'textarea',
                'group' => 'general',
                'label' => 'Alamat Sekolah',
                'description' => 'Alamat lengkap sekolah',
                'order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'school_phone',
                'value' => '021-12345678',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Telepon Sekolah',
                'description' => 'Nomor telepon sekolah',
                'order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'school_email',
                'value' => 'info@smkn1.sch.id',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Email Sekolah',
                'description' => 'Email resmi sekolah',
                'order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'school_website',
                'value' => 'https://smkn1.sch.id',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Website Sekolah',
                'description' => 'URL website sekolah',
                'order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Appearance Settings
            [
                'key' => 'logo',
                'value' => null,
                'type' => 'image',
                'group' => 'appearance',
                'label' => 'Logo Sekolah',
                'description' => 'Logo yang ditampilkan di header (max 2MB, format: jpg, png)',
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'logo_small',
                'value' => null,
                'type' => 'image',
                'group' => 'appearance',
                'label' => 'Logo Kecil',
                'description' => 'Logo kecil untuk favicon dan mobile',
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'primary_color',
                'value' => '#4f46e5',
                'type' => 'color',
                'group' => 'appearance',
                'label' => 'Warna Utama',
                'description' => 'Warna utama aplikasi',
                'order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'secondary_color',
                'value' => '#7c3aed',
                'type' => 'color',
                'group' => 'appearance',
                'label' => 'Warna Sekunder',
                'description' => 'Warna sekunder aplikasi',
                'order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'login_background',
                'value' => null,
                'type' => 'image',
                'group' => 'appearance',
                'label' => 'Background Login',
                'description' => 'Gambar background halaman login',
                'order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Exam Settings
            [
                'key' => 'default_exam_duration',
                'value' => '90',
                'type' => 'number',
                'group' => 'exam',
                'label' => 'Durasi Ujian Default (menit)',
                'description' => 'Durasi default untuk ujian baru',
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'auto_submit_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'exam',
                'label' => 'Auto Submit',
                'description' => 'Otomatis submit ujian saat waktu habis',
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'show_result_immediately',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'exam',
                'label' => 'Tampilkan Hasil Langsung',
                'description' => 'Tampilkan hasil ujian setelah submit',
                'order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'anti_cheat_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'exam',
                'label' => 'Anti-Cheat',
                'description' => 'Aktifkan sistem anti-cheat',
                'order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'max_tab_switch',
                'value' => '2',
                'type' => 'number',
                'group' => 'exam',
                'label' => 'Maksimal Pindah Tab',
                'description' => 'Jumlah maksimal pindah tab sebelum auto-submit',
                'order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Email Settings
            [
                'key' => 'email_from_name',
                'value' => 'CBT SMK',
                'type' => 'text',
                'group' => 'email',
                'label' => 'Nama Pengirim Email',
                'description' => 'Nama yang muncul sebagai pengirim email',
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'email_from_address',
                'value' => 'noreply@cbt.sch.id',
                'type' => 'text',
                'group' => 'email',
                'label' => 'Email Pengirim',
                'description' => 'Alamat email pengirim',
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Footer Settings
            [
                'key' => 'footer_text',
                'value' => '© 2024 CBT SMK. All rights reserved.',
                'type' => 'text',
                'group' => 'appearance',
                'label' => 'Teks Footer',
                'description' => 'Teks yang ditampilkan di footer',
                'order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'show_powered_by',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'appearance',
                'label' => 'Tampilkan "Powered by"',
                'description' => 'Tampilkan credit di footer',
                'order' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
