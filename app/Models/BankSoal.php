<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankSoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'mapel_id', 'guru_id', 'tipe_soal', 'tingkat_kesulitan', 'bobot_nilai',
        'pertanyaan', 'gambar_soal', 'pembahasan', 'status', 'kategori', 'tag', 'digunakan_count',
    ];

    public function mapel()
    {
        return $this->belongsTo(Mapel::class);
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    public function opsiJawabans()
    {
        return $this->hasMany(OpsiJawaban::class);
    }

    public function ujians()
    {
        return $this->belongsToMany(Ujian::class, 'ujian_bank_soals');
    }
}
