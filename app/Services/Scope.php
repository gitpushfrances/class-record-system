<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Program;

/**
 * The department/program the signed-in user works in.
 * Deans and Program Heads use their own assignment. An Admin uses the
 * department or program picked in "View as", so every Dean and Program Head
 * page works for the Admin without separate admin copies of those pages.
 */
class Scope
{
    private const SESSION = 'view_as';

    /** The role whose pages the user is working in. */
    public static function role(): ?string
    {
        return self::viewingAs()['role'] ?? auth()->user()?->role;
    }

    /** The Admin's active "View as" selection, or null. */
    public static function viewingAs(): ?array
    {
        return auth()->user()?->isSuperAdmin() ? session(self::SESSION) : null;
    }

    public static function departmentId(): ?int
    {
        $id = ($viewAs = self::viewingAs()) ? $viewAs['department_id'] : auth()->user()?->department_id;

        return $id === null ? null : (int) $id;
    }

    public static function programId(): ?int
    {
        $id = ($viewAs = self::viewingAs()) ? $viewAs['program_id'] : auth()->user()?->program_id;

        return $id === null ? null : (int) $id;
    }

    public static function viewAsDean(Department $department): void
    {
        session([self::SESSION => [
            'role'          => 'dean',
            'department_id' => $department->id,
            'program_id'    => null,
            'label'         => "Dean · {$department->code}",
        ]]);
    }

    public static function viewAsProgramHead(Program $program): void
    {
        session([self::SESSION => [
            'role'          => 'program_head',
            'department_id' => $program->department_id,
            'program_id'    => $program->id,
            'label'         => "Program Head · {$program->code}",
        ]]);
    }

    public static function stop(): void
    {
        session()->forget(self::SESSION);
    }
}
