<?php

namespace App\Services;

use App\Models\Section;

/**
 * Where a teacher opened a class from (?from=teaching|advisory|dashboard), so every
 * class page leads back there instead of a fixed page. Only these three values are
 * accepted; anything else falls back to My Subjects.
 */
class TeacherNav
{
    public const ORIGINS = ['teaching', 'advisory', 'dashboard'];

    public static function from(): string
    {
        $from = request()->query('from');

        return in_array($from, self::ORIGINS, true) ? $from : 'teaching';
    }

    /** route() that keeps ?from= on links and redirects between the class pages. */
    public static function route(string $name, $parameters = []): string
    {
        $parameters = is_array($parameters) ? $parameters : [$parameters];

        return route($name, $parameters + ['from' => self::from()]);
    }

    /** Back button of the Class Record: [url, label]. */
    public static function recordBack(Section $section): array
    {
        return match (self::from()) {
            'dashboard' => [route('teacher.dashboard'), 'Back to Dashboard'],
            'advisory'  => [route('teacher.classes.show', [$section, 'from' => 'advisory']), 'Back to Section'],
            default     => [route('teacher.teaching'), 'Back to My Subjects'],
        };
    }

    /** Back button of the advisory section page: [url, label]. */
    public static function sectionBack(): array
    {
        return self::from() === 'dashboard'
            ? [route('teacher.dashboard'), 'Back to Dashboard']
            : [route('teacher.advisory'), 'Back to My Advisory'];
    }
}
