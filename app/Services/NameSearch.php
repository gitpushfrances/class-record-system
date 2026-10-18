<?php

namespace App\Services;

use Illuminate\Support\HtmlString;

/**
 * One search rule for every search box: each typed word must match the START of a word in the
 * last or first name, or of a part of the student number (parts are split at "-"). An e-mail
 * address is searched only when the typed word contains "@". So "de" finds "Dela Cruz", not every
 * address ending in "student.test". The same rule runs in the browser (layouts/partials/live-search).
 */
class NameSearch
{
    /** @return string[] lower-case search words */
    public static function terms(?string $search): array
    {
        $words = preg_split('/[\s,]+/u', mb_strtolower(trim((string) $search))) ?: [];

        return array_values(array_filter($words, fn ($w) => $w !== ''));
    }

    /** Adds the search to an Eloquent or query builder. */
    public static function apply($query, ?string $search, array $nameColumns, ?string $numberColumn = null, ?string $emailColumn = null)
    {
        foreach (self::terms($search) as $term) {
            $like = addcslashes($term, '%_\\');

            $query->where(function ($q) use ($like, $term, $nameColumns, $numberColumn, $emailColumn) {
                foreach ($nameColumns as $column) {
                    $q->orWhere($column, 'like', "{$like}%")
                      ->orWhere($column, 'like', "% {$like}%")
                      ->orWhere($column, 'like', "%-{$like}%");
                }
                if ($numberColumn) {
                    $q->orWhere($numberColumn, 'like', "{$like}%")
                      ->orWhere($numberColumn, 'like', "%-{$like}%");
                }
                if ($emailColumn && str_contains($term, '@')) {
                    $q->orWhere($emailColumn, 'like', "{$like}%");
                }
            });
        }

        return $query;
    }

    /** The text, HTML-escaped, with each typed word marked where it starts a word. $emailOnly marks only words containing "@". */
    public static function highlight(?string $text, ?string $search, bool $emailOnly = false): HtmlString
    {
        $text  = (string) $text;
        $terms = self::terms($search);
        if ($emailOnly) {
            $terms = array_filter($terms, fn ($t) => str_contains($t, '@'));
        }

        $chars = mb_str_split($text);
        if (!$chars || !$terms) {
            return new HtmlString(e($text));
        }

        $lower = array_map('mb_strtolower', $chars);
        $mark  = array_fill(0, count($chars), false);

        foreach ($terms as $term) {
            $termChars = mb_str_split($term);
            $length    = count($termChars);
            for ($i = 0; $i + $length <= count($lower); $i++) {
                $atWordStart = $i === 0 || !preg_match('/[\p{L}\p{N}]/u', $lower[$i - 1]);
                if ($atWordStart && array_slice($lower, $i, $length) === $termChars) {
                    for ($k = $i; $k < $i + $length; $k++) {
                        $mark[$k] = true;
                    }
                }
            }
        }

        $html = '';
        $open = false;
        foreach ($chars as $i => $char) {
            if ($mark[$i] !== $open) {
                $html .= $mark[$i] ? '<mark class="search-hit">' : '</mark>';
                $open = $mark[$i];
            }
            $html .= e($char);
        }

        return new HtmlString($html . ($open ? '</mark>' : ''));
    }
}
