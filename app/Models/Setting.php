<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key, with an optional default.
     * Caches the setting forever until updated.
     */
    public static function get(string $key, $default = null)
    {
        // For larger apps, use caching here: Cache::rememberForever("setting.{$key}", ...)
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key. Creates or updates automatically.
     */
    public static function set(string $key, $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        
        // Cache::forget("setting.{$key}");
    }
}
