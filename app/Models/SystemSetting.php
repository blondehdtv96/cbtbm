<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'order',
    ];

    /**
     * Get setting value by key
     */
    public static function get($key, $default = null)
    {
        return Cache::remember("setting:{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set setting value
     */
    public static function set($key, $value)
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        // Clear cache
        Cache::forget("setting:{$key}");
        Cache::forget('settings:all');

        return $setting;
    }

    /**
     * Get all settings
     */
    public static function getAll()
    {
        return Cache::remember('settings:all', 3600, function () {
            return self::all()->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Get settings by group
     */
    public static function getByGroup($group)
    {
        return Cache::remember("settings:group:{$group}", 3600, function () use ($group) {
            return self::where('group', $group)
                ->orderBy('order')
                ->get();
        });
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache()
    {
        Cache::forget('settings:all');
        
        $keys = self::pluck('key');
        foreach ($keys as $key) {
            Cache::forget("setting:{$key}");
        }

        $groups = self::distinct()->pluck('group');
        foreach ($groups as $group) {
            Cache::forget("settings:group:{$group}");
        }
    }

    /**
     * Get typed value
     */
    public function getTypedValue()
    {
        switch ($this->type) {
            case 'boolean':
                return (bool) $this->value;
            case 'number':
                return (int) $this->value;
            case 'color':
            case 'image':
            case 'text':
            case 'textarea':
            default:
                return $this->value;
        }
    }
}
