<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportBatch extends Model
{
    protected $fillable = [
        'created_by', 'status', 'success_count', 'imported_soals', 'error_message',
    ];

    protected $casts = [
        'imported_soals' => 'array',
    ];
}
