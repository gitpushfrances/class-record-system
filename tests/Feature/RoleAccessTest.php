<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every main page against every role: the owner gets the page, every other role is
 * refused, an Admin is sent to View As for Dean / Program Head pages, and a guest
 * is sent to sign in. Catches broken pages (500) and leaked access in one run.
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = [
        'super_admin'  => ['admin.dashboard', 'admin.view-as.index', 'admin.deans.index', 'admin.users.create', 'admin.subjects.index', 'admin.departments.index', 'admin.academic.index', 'admin.backup.index', 'admin.rog.index'],
        'dean'         => ['dean.dashboard', 'dean.faculty.index', 'dean.teachers.pending', 'dean.sections.index', 'dean.students.index', 'dean.subjects.index', 'dean.assignments.index', 'dean.enrollments.index', 'dean.program-heads.index', 'dean.promotions.index'],
        'program_head' => ['program-head.dashboard', 'program-head.sections.index', 'program-head.sections.create', 'program-head.students.index', 'program-head.students.create', 'program-head.subjects.index', 'program-head.subjects.create'],
        'teacher'      => ['teacher.dashboard', 'teacher.advisory', 'teacher.teaching', 'teacher.past-classes.index'],
    ];

    private const ACCOUNTS = [
        'super_admin'  => 'admin@classrecord.test',
        'dean'         => 'dean@classrecord.test',
        'program_head' => 'programhead@classrecord.test',
        'teacher'      => 'teacher@classrecord.test',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();
    }

    public function test_every_role_sees_only_its_own_pages(): void
    {
        foreach (self::ACCOUNTS as $role => $email) {
            $user = User::where('email', $email)->firstOrFail();

            foreach (self::PAGES as $owner => $routes) {
                foreach ($routes as $route) {
                    $response = $this->actingAs($user)->get(route($route));
                    $label    = "{$role} → {$route}";

                    if ($owner === $role) {
                        $this->assertSame(200, $response->getStatusCode(), "{$label} should open");
                    } elseif ($role === 'super_admin' && in_array($owner, ['dean', 'program_head'], true)) {
                        $this->assertTrue($response->isRedirect(route('admin.view-as.index')), "{$label} should go to View As");
                    } else {
                        $this->assertSame(403, $response->getStatusCode(), "{$label} should be refused");
                    }
                }
            }

            $this->actingAs($user)->get(route('profile.edit'))->assertOk();
        }
    }

    public function test_guests_are_sent_to_sign_in(): void
    {
        foreach (self::PAGES as $routes) {
            foreach ($routes as $route) {
                $this->get(route($route))->assertRedirect(route('login'));
            }
        }
    }
}
