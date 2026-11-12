<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirmSetting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    public static function setValue(string $key, mixed $value, string $group = 'general'): self
    {
        return static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value, 'group' => $group]
        );
    }

    public static function allAsMap(): array
    {
        return static::query()->pluck('value', 'key')->toArray();
    }
}

