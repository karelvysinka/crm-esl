<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'system_settings';
    public $timestamps = true;
    protected $fillable = ['key','value'];

    public static function get(string $key, $default = null): mixed
    {
        $rec = static::query()->where('key', $key)->first();
        return $rec?->value ?? $default;
    }

    public static function set(string $key, $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
