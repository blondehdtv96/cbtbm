<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    use HasFactory;

    protected $fillable = ['nis', 'nisn', 'nama', 'kelas_id', 'user_id', 'foto', 'telepon'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function pesertaUjians()
    {
        return $this->hasMany(PesertaUjian::class);
    }
}
