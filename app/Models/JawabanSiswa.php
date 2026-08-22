<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JawabanSiswa extends Model
{
    use HasFactory;

    protected $fillable = [
        'peserta_ujian_id', 'bank_soal_id', 'jawaban_dipilih', 'jawaban_file',
        'is_ragu', 'nilai', 'is_correct',
    ];

    protected $casts = [
        'is_ragu' => 'boolean',
        'is_correct' => 'boolean',
        'nilai' => 'decimal:2',
    ];

    public function pesertaUjian()
    {
        return $this->belongsTo(PesertaUjian::class);
    }

    public function bankSoal()
    {
        return $this->belongsTo(BankSoal::class);
    }
}
