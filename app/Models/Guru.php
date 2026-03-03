<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guru extends Model
{
    use HasFactory;

    protected $fillable = ['nip', 'nama', 'user_id', 'telepon', 'foto'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bankSoals()
    {
        return $this->hasMany(BankSoal::class);
    }

    public function ujians()
    {
        return $this->hasMany(Ujian::class);
    }

    public function mapels()
    {
        return $this->belongsToMany(Mapel::class , 'guru_mapel')->withTimestamps();
    }
}
