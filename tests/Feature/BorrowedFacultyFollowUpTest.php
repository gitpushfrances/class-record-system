<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\Department;
use App\Models\FacultyBorrowRequest;
use App\Models\SectionTerm;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The borrowing Dean can revoke too, and a borrowed teacher's classes are separated by department. */
class BorrowedFacultyFollowUpTest extends TestCase
{
    use RefreshDatabase;

    private SectionTerm $ccsTerm;
    private SectionTerm $cbaTerm;
    private Subject $subject;
    private User $borrowingDean;
    private User $lendingDean;
    private User $borrowed;
    private FacultyBorrowRequest $loan;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $this->ccsTerm = SectionTerm::where('status', 'active')->whereHas('section.program', fn ($q) => $q->where('code', 'BSCS'))->firstOrFail();
        $this->cbaTerm = SectionTerm::where('status', 'active')->whereHas('section.program', fn ($q) => $q->where('code', 'BSBA'))->firstOrFail();
        $this->subject = Subject::firstOrFail();

        AcademicPeriod::query()->update(['is_active' => false]);
        AcademicPeriod::create(['school_year' => $this->ccsTerm->academic_year, 'semester' => $this->ccsTerm->semester, 'is_active' => true]);

        $this->borrowingDean = User::where('email', 'dean@classrecord.test')->firstOrFail();
        $this->lendingDean   = User::where('email', 'dean.cba@classrecord.test')->firstOrFail();
        $this->borrowed      = User::where('email', 'teacher.cba@classrecord.test')->firstOrFail();

