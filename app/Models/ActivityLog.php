<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'module', 'description', 'ip_address', 'user_agent', 'data'];

    protected $casts = ['data' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log($action, $module, $description = null, $data = null)
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data' => $data,
        ]);
    }
}
