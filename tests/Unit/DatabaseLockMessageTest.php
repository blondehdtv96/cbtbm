<?php

namespace Tests\Unit;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\TestCase;

/**
 * Deadlock / lock wait timeout bukan kesalahan data, jadi admin harus membaca
 * saran mengulang — bukan SQL mentah seperti
 * "SQLSTATE[40001]: Serialization failure: 1213 Deadlock found...".
 */
class DatabaseLockMessageTest extends TestCase
{
    private function controller(): Controller
    {
        return new class extends Controller {
            public function message(\Throwable $e, string $prefix): string
            {
                return $this->writeErrorMessage($e, $prefix);
            }
        };
    }

    private function queryException(int $code, string $message): QueryException
    {
        $previous = new \PDOException($message);
        $previous->errorInfo = ['40001', $code, $message];

        return new QueryException('mysql', 'insert into `users` (`name`) values (?)', ['Ahmad'], $previous);
    }

    public function test_deadlock_diterjemahkan_jadi_saran_mengulang(): void
    {
        $message = $this->controller()->message(
            $this->queryException(1213, 'SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction'),
            'Gagal import data'
        );

        $this->assertStringStartsWith('Gagal import data: database sedang sibuk', $message);
        $this->assertStringContainsString('Tidak ada data yang tersimpan', $message);
        $this->assertStringNotContainsString('SQLSTATE', $message);
        $this->assertStringNotContainsString('insert into', $message);
    }

    public function test_lock_wait_timeout_juga_diterjemahkan(): void
    {
        $message = $this->controller()->message(
            $this->queryException(1205, 'SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded; try restarting transaction'),
            'Gagal menghapus data terpilih'
        );

        $this->assertStringStartsWith('Gagal menghapus data terpilih: database sedang sibuk', $message);
    }

    public function test_error_lain_tetap_menampilkan_pesan_aslinya(): void
    {
        $message = $this->controller()->message(
            new \RuntimeException('Kelas tidak ditemukan'),
            'Gagal import data'
        );

        $this->assertSame('Gagal import data: Kelas tidak ditemukan', $message);
    }
}
