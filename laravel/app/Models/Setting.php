<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Keys that should be encrypted/decrypted automatically.
     *
     * @var string[]
     */
    protected static array $encryptedKeys = [
        'hemis.token',
        'hemis.oauth.client_id',
        'hemis.oauth.client_secret',
    ];

    /**
     * Get a setting value by key, with an optional default.
     * Automatically decrypts sensitive keys.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        if (in_array($key, static::$encryptedKeys)) {
            try {
                return Crypt::decryptString($setting->value);
            } catch (DecryptException) {
                // Gracefully handle pre-existing unencrypted values
                return $setting->value;
            }
        }

        return $setting->value;
    }

    /**
     * Set a setting value by key. Creates or updates automatically.
     * Automatically encrypts sensitive keys.
     */
    public static function set(string $key, mixed $value): void
    {
        $storedValue = in_array($key, static::$encryptedKeys)
            ? Crypt::encryptString($value)
            : $value;

        self::updateOrCreate(['key' => $key], ['value' => $storedValue]);
    }
}
