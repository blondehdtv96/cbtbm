<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesertaUjian extends Model
{
    use HasFactory;

    protected $fillable = [
        'ujian_id', 'siswa_id', 'waktu_mulai', 'waktu_selesai',
        'nilai', 'status', 'soal_order',
    ];

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
        'nilai' => 'decimal:2',
    ];

    public function ujian()
    {
        return $this->belongsTo(Ujian::class);
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function jawabanSiswas()
    {
        return $this->hasMany(JawabanSiswa::class);
    }

    public function getSoalOrderArray(): array
    {
        return $this->soal_order ? json_decode($this->soal_order, true) : [];
    }
}
