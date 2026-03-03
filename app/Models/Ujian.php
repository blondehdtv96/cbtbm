<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ujian extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_ujian', 'jenis_ujian', 'mapel_id', 'guru_id', 'sesi_ujian_id',
        'durasi_menit', 'tanggal_mulai', 'tanggal_selesai', 'metode_soal',
        'acak_opsi', 'jumlah_soal', 'status', 'token', 'tampilkan_nilai',
        'tampilkan_pembahasan', 'instruksi',
    ];

    protected $casts = [
        'tanggal_mulai' => 'datetime',
        'tanggal_selesai' => 'datetime',
        'acak_opsi' => 'boolean',
        'tampilkan_nilai' => 'boolean',
        'tampilkan_pembahasan' => 'boolean',
    ];

    public function sesiUjian()
    {
        return $this->belongsTo(SesiUjian::class);
    }

    public function mapel()
    {
        return $this->belongsTo(Mapel::class);
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    public function bankSoals()
    {
        return $this->belongsToMany(BankSoal::class , 'ujian_bank_soals')->withPivot('nomor_urut');
    }

    public function pesertaUjians()
    {
        return $this->hasMany(PesertaUjian::class);
    }

    public function kelasList()
    {
        return $this->belongsToMany(Kelas::class , 'kelas_ujian');
    }

    public function isActive(): bool
    {
        return $this->status === 'publish' &&
            now()->between($this->tanggal_mulai, $this->tanggal_selesai);
    }
}
