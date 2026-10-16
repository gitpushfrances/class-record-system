<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /** Values printed on the Report of Grades; defaults follow form ESSU-ACAD-712.b. */
    public const ROG_DEFAULTS = [
        'rog_registrar_name' => '',
        'rog_form_code'      => 'ESSU-ACAD-712.b',
        'rog_form_version'   => 'Version 5',
        'rog_effectivity'    => 'March 15, 2024',
        'rog_logo_left'      => null,
        'rog_logo_right'     => null,
    ];

    /** Bundled logos used until the Admin uploads replacements. */
    public const LOGO_DEFAULTS = [
        'left'  => 'images/rog/essu-banner.png',
        'right' => 'images/rog/bagong-pilipinas.png',
    ];

    public static function rog(): array
    {
        $stored = static::whereIn('key', array_keys(self::ROG_DEFAULTS))->pluck('value', 'key')->all();

        return array_merge(self::ROG_DEFAULTS, array_filter($stored, fn ($v) => $v !== null));
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /** Public URL of a ROG logo: the uploaded file, or the bundled default. */
    public static function rogLogoUrl(string $side): string
    {
        $path = static::rog()["rog_logo_{$side}"];

        return $path ? asset('storage/' . $path) : asset(self::LOGO_DEFAULTS[$side]);
    }
}
