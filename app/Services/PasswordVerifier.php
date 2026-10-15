<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Memeriksa password login tanpa pernah melempar exception.
 *
 * config/hashing.php memasang 'verify' => true, sehingga Hash::check()
 * MELEMPAR RuntimeException alih-alih mengembalikan false begitu kolom
 * users.password tidak berisi hash bcrypt yang sah — termasuk ketika kolomnya
 * kosong, berisi teks biasa, atau hash lama (md5/sha1/argon2). Satu baris data
 * seperti itu cukup untuk membuat login membalas "500 Server Error", karena
 * exception-nya terjadi sebelum controller sempat membalas pesan kesalahan.
 *
 * Semua akun yang dibuat aplikasi ini sekarang selalu di-bcrypt, tetapi data
 * lama (impor versi awal, suntingan manual lewat phpMyAdmin, migrasi dari
 * sistem lain) bisa menyimpan nilai yang tidak terpakai. Kelas ini membuat
 * kondisi itu bisa ditangani: akun dipulihkan jika password yang dikirim
 * memang cocok dengan kredensial yang tersimpan apa adanya, dan sisanya
 * ditolak dengan pesan yang bisa dibaca user sambil dicatat ke log.
 *
 * @see \App\Console\Commands\RepairPasswordHashes untuk perbaikan massal.
 */
class PasswordVerifier
{
    /** Password cocok — user boleh masuk. */
    public const VALID = 'valid';

    /** Password tidak cocok. */
    public const INVALID = 'invalid';

    /**
     * Hash tersimpan tidak terpakai dan tidak bisa dipulihkan dari password
     * yang dikirim. Password user belum tentu salah, jadi pesan ke user harus
     * mengarahkan ke admin, bukan "password salah".
     */
    public const UNUSABLE_HASH = 'unusable_hash';

    /**
     * @return self::VALID|self::INVALID|self::UNUSABLE_HASH
     */
    public function check(User $user, ?string $submitted): string
    {
        if ($submitted === null || $submitted === '') {
            return self::INVALID;
        }

        $stored = (string) $user->getAuthPassword();

        if ($this->isUsable($stored)) {
            return Hash::check($submitted, $stored) ? self::VALID : self::INVALID;
        }

        return $this->recover($user, $submitted, $stored);
    }

    /**
     * Hash hanya terpakai kalau benar-benar bcrypt — itu satu-satunya algoritma
     * yang diterima Hash::check() selama config hashing.verify aktif.
     */
    public function isUsable(?string $hash): bool
    {
        return is_string($hash)
            && $hash !== ''
            && password_get_info($hash)['algoName'] === 'bcrypt'
            && password_get_info($hash)['algo'] !== null;
    }

    /**
     * Pulihkan akun yang hash-nya tidak terpakai.
     *
     * Kredensial yang dicetak di kartu peserta adalah plain_password, jadi itu
     * yang dicocokkan lebih dulu. Kolom password yang ternyata berisi teks
     * biasa juga diterima, karena itu bentuk kerusakan data yang paling sering
     * ditemui. Begitu cocok, hash langsung ditulis ulang ke bcrypt supaya
     * login berikutnya lewat jalur normal.
     */
    protected function recover(User $user, string $submitted, string $stored): string
    {
        $candidates = array_filter([
            (string) $user->plain_password,
            $stored,
        ], static fn (string $candidate): bool => $candidate !== '');

        foreach ($candidates as $candidate) {
            if (hash_equals($candidate, $submitted)) {
                // Hash::make() eksplisit: cast 'hashed' pada model User
                // membiarkan nilai yang sudah berupa hash apa adanya, jadi
                // argon2/md5 tidak akan ikut ter-bcrypt sendiri.
                $user->forceFill(['password' => Hash::make($submitted)])->save();

                Log::warning('Hash password tidak sah, diperbaiki saat login.', [
                    'user_id' => $user->id,
                    'algo_lama' => $this->describe($stored),
                ]);

                return self::VALID;
            }
        }

        Log::warning('Login ditolak: hash password tidak sah dan tidak bisa dipulihkan.', [
            'user_id' => $user->id,
            'algo_lama' => $this->describe($stored),
        ]);

        return self::UNUSABLE_HASH;
    }

    /**
     * Nama algoritma untuk log — tidak pernah memuat isi hash-nya.
     */
    public function describe(?string $hash): string
    {
        if (!is_string($hash) || $hash === '') {
            return 'kosong';
        }

        $info = password_get_info($hash);

        return $info['algo'] === null
            ? 'bukan hash (teks biasa/md5/sha1)'
            : (string) $info['algoName'];
    }
}
