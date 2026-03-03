<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jurusan extends Model
{
    use HasFactory;

    protected $fillable = ['nama_jurusan', 'kode_jurusan', 'deskripsi', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function kelas()
    {
        return $this->hasMany(Kelas::class);
    }

    public function mapels()
    {
        return $this->hasMany(Mapel::class);
    }
}
