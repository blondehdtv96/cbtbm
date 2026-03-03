<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mapel extends Model
{
    use HasFactory;

    protected $fillable = ['nama_mapel', 'kode_mapel', 'jurusan_id', 'is_umum', 'is_active'];

    protected $casts = ['is_umum' => 'boolean', 'is_active' => 'boolean'];

    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class);
    }

    public function bankSoals()
    {
        return $this->hasMany(BankSoal::class);
    }

    public function ujians()
    {
        return $this->hasMany(Ujian::class);
    }

    public function gurus()
    {
        return $this->belongsToMany(Guru::class , 'guru_mapel')->withTimestamps();
    }
}
