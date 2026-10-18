<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Schema;

/**
 * Sumber tunggal definisi pengaturan "Peraturan Siswa".
 *
 * Keempat barisnya dibuat oleh migrasi 2026_09_13_000000, tetapi halaman
 * Pengaturan Sistem tidak boleh bergantung pada migrasi itu sudah jalan:
 * halaman tersebut merender field dari baris system_settings yang ada, jadi
 * di server yang file migrasinya tertinggal grupnya kosong — kolomnya "tidak
 * ada dan tidak bisa dibuat", dan admin tidak punya cara memperbaikinya dari
 * UI. Kelas ini membuat barisnya bisa dipastikan ada kapan saja (idempoten),
 * dipakai bersama oleh SystemSettingController dan `app:repair-schema`.
 *
 * @see \App\Http\Controllers\SystemSettingController::index()
 * @see \App\Console\Commands\RepairSchema
 */
class StudentRulesSettings
{
    /** Kunci pengaturan peraturan siswa, berurutan seperti tampil di form. */
    public const KEYS = [
        'student_rules_enabled',
        'student_rules_title',
        'student_rules_content',
        'student_rules_version',
    ];

    /** Kolom users yang menyimpan persetujuan siswa (migrasi 2026_09_13_000000). */
    public const USER_COLUMNS = [
        'student_rules_ack_version',
        'student_rules_acknowledged_at',
    ];

    /**
     * Definisi baris default.
     *
     * @return array<int, array<string, string|int>>
     */
    public function definitions(?int $version = null): array
    {
        return [
            [
                'key' => 'student_rules_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'student',
                'label' => 'Aktifkan Peraturan Siswa',
                'description' => 'Wajibkan siswa membaca dan menyetujui peraturan setelah login.',
                'order' => 1,
            ],
            [
                'key' => 'student_rules_title',
                'value' => 'Peraturan Penggunaan CBT untuk Siswa',
                'type' => 'text',
                'group' => 'student',
                'label' => 'Judul Popup Peraturan',
                'description' => 'Judul yang ditampilkan pada popup peraturan siswa.',
                'order' => 2,
            ],
            [
                'key' => 'student_rules_content',
                'value' => $this->defaultContent(),
                'type' => 'textarea',
                'group' => 'student',
                'label' => 'Isi Peraturan Siswa',
                'description' => 'Isi peraturan wajib yang dibaca siswa. Konten ditampilkan sebagai teks aman.',
                'order' => 3,
            ],
            [
                'key' => 'student_rules_version',
                'value' => (string) max(1, (int) ($version ?? 1)),
                'type' => 'number',
                'group' => 'student',
                'label' => 'Versi Peraturan',
                'description' => 'Naikkan versi agar seluruh siswa diminta menyetujui kembali.',
                'order' => 4,
            ],
        ];
    }

    /**
     * Buat baris yang belum ada; baris yang sudah ada tidak disentuh.
     *
     * Nilai yang sudah disunting admin sengaja dipertahankan — perintah ini
     * dipanggil setiap kali halaman pengaturan dibuka, jadi menimpa nilai akan
     * mengembalikan peraturan ke default setiap kali halaman dimuat. Untuk
     * mengembalikan ke default, gunakan tombol Reset Pengaturan.
     *
     * @return array<int, string> Kunci yang baru dibuat.
     */
    public function ensureExists(): array
    {
        $existing = SystemSetting::whereIn('key', self::KEYS)->pluck('key')->all();
        $created = [];

        foreach ($this->definitions() as $definition) {
            if (in_array($definition['key'], $existing, true)) {
                continue;
            }

            SystemSetting::create($definition);
            $created[] = (string) $definition['key'];
        }

        if ($created !== []) {
            SystemSetting::clearCache();
        }

        return $created;
    }

    /**
     * Timpa keempat baris dengan nilai default pada versi tertentu.
     *
     * Dipakai tombol Reset Pengaturan, yang memang bermaksud mengembalikan
     * teks peraturan ke bawaan aplikasi dan menaikkan versinya agar siswa
     * menyetujui ulang.
     */
    public function restoreDefaults(int $version): void
    {
        foreach ($this->definitions($version) as $definition) {
            SystemSetting::updateOrCreate(['key' => $definition['key']], $definition);
        }

        SystemSetting::clearCache();
    }

    /**
     * Apakah persetujuan siswa bisa disimpan di database ini.
     *
     * Tanpa kolomnya, popup peraturan muncul lagi setiap login karena
     * persetujuan tidak punya tempat disimpan.
     */
    public function canPersistAcknowledgement(): bool
    {
        foreach (self::USER_COLUMNS as $column) {
            if (!Schema::hasColumn('users', $column)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Isi peraturan bawaan, diambil dari PERATURAN_PENGGUNAAN_SISWA.md.
     *
     * Penanda markdown dibuang karena popup menampilkan kontennya sebagai teks
     * aman (bukan HTML), jadi tanda '#' dan '**' akan terlihat apa adanya.
     */
    public function defaultContent(): string
    {
        $path = base_path('PERATURAN_PENGGUNAAN_SISWA.md');

        if (!is_file($path)) {
            return 'Siswa wajib mengerjakan ujian secara jujur, menjaga kerahasiaan akun dan token, '
                . 'tidak membuka tab atau aplikasi lain, serta mengikuti arahan pengawas.';
        }

        $content = (string) file_get_contents($path);
        $content = preg_replace('/^#{1,6}\s+/m', '', $content);

        return trim(str_replace(['**', '---'], '', $content));
    }
}
