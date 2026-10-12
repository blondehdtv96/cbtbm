<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Berapa kali sebuah transaksi tulis dicoba ulang. MySQL memilih salah satu
     * transaksi sebagai "korban" saat dua transaksi saling menunggu lock
     * (deadlock); yang jadi korban cukup dijalankan ulang. Dipakai sebagai
     * argumen kedua DB::transaction().
     */
    protected const TRANSACTION_ATTEMPTS = 5;

    /**
     * Pesan kegagalan tulis yang bisa dipahami admin. Deadlock / lock wait
     * timeout bukan masalah data, jadi diterjemahkan menjadi saran mengulang
     * alih-alih menampilkan SQL mentah.
     */
    protected function writeErrorMessage(\Throwable $e, string $prefix): string
    {
        if ($e instanceof QueryException && $this->isLockError($e)) {
            return $prefix . ': database sedang sibuk karena ada aktivitas lain yang menulis data '
                . '(misalnya siswa yang sedang login atau ujian yang sedang berjalan). '
                . 'Tidak ada data yang tersimpan — silakan ulangi, sebaiknya di luar jam ujian.';
        }

        return $prefix . ': ' . $e->getMessage();
    }

    /**
     * 1213 = deadlock, 1205 = lock wait timeout.
     */
    protected function isLockError(QueryException $e): bool
    {
        $code = (int) ($e->errorInfo[1] ?? 0);

        return in_array($code, [1213, 1205], true)
            || str_contains($e->getMessage(), 'Deadlock')
            || str_contains($e->getMessage(), 'Lock wait timeout');
    }
}