        $this->loan = FacultyBorrowRequest::create([
            'borrowing_department_id' => $this->borrowingDean->department_id,
            'lending_department_id'   => $this->borrowed->department_id,
            'teacher_id'              => $this->borrowed->id,
            'academic_year'           => $this->ccsTerm->academic_year,
            'semester'                => $this->ccsTerm->semester,
            'status'                  => 'approved',
            'requested_by'            => $this->borrowingDean->id,
        ]);
    }

    private function teachBorrowedInCcs(): void
    {
        $this->ccsTerm->subjects()->attach($this->subject->id, ['teacher_id' => $this->borrowed->id]);
    }

    private function teachAtHome(): void
    {
        $this->cbaTerm->subjects()->attach($this->subject->id, ['teacher_id' => $this->borrowed->id]);
    }

    public function test_the_borrowing_dean_can_revoke_an_approved_loan_nobody_teaches_yet(): void
    {
        $this->actingAs($this->borrowingDean)->patch(route('dean.borrowing.revoke', $this->loan))->assertSessionHas('success');

        $this->assertSame('revoked', $this->loan->fresh()->status);
        $this->assertSame($this->borrowingDean->id, $this->loan->fresh()->decided_by);
    }

    public function test_a_loan_cannot_be_revoked_while_the_teacher_has_classes_from_it_by_either_dean(): void
    {
        $this->teachBorrowedInCcs();

        $this->actingAs($this->borrowingDean)->patch(route('dean.borrowing.revoke', $this->loan))->assertSessionHas('error');
        $this->actingAs($this->lendingDean)->patch(route('dean.borrowing.revoke', $this->loan))->assertSessionHas('error');

        $this->assertSame('approved', $this->loan->fresh()->status);
    }

    public function test_a_dean_of_a_third_department_cannot_revoke(): void
    {
        $outsider = User::factory()->create([
            'role' => 'dean', 'status' => 'active', 'department_id' => Department::where('code', 'COE')->value('id'),
        ]);

        $this->actingAs($outsider)->patch(route('dean.borrowing.revoke', $this->loan))->assertForbidden();
        $this->assertSame('approved', $this->loan->fresh()->status);
    }

    public function test_the_requests_table_of_the_borrowing_dean_has_a_revoke_button(): void
    {
        $this->actingAs($this->borrowingDean)->get(route('dean.borrowing.index'))
            ->assertOk()->assertSee('Your requests')->assertSee(route('dean.borrowing.revoke', $this->loan), false);
    }

    public function test_my_subjects_separates_a_borrowed_teachers_classes_by_department(): void
    {
        $this->teachAtHome();
        $this->teachBorrowedInCcs();

        $this->actingAs($this->borrowed)->get(route('teacher.teaching'))
            ->assertOk()
            ->assertSee('College of Business Administration')
            ->assertSee('College of Computer Studies')
            ->assertSee('Borrowed');
    }

    public function test_my_classes_separates_them_too(): void
    {
        $this->teachAtHome();
        $this->teachBorrowedInCcs();

        $this->actingAs($this->borrowed)->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('College of Business Administration')
            ->assertSee('College of Computer Studies')
            ->assertSee('Borrowed');
    }

    private function pendingLoan(): FacultyBorrowRequest
    {
        $other = User::factory()->create(['role' => 'teacher', 'status' => 'active', 'department_id' => $this->borrowed->department_id]);

        return FacultyBorrowRequest::create([
            'borrowing_department_id' => $this->borrowingDean->department_id,
            'lending_department_id'   => $other->department_id,
            'teacher_id'              => $other->id,
            'academic_year'           => $this->ccsTerm->academic_year,
            'semester'                => $this->ccsTerm->semester,
            'status'                  => 'pending',
            'requested_by'            => $this->borrowingDean->id,
        ]);
    }

    public function test_the_borrowing_page_has_no_long_instruction_paragraphs(): void
    {
        $this->pendingLoan();

        foreach ([$this->borrowingDean, $this->lendingDean] as $dean) {
            $this->actingAs($dean)->get(route('dean.borrowing.index'))
                ->assertOk()
                ->assertDontSee('Nothing changes until they approve it, and you can cancel it while it is pending')
                ->assertDontSee('Decline sends your reason back and nothing changes')
                ->assertDontSee('Cannot revoke yet')
                ->assertDontSee('Withdraws this request. Nothing was assigned.</p>', false);
        }
    }

    public function test_the_buttons_keep_a_short_tooltip(): void
    {
        $this->pendingLoan();

        $this->actingAs($this->borrowingDean)->get(route('dean.borrowing.index'))
            ->assertOk()
            ->assertSee('title="Sends your request to the other department', false)
            ->assertSee('title="Withdraws this request. Nothing was assigned."', false)
            ->assertSee('Hands the teacher back to CBA.');

        $this->actingAs($this->lendingDean)->get(route('dean.borrowing.index'))
            ->assertOk()
            ->assertSee('CCS can assign this teacher to its subjects until the semester ends.')
            ->assertSee('Takes the teacher back. CCS can no longer assign them.');
    }

    public function test_a_blocked_revoke_shows_a_short_note_to_both_deans(): void
    {
        $this->teachBorrowedInCcs();

        foreach ([$this->borrowingDean, $this->lendingDean] as $dean) {
            $this->actingAs($dean)->get(route('dean.borrowing.index'))
                ->assertOk()
                ->assertSee('Teaching 1 subject now');
        }

        $this->actingAs($this->borrowingDean)->get(route('dean.borrowing.index'))
            ->assertSee('Reassign this teacher');
    }

    private function loanOfAnEndedSemester(string $status): FacultyBorrowRequest
    {
        return FacultyBorrowRequest::create([
            'borrowing_department_id' => $this->borrowingDean->department_id,
            'lending_department_id'   => $this->borrowed->department_id,
            'teacher_id'              => $this->borrowed->id,
            'academic_year'           => '2023-2024',
            'semester'                => $this->ccsTerm->semester,
            'status'                  => $status,
            'requested_by'            => $this->borrowingDean->id,
        ]);
    }

    public function test_loans_of_an_ended_semester_are_not_listed_for_either_dean(): void
    {
        $this->loanOfAnEndedSemester('approved');

        foreach ([$this->borrowingDean, $this->lendingDean] as $dean) {
            $this->actingAs($dean)->get(route('dean.borrowing.index'))
                ->assertOk()
                ->assertSee($this->ccsTerm->academic_year)
                ->assertDontSee('2023-2024');
        }
    }

    public function test_a_request_of_an_ended_semester_is_not_counted_in_the_sidebar(): void
    {
        $this->loanOfAnEndedSemester('pending');

        $this->actingAs($this->lendingDean)->get(route('dean.borrowing.index'))
            ->assertOk()->assertDontSee('data-nav-badge', false);
    }

    public function test_with_no_active_period_no_loan_is_listed(): void
    {
        AcademicPeriod::query()->update(['is_active' => false]);

        foreach ([$this->borrowingDean, $this->lendingDean] as $dean) {
            $this->actingAs($dean)->get(route('dean.borrowing.index'))
                ->assertOk()
                ->assertSee('No other department has asked for your teachers.')
                ->assertDontSee($this->ccsTerm->academic_year);
        }
    }

    public function test_a_teacher_with_classes_in_one_department_sees_no_separator(): void
    {
        $this->teachAtHome();

        $this->actingAs($this->borrowed)->get(route('teacher.teaching'))
            ->assertOk()->assertDontSee('Borrowed')->assertDontSee('border-top:2px solid #d4af37', false);
    }
}
