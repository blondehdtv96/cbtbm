<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SoalReview extends Model
{
    protected $fillable = ['bank_soal_id', 'user_id', 'status', 'catatan'];

    public function bankSoal()
    {
        return $this->belongsTo(BankSoal::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Server produksi kerap tertinggal migrasi; fitur review harus mati dengan tenang, bukan 500.
    public static function tableReady(): bool
    {
        static $ready;
        return $ready ??= Schema::hasTable('soal_reviews');
    }
}
