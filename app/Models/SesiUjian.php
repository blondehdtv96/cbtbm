<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SesiUjian extends Model
{
    use HasFactory;

    protected $fillable = ['nama_sesi', 'jam_mulai', 'jam_selesai', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function ujians()
    {
        return $this->hasMany(Ujian::class);
    }

    /**
     * Format jam untuk tampilan (07:30 - 09:30)
     */
    public function getJamFormatAttribute(): string
    {
        return substr($this->jam_mulai, 0, 5) . ' – ' . substr($this->jam_selesai, 0, 5);
    }
}
