<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('student_rules_ack_version')->nullable()->after('last_seen_at');
            $table->timestamp('student_rules_acknowledged_at')->nullable()->after('student_rules_ack_version');
        });

        $now = now();
        $settings = [
            [
                'key' => 'student_rules_enabled', 'value' => '1', 'type' => 'boolean',
                'group' => 'student', 'label' => 'Aktifkan Peraturan Siswa',
                'description' => 'Wajibkan siswa membaca dan menyetujui peraturan setelah login.', 'order' => 1,
            ],
            [
                'key' => 'student_rules_title', 'value' => 'Peraturan Penggunaan CBT untuk Siswa', 'type' => 'text',
                'group' => 'student', 'label' => 'Judul Popup Peraturan',
                'description' => 'Judul yang ditampilkan pada popup peraturan siswa.', 'order' => 2,
            ],
            [
                'key' => 'student_rules_content', 'value' => $this->defaultRulesContent(), 'type' => 'textarea',
                'group' => 'student', 'label' => 'Isi Peraturan Siswa',
                'description' => 'Isi peraturan wajib yang dibaca siswa. Konten ditampilkan sebagai teks aman.', 'order' => 3,
            ],
            [
                'key' => 'student_rules_version', 'value' => '1', 'type' => 'number',
                'group' => 'student', 'label' => 'Versi Peraturan',
                'description' => 'Naikkan versi agar seluruh siswa diminta menyetujui kembali.', 'order' => 4,
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->insertOrIgnore($setting + ['created_at' => $now, 'updated_at' => $now]);
            \Illuminate\Support\Facades\Cache::forget("setting:{$setting['key']}");
        }

        \Illuminate\Support\Facades\Cache::forget('settings:all');
        \Illuminate\Support\Facades\Cache::forget('settings:group:student');
    }
    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'student_rules_enabled',
            'student_rules_title',
            'student_rules_content',
            'student_rules_version',
        ])->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['student_rules_ack_version', 'student_rules_acknowledged_at']);
        });
    }

    private function defaultRulesContent(): string
    {
        $path = base_path('PERATURAN_PENGGUNAAN_SISWA.md');
        if (is_file($path)) {
            $content = (string) file_get_contents($path);
            $content = preg_replace('/^#{1,6}\s+/m', '', $content);
            $content = str_replace(['**', '---'], '', $content);

            return trim($content);
        }

        return "PERATURAN PENGGUNAAN CBT UNTUK SISWA\n\n"
            . "1. Gunakan akun sendiri dan rahasiakan password serta token ujian.\n"
            . "2. Kerjakan ujian secara mandiri, jujur, tertib, dan bertanggung jawab.\n"
            . "3. Dilarang membuka tab, browser, aplikasi lain, Developer Tools, atau sumber yang tidak diizinkan.\n"
            . "4. Dilarang menyalin, memotret, merekam, atau menyebarkan soal dan jawaban.\n"
            . "5. Aktivitas ujian dapat dicatat oleh sistem anti-kecurangan.\n"
            . "6. Pelanggaran dapat menyebabkan ujian dikumpulkan otomatis dan ditindak oleh sekolah.\n"
            . "7. Laporkan setiap gangguan teknis kepada pengawas tanpa menutup halaman ujian.\n"
            . "8. Periksa jawaban sebelum menekan tombol Kumpulkan Jawaban.";
    }
};