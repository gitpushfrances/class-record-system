<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDepartmentStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Department $ccs;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $this->admin = User::where('email', 'admin@classrecord.test')->firstOrFail();
        $this->ccs   = Department::where('code', 'CCS')->firstOrFail();
    }

    public function test_a_duplicate_department_shows_its_error_once_inside_the_dialog(): void
    {
        // Open the page first so the failed form returns to it, as it does in the browser.
        $this->actingAs($this->admin)->get(route('admin.departments.index'))->assertOk();

        $this->actingAs($this->admin)->followingRedirects()
            ->post(route('admin.departments.store'), ['_form' => 'addDepartment', 'code' => 'CCS', 'name' => 'College of Computer Studies'])
            ->assertOk()
            ->assertSee('Another department already uses this code.')
            ->assertDontSee("title: 'Error'", false);
    }

    public function test_program_dialogs_show_program_examples(): void
    {
        $this->actingAs($this->admin)->get(route('admin.departments.show', $this->ccs))
            ->assertOk()->assertSee('e.g. BSIT')->assertSee('e.g. Bachelor of Science in Information Technology');
    }

    public function test_an_unused_department_is_deleted_and_a_used_one_is_refused(): void
    {
        $this->actingAs($this->admin)->post(route('admin.departments.store'), ['code' => 'TST', 'name' => 'Test College']);
        $test = Department::where('code', 'TST')->firstOrFail();

        $this->actingAs($this->admin)->delete(route('admin.departments.destroy', $test))
            ->assertRedirect(route('admin.departments.index'))->assertSessionHas('success');
        $this->assertDatabaseMissing('departments', ['id' => $test->id]);
        $this->actingAs($this->admin)->post(route('admin.departments.store'), ['code' => 'TST', 'name' => 'Test College'])->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->delete(route('admin.departments.destroy', $this->ccs))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Deactivate it instead'));
        $this->assertDatabaseHas('departments', ['id' => $this->ccs->id]);
    }

    public function test_a_deactivated_program_disappears_from_new_accounts_and_comes_back(): void
    {
        $this->actingAs($this->admin)->post(route('admin.programs.store', $this->ccs), ['code' => 'BSXTEST', 'name' => 'BS Test Program']);
        $program = Program::where('code', 'BSXTEST')->firstOrFail();
        // The account form's program list (page text also holds the flash message, which names the program).
        $offered = fn () => $this->actingAs($this->admin)->get(route('admin.users.create'))->viewData('programs')->pluck('code');
        $this->assertTrue($offered()->contains('BSXTEST'));

        $this->actingAs($this->admin)->patch(route('admin.programs.status', [$this->ccs, $program]))->assertSessionHas('success');
        $this->assertSame('inactive', $program->fresh()->status);
        $this->assertFalse($offered()->contains('BSXTEST'));
        $this->actingAs($this->admin)->get(route('admin.departments.show', $this->ccs))->assertSee('Inactive');

        $this->actingAs($this->admin)->patch(route('admin.programs.status', [$this->ccs, $program]));
        $this->assertSame('approved', $program->fresh()->status);
    }

    public function test_a_program_in_use_is_not_deleted_and_an_unused_one_is(): void
    {
        $bscs = Program::where('code', 'BSCS')->firstOrFail();
        $this->actingAs($this->admin)->delete(route('admin.programs.destroy', [$this->ccs, $bscs]))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Deactivate it instead'));
        $this->assertDatabaseHas('programs', ['id' => $bscs->id]);

        $this->actingAs($this->admin)->post(route('admin.programs.store', $this->ccs), ['code' => 'BSXTEST', 'name' => 'BS Test Program']);
        $unused = Program::where('code', 'BSXTEST')->firstOrFail();
        $this->actingAs($this->admin)->delete(route('admin.programs.destroy', [$this->ccs, $unused]))
            ->assertRedirect(route('admin.departments.show', $this->ccs));
        $this->assertDatabaseMissing('programs', ['id' => $unused->id]);
    }
}
