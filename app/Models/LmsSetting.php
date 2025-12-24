<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsSetting extends Model
{
    protected $table = 'lms_settings';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getInt(string $key, int $default): int
    {
        $row = static::where('key', $key)->first();
        if (!$row || $row->value === null) {
            return $default;
        }
        $val = filter_var($row->value, FILTER_VALIDATE_INT);
        return $val === false ? $default : (int)$val;
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value === null ? null : (string)$value]
        );
    }
}
