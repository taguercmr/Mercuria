<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();

        return $row?->value ?? $default;
    }

    public static function setValue(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value],
        );
    }

    public static function maintenanceEnabled(): bool
    {
        return static::getValue('maintenance_enabled', '0') === '1';
    }

    public static function maintenanceMessage(): string
    {
        return (string) static::getValue(
            'maintenance_message',
            config('mercuria.maintenance.default_message'),
        );
    }
}
