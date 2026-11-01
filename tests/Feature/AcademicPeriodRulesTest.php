<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\SectionTerm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The Administrator cannot add a semester that has already ended, in any school year or semester. */
class AcademicPeriodRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $this->admin = User::where('email', 'admin@classrecord.test')->firstOrFail();
    }

    private function add(array $overrides = [])
    {
        return $this->actingAs($this->admin)->post(route('admin.academic.store'), $overrides + [
            'school_year'         => '2098-2099',
            'semester'            => '1st Semester',
            'start_date'          => '2098-08-03',
            'midterm_cutoff_date' => '2098-10-02',
            'finals_cutoff_date'  => '2098-12-04',
        ]);
    }

    public function test_an_upcoming_semester_is_added_with_its_start_date(): void
    {
        $this->add()->assertSessionHas('success');

        $period = AcademicPeriod::firstOrFail();
        $this->assertSame('2098-2099', $period->school_year);
        $this->assertSame('2098-08-03', $period->start_date->toDateString());
        $this->assertFalse((bool) $period->is_active);
    }

    public function test_the_first_semester_of_a_past_school_year_is_refused_like_the_second(): void
    {
        $this->add(['school_year' => '2024-2025', 'start_date' => '2024-08-05', 'midterm_cutoff_date' => '2024-10-04', 'finals_cutoff_date' => '2024-12-06'])
            ->assertSessionHas('error', 'Cannot add a semester that has already ended.');

        $this->add(['school_year' => '2024-2025', 'semester' => '2nd Semester', 'start_date' => '2025-01-06', 'midterm_cutoff_date' => '2025-03-07', 'finals_cutoff_date' => '2025-05-16'])
            ->assertSessionHas('error', 'Cannot add a semester that has already ended.');

        $this->assertSame(0, AcademicPeriod::count());
    }

    public function test_a_first_semester_that_ended_earlier_in_the_current_calendar_year_is_refused(): void
    {
        // The old rule compared the school year's last year with the month, so in February 2026 it still accepted 1st Semester 2025-2026.
        $this->travelTo('2026-02-10');

        $this->add(['school_year' => '2025-2026', 'start_date' => '2025-08-04', 'midterm_cutoff_date' => '2025-10-03', 'finals_cutoff_date' => '2025-12-05'])
            ->assertSessionHas('error', 'Cannot add a semester that has already ended.');

        $this->add(['school_year' => '2025-2026', 'semester' => '2nd Semester', 'start_date' => '2026-01-05', 'midterm_cutoff_date' => '2026-03-06', 'finals_cutoff_date' => '2026-05-15'])
            ->assertSessionHas('success');

        $this->travelBack();
        $this->assertSame(1, AcademicPeriod::count());
    }

    public function test_a_semester_still_running_is_allowed(): void
    {
        $this->travelTo('2026-10-28');

        $this->add(['school_year' => '2026-2027', 'start_date' => '2026-08-03', 'midterm_cutoff_date' => '2026-10-30', 'finals_cutoff_date' => '2026-12-04'])
            ->assertSessionHas('success');

        $this->travelBack();
    }

    public function test_the_school_year_must_be_two_consecutive_years(): void
    {
        $this->add(['school_year' => '2098/2099'])->assertSessionHasErrors('school_year');
        $this->add(['school_year' => '2098-2100'])->assertSessionHasErrors('school_year');
        $this->add(['school_year' => 'abc'])->assertSessionHasErrors('school_year');

        $this->assertSame(0, AcademicPeriod::count());
    }

    public function test_the_dates_must_be_in_order_and_inside_the_school_year(): void
    {
        $this->add(['midterm_cutoff_date' => '2098-08-01'])->assertSessionHasErrors('midterm_cutoff_date');
        $this->add(['finals_cutoff_date' => '2098-10-01'])->assertSessionHasErrors('finals_cutoff_date');
        $this->add(['start_date' => '2090-08-03'])->assertSessionHasErrors('start_date');
        $this->add(['finals_cutoff_date' => '2100-01-15'])->assertSessionHasErrors('finals_cutoff_date');

        $this->assertSame(0, AcademicPeriod::count());
    }

    public function test_a_duplicate_semester_is_still_refused(): void
    {
        $this->add()->assertSessionHas('success');
        $this->add()->assertSessionHas('error', 'This semester for that school year already exists.');

        $this->assertSame(1, AcademicPeriod::count());
    }

    public function test_dates_can_be_corrected_and_an_old_period_gets_its_start_date(): void
    {
        $period = AcademicPeriod::create([
            'school_year' => '2098-2099', 'semester' => '1st Semester',
            'midterm_cutoff_date' => '2098-10-02', 'finals_cutoff_date' => '2098-12-04', 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->patch(route('admin.academic.update', $period), [
            'start_date' => '2098-08-10', 'midterm_cutoff_date' => '2098-10-09', 'finals_cutoff_date' => '2098-12-11',
        ])->assertSessionHas('success');

        $period->refresh();
        $this->assertSame('2098-08-10', $period->start_date->toDateString());
        $this->assertSame('2098-12-11', $period->finals_cutoff_date->toDateString());
        $this->assertTrue((bool) $period->is_active);

        $this->actingAs($this->admin)->patch(route('admin.academic.update', $period), [
            'start_date' => '2098-10-20', 'midterm_cutoff_date' => '2098-10-09', 'finals_cutoff_date' => '2098-12-11',
        ])->assertSessionHasErrors('midterm_cutoff_date');
    }

    public function test_the_page_asks_for_the_semester_start(): void
    {
        $this->actingAs($this->admin)->get(route('admin.academic.index'))
            ->assertOk()->assertSee('Semester Start')->assertSee('finals cutoff has already passed');
    }

    public function test_every_button_on_the_page_says_what_it_will_do(): void
    {
        AcademicPeriod::create(['school_year' => '2098-2099', 'semester' => '1st Semester', 'start_date' => '2098-08-03', 'midterm_cutoff_date' => '2098-10-02', 'finals_cutoff_date' => '2098-12-04', 'is_active' => true]);
        AcademicPeriod::create(['school_year' => '2098-2099', 'semester' => '2nd Semester', 'start_date' => '2099-01-05', 'midterm_cutoff_date' => '2099-03-06', 'finals_cutoff_date' => '2099-05-15', 'is_active' => false]);

        $this->actingAs($this->admin)->get(route('admin.academic.index'))
            ->assertOk()
            ->assertSee('Adds the semester as Inactive')
            ->assertSee('Makes this the current semester and deactivates the one that is active now')
            ->assertSee('Permanently removes this period. Refused if a class term still uses it.')
            ->assertSee('Teachers are recording inside these dates now')
            ->assertSee('Changes the start and the cutoffs. Records already saved are kept');
    }

    public function test_the_dates_are_not_limited_to_june_to_august(): void
    {
        // A summer term that runs into September, in a school year the old June-August rule rejected.
        $this->add([
            'semester' => 'Summer', 'start_date' => '2099-06-01', 'midterm_cutoff_date' => '2099-07-15', 'finals_cutoff_date' => '2099-09-15',
        ])->assertSessionHas('success');

        $this->add([
            'semester' => '2nd Semester', 'start_date' => '2099-01-05', 'midterm_cutoff_date' => '2099-03-06', 'finals_cutoff_date' => '2099-05-15',
        ])->assertSessionHas('success');

        $this->assertSame(2, AcademicPeriod::count());
    }

    public function test_a_semester_is_over_by_the_manila_date_not_the_utc_date(): void
    {
        // 18:00 UTC on Dec 4 is already Dec 5 in Manila: a semester whose finals cutoff was Dec 4 has ended.
        $this->travelTo('2098-12-04 18:00:00');

        $this->add(['school_year' => '2098-2099'])->assertSessionHas('error', 'Cannot add a semester that has already ended.');

        $this->travelBack();
        $this->assertSame(0, AcademicPeriod::count());
    }

    public function test_a_period_that_an_active_class_term_uses_cannot_be_deleted(): void
    {
        $term   = SectionTerm::where('status', 'active')->firstOrFail();
        $period = AcademicPeriod::create(['school_year' => $term->academic_year, 'semester' => $term->semester, 'is_active' => false]);

        $this->actingAs($this->admin)->delete(route('admin.academic.destroy', $period))
            ->assertSessionHas('error');

        $this->assertNotNull($period->fresh());

        // Once every class of that semester is completed nothing needs the period any more.
        SectionTerm::where('academic_year', $term->academic_year)->where('semester', $term->semester)->update(['status' => 'completed']);

        $this->actingAs($this->admin)->delete(route('admin.academic.destroy', $period))->assertSessionHas('success', 'Period deleted.');
        $this->assertNull(AcademicPeriod::find($period->id));
    }

    public function test_a_period_nobody_uses_is_deleted_and_the_active_one_is_not(): void
    {
        $unused = AcademicPeriod::create(['school_year' => '2098-2099', 'semester' => '1st Semester', 'is_active' => false]);
        $active = AcademicPeriod::create(['school_year' => '2098-2099', 'semester' => '2nd Semester', 'is_active' => true]);

        $this->actingAs($this->admin)->delete(route('admin.academic.destroy', $active))->assertSessionHas('error', 'Cannot delete the active period.');
        $this->actingAs($this->admin)->delete(route('admin.academic.destroy', $unused))->assertSessionHas('success', 'Period deleted.');

        $this->assertNotNull($active->fresh());
        $this->assertNull(AcademicPeriod::find($unused->id));
    }

    public function test_a_period_without_all_three_dates_cannot_be_made_active(): void
    {
        $incomplete = AcademicPeriod::create([
            'school_year' => '2098-2099', 'semester' => '1st Semester', 'midterm_cutoff_date' => '2098-10-02', 'finals_cutoff_date' => '2098-12-04', 'is_active' => false,
        ]);

        $this->actingAs($this->admin)->patch(route('admin.academic.setActive', $incomplete))->assertSessionHas('error');
        $this->assertFalse((bool) $incomplete->fresh()->is_active);

        $this->actingAs($this->admin)->patch(route('admin.academic.update', $incomplete), [
            'start_date' => '2098-08-03', 'midterm_cutoff_date' => '2098-10-02', 'finals_cutoff_date' => '2098-12-04',
        ])->assertSessionHas('success');

        $this->actingAs($this->admin)->patch(route('admin.academic.setActive', $incomplete))->assertSessionHas('success', 'Active period updated.');
        $this->assertTrue((bool) $incomplete->fresh()->is_active);
    }
}
