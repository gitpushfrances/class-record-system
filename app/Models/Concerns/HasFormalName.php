<?php

namespace App\Models\Concerns;

trait HasFormalName
{
    public static function bootHasFormalName(): void
    {
        static::saving(function ($model) {
            if (filled($model->last_name) && filled($model->first_name)) {
                $model->name = static::formalName($model->last_name, $model->first_name, $model->middle_name);
            }
        });
    }

    /** Shared validation rules for the three name parts. */
    public static function nameRules(): array
    {
        $part = ['string', 'max:100', "regex:/^[\\pL\\s.'\\-]+\$/u"];

        return [
            'last_name'   => array_merge(['required'], $part),
            'first_name'  => array_merge(['required'], $part),
            'middle_name' => array_merge(['nullable'], $part),
        ];
    }

    /** "Dela Cruz, Juan M." */
    public static function formalName(string $last, string $first, ?string $middle = null): string
    {
        return trim($last) . ', ' . trim($first) . static::middleInitial($middle);
    }

    /** "Juan M. Dela Cruz" (falls back to the stored name for legacy accounts). */
    public function getDisplayNameAttribute(): string
    {
        if (filled($this->last_name) && filled($this->first_name)) {
            return trim($this->first_name) . static::middleInitial($this->middle_name) . ' ' . trim($this->last_name);
        }

        return (string) $this->name;
    }

    protected static function middleInitial(?string $middle): string
    {
        return filled($middle) ? ' ' . mb_strtoupper(mb_substr(trim($middle), 0, 1)) . '.' : '';
    }
}
