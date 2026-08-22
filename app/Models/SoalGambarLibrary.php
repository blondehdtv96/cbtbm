<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoalGambarLibrary extends Model
{
    protected $table = 'soal_gambar_library';

    protected $fillable = [
        'original_filename', 'stored_path', 'size', 'mime_type', 'uploaded_by',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
