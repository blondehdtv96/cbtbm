<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpsiJawaban extends Model
{
    use HasFactory;

    protected $fillable = ['bank_soal_id', 'opsi_label', 'isi_opsi', 'gambar_opsi', 'is_correct'];

    protected $casts = ['is_correct' => 'boolean'];

    public function bankSoal()
    {
        return $this->belongsTo(BankSoal::class);
    }
}
