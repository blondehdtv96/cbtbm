<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PasswordVerifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Memperbaiki baris users yang kolom password-nya bukan hash bcrypt.
 *
 * Akun seperti ini membuat login membalas 500 sebelum perbaikan di
 * PasswordVerifier, dan sesudahnya tetap tidak bisa dipakai masuk selama
 * password aslinya tidak diketahui. Jalankan sekali di server tempat aplikasi
 * berjalan untuk membersihkan data lama:
 *
 *     php artisan users:repair-password-hashes --dry-run   # lihat dulu
 *     php artisan users:repair-password-hashes             # perbaiki
 *
 * Akun yang masih punya plain_password dipulihkan apa adanya (password siswa
 * tidak berubah). Sisanya diberi password acak baru dan harus dibagikan ulang
 * lewat Cetak Kartu Peserta / halaman kredensial siswa.
 */
class RepairPasswordHashes extends Command
{
    protected $signature = 'users:repair-password-hashes
                            {--dry-run : Hanya laporkan, jangan ubah data}';

    protected $description = 'Perbaiki akun yang kolom password-nya bukan hash bcrypt (penyebab 500 saat login)';

    public function handle(PasswordVerifier $verifier): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $rows = [];
        $repaired = 0;
        $reset = 0;
        $scanned = 0;

        // chunkById supaya aman di tabel users yang besar.
        User::select(['id', 'name', 'email', 'role', 'password', 'plain_password'])
            ->orderBy('id')
            ->chunkById(500, function ($users) use ($verifier, $dryRun, &$rows, &$repaired, &$reset, &$scanned) {
                foreach ($users as $user) {
                    $scanned++;

                    if ($verifier->isUsable($user->getAuthPassword())) {
                        continue;
                    }

                    // Dicatat sebelum disimpan: setelah save(), nilai "asli"
                    // pada model sudah tersinkron ke hash yang baru.
                    $before = $verifier->describe($user->getAuthPassword());

                    $plain = (string) $user->plain_password;
                    $fromPlain = $plain !== '';

                    if (!$fromPlain) {
                        // Password lama tidak diketahui — satu-satunya jalan
                        // adalah memberi password baru lalu dibagikan ulang.
                        $plain = strtoupper(Str::random(8));
                    }

                    if (!$dryRun) {
                        $user->forceFill([
                            'password' => Hash::make($plain),
                            'plain_password' => $plain,
                        ])->save();
                    }

                    $fromPlain ? $repaired++ : $reset++;

                    $rows[] = [
                        $user->id,
                        $user->email,
                        $user->role,
                        $before,
                        $fromPlain ? 'dipulihkan dari plain_password' : 'PASSWORD BARU — bagikan ulang',
                    ];
                }
            });

        $this->newLine();
        $this->line("Akun diperiksa: {$scanned}");

        if ($rows === []) {
            $this->info('Semua akun sudah memakai hash bcrypt. Tidak ada yang perlu diperbaiki.');

            return self::SUCCESS;
        }

        $this->table(['ID', 'Email', 'Role', 'Isi password lama', 'Tindakan'], $rows);

        if ($dryRun) {
            $this->warn('Mode --dry-run: tidak ada data yang diubah.');
            $this->line('Jalankan tanpa --dry-run untuk menerapkan perbaikan.');

            return self::SUCCESS;
        }

        $this->info("Dipulihkan tanpa mengubah password siswa: {$repaired}");

        if ($reset > 0) {
            $this->warn("Diberi password baru: {$reset}");
            $this->line('Cetak ulang Kartu Peserta / halaman kredensial siswa untuk akun bertanda "PASSWORD BARU".');
        }

        return self::SUCCESS;
    }
}
