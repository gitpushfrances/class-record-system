<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Splits a teacher's classes by the department that owns them: their own department first, then each
 * department they were lent to, so a borrowed teacher sees where each class belongs.
 * Each group is ['department' => ?Department, 'borrowed' => bool, 'items' => Collection].
 */
class DepartmentGroups
{
    public static function group(Collection $items, callable $departmentOf, ?int $homeDepartmentId): Collection
    {
        return $items
            ->groupBy(fn ($item) => $departmentOf($item)?->id ?? 0)
            ->map(function (Collection $rows) use ($departmentOf, $homeDepartmentId) {
                $department = $departmentOf($rows->first());

                return [
                    'department' => $department,
                    'borrowed'   => (bool) ($homeDepartmentId && $department && (int) $department->id !== $homeDepartmentId),
                    'items'      => $rows->values(),
                ];
            })
            ->sortBy(fn (array $group) => [$group['borrowed'] ? 1 : 0, mb_strtolower($group['department']?->name ?? '')])
            ->values();
    }
}
