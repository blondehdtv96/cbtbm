<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'kelas';

    protected $fillable = ['nama_kelas', 'jurusan_id', 'tingkat', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class);
    }

    public function siswas()
    {
        return $this->hasMany(Siswa::class);
    }

    public function ujians()
    {
        return $this->belongsToMany(Ujian::class, 'kelas_ujian');
    }
}
