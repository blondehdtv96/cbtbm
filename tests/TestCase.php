<?php

namespace Tests;

use App\Http\Middleware\LogoutOnNewDay;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // LogoutOnNewDay membandingkan cookie "login_date" dengan tanggal hari ini
        // dan me-logout sesi kalau berbeda. actingAs() tidak menyetel cookie itu,
        // sehingga tanpa baris ini SETIAP request test yang login akan dialihkan
        // ke halaman login. Test yang ingin menguji logout harian bisa
        // menghapusnya sendiri lewat withCookie()/withoutMiddleware().
        $this->withCookie(LogoutOnNewDay::COOKIE, now()->toDateString());

        // Request JSON hanya ikut mengirim cookie kalau kredensial diaktifkan,
        // jadi tanpa ini endpoint JSON (misal upload Pustaka Gambar Soal) ikut
        // terkena logout harian di atas.
        $this->withCredentials();
    }
}
