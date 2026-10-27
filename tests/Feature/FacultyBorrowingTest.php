<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\FacultyBorrowRequest;
use App\Models\SectionTerm;
use App\Models\Subject;
use App\Models\User;
use App\Services\FacultyBorrowing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** A Dean borrows a teacher of another department for one semester; the lending Dean approves. */
class FacultyBorrowingTest extends TestCase
{
    use RefreshDatabase;

    private SectionTerm $term;
    private User $borrowingDean;
    private User $lendingDean;
    private User $borrowed;   // CBA teacher
    private User $own;        // CCS teacher

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $this->term = SectionTerm::where('status', 'active')
            ->whereHas('section.program', fn ($q) => $q->where('code', 'BSCS'))
            ->firstOrFail();

        AcademicPeriod::query()->update(['is_active' => false]);
        AcademicPeriod::create(['school_year' => $this->term->academic_year, 'semester' => $this->term->semester, 'is_active' => true]);

        $this->borrowingDean = User::where('email', 'dean@classrecord.test')->firstOrFail();
        $this->lendingDean   = User::where('email', 'dean.cba@classrecord.test')->firstOrFail();
        $this->borrowed      = User::where('email', 'teacher.cba@classrecord.test')->firstOrFail();
        $this->own           = User::where('email', 'teacher@classrecord.test')->firstOrFail();
    }

    private function loan(string $status = 'pending', ?string $semester = null): FacultyBorrowRequest
    {
        return FacultyBorrowRequest::create([
            'borrowing_department_id' => $this->borrowingDean->department_id,
            'lending_department_id'   => $this->borrowed->department_id,
            'teacher_id'              => $this->borrowed->id,
            'academic_year'           => $this->term->academic_year,
            'semester'                => $semester ?? $this->term->semester,
            'status'                  => $status,
            'requested_by'            => $this->borrowingDean->id,
        ]);
    }

    private function subject(string $code): Subject
    {
        return Subject::create([
            'code' => $code, 'name' => "Subject {$code}", 'units' => 3, 'status' => 'approved',
            'program_id' => $this->term->section->program_id, 'has_laboratory' => false,
            'year_level' => $this->term->section->year_level, 'semester' => $this->term->semester,
        ]);
    }

    private function assign(Subject $subject, User $teacher)
    {
        return $this->actingAs($this->borrowingDean)->post(route('dean.assignments.store'), [
            'section_id' => $this->term->section_id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id,
        ]);
    }

    public function test_dean_can_request_a_teacher_of_another_department(): void
    {
        $this->actingAs($this->borrowingDean)
            ->post(route('dean.borrowing.store'), ['teacher_id' => $this->borrowed->id, 'message' => 'Needed for IT 301'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('faculty_borrow_requests', [
            'borrowing_department_id' => $this->borrowingDean->department_id,
            'lending_department_id'   => $this->borrowed->department_id,
            'teacher_id'              => $this->borrowed->id,
            'academic_year'           => $this->term->academic_year,
            'semester'                => $this->term->semester,
            'status'                  => 'pending',
        ]);
    }

    public function test_own_department_teacher_is_refused(): void
    {
        $this->actingAs($this->borrowingDean)
            ->post(route('dean.borrowing.store'), ['teacher_id' => $this->own->id])
            ->assertSessionHas('error');

        $this->assertSame(0, FacultyBorrowRequest::count());
    }

    public function test_a_second_open_request_for_the_same_teacher_and_semester_is_refused(): void
    {
        $this->loan('pending');

        $this->actingAs($this->borrowingDean)
            ->post(route('dean.borrowing.store'), ['teacher_id' => $this->borrowed->id])
            ->assertSessionHas('error');

        $this->assertSame(1, FacultyBorrowRequest::count());
    }

    public function test_request_needs_an_active_academic_period(): void
    {
        AcademicPeriod::query()->update(['is_active' => false]);

        $this->actingAs($this->borrowingDean)
            ->post(route('dean.borrowing.store'), ['teacher_id' => $this->borrowed->id])
            ->assertSessionHas('error');

        $this->assertSame(0, FacultyBorrowRequest::count());
    }

    public function test_lending_dean_can_approve_and_decline(): void
    {
        $approved = $this->loan('pending');
        $this->actingAs($this->lendingDean)->patch(route('dean.borrowing.approve', $approved))->assertSessionHas('success');
        $this->assertSame('approved', $approved->fresh()->status);
        $this->assertSame($this->lendingDean->id, $approved->fresh()->decided_by);

        $declined = $this->loan('pending', 'Another Semester');
        $this->actingAs($this->lendingDean)->patch(route('dean.borrowing.decline', $declined), ['decision_note' => 'Full load'])->assertSessionHas('success');
        $this->assertSame('declined', $declined->fresh()->status);
        $this->assertSame('Full load', $declined->fresh()->decision_note);
    }

    public function test_only_the_lending_dean_can_decide(): void
    {
        $loan = $this->loan('pending');

        $this->actingAs($this->borrowingDean)->patch(route('dean.borrowing.approve', $loan))->assertForbidden();
        $this->actingAs($this->borrowingDean)->patch(route('dean.borrowing.decline', $loan))->assertForbidden();
        $this->assertSame('pending', $loan->fresh()->status);
    }

    public function test_an_answered_request_cannot_be_answered_again(): void
    {
        $loan = $this->loan('declined');

        $this->actingAs($this->lendingDean)->patch(route('dean.borrowing.approve', $loan))->assertSessionHas('error');
        $this->assertSame('declined', $loan->fresh()->status);
    }

    public function test_borrowing_dean_can_cancel_only_a_pending_request(): void
    {
        $pending = $this->loan('pending');
        $this->actingAs($this->borrowingDean)->patch(route('dean.borrowing.cancel', $pending))->assertSessionHas('success');
        $this->assertSame('cancelled', $pending->fresh()->status);

        $approved = $this->loan('approved');
        $this->actingAs($this->borrowingDean)->patch(route('dean.borrowing.cancel', $approved))->assertSessionHas('error');
        $this->assertSame('approved', $approved->fresh()->status);

        $this->actingAs($this->lendingDean)->patch(route('dean.borrowing.cancel', $this->loan('pending', 'Other')))->assertForbidden();
    }

    public function test_borrowed_teacher_is_in_the_picker_only_after_approval(): void
    {
        $this->assertFalse(FacultyBorrowing::teachersFor($this->borrowingDean->department_id)->contains('id', $this->borrowed->id));

        $loan = $this->loan('pending');
        $this->assertFalse(FacultyBorrowing::teachersFor($this->borrowingDean->department_id)->contains('id', $this->borrowed->id));

        $loan->update(['status' => 'approved']);
        $teachers = FacultyBorrowing::teachersFor($this->borrowingDean->department_id);
        $this->assertTrue($teachers->contains('id', $this->borrowed->id));
        $this->assertTrue($teachers->contains('id', $this->own->id));
        $this->assertNotNull($teachers->firstWhere('id', $this->borrowed->id)->borrowed_from);
        $this->assertNull($teachers->firstWhere('id', $this->own->id)->borrowed_from);
    }

    public function test_a_loan_for_another_semester_does_not_apply(): void
    {
        $this->loan('approved', 'Some Other Semester');

        $this->assertFalse(FacultyBorrowing::teachersFor($this->borrowingDean->department_id)->contains('id', $this->borrowed->id));
    }

    public function test_assigning_a_teacher_of_another_department_is_refused_until_approved(): void
    {
        $subject = $this->subject('BRW101');

        $this->assign($subject, $this->borrowed)->assertSessionHas('error');
        $this->assertDatabaseMissing('section_subject_teachers', ['subject_id' => $subject->id]);

        $this->loan('pending');
        $this->assign($subject, $this->borrowed)->assertSessionHas('error');

        FacultyBorrowRequest::query()->update(['status' => 'approved']);
        $this->assign($subject, $this->borrowed)->assertSessionHas('success');
        $this->assertDatabaseHas('section_subject_teachers', [
            'section_term_id' => $this->term->id, 'subject_id' => $subject->id, 'teacher_id' => $this->borrowed->id,
        ]);
    }

    public function test_a_loan_for_another_semester_does_not_allow_assigning(): void
    {
        $this->loan('approved', 'Some Other Semester');

        $this->assign($this->subject('BRW102'), $this->borrowed)->assertSessionHas('error');
    }

    public function test_the_lending_department_still_assigns_its_own_teacher_in_its_own_department(): void
    {
        $this->assign($this->subject('BRW103'), $this->own)->assertSessionHas('success');
    }

    public function test_revoke_is_blocked_while_the_teacher_has_classes_and_allowed_after(): void
    {
        $loan    = $this->loan('approved');
        $subject = $this->subject('BRW104');
        $this->assign($subject, $this->borrowed)->assertSessionHas('success');

        $this->actingAs($this->lendingDean)->patch(route('dean.borrowing.revoke', $loan))->assertSessionHas('error');
        $this->assertSame('approved', $loan->fresh()->status);

        $this->term->subjects()->detach($subject->id);

        $this->actingAs($this->lendingDean)->patch(route('dean.borrowing.revoke', $loan))->assertSessionHas('success');
        $this->assertSame('revoked', $loan->fresh()->status);
        $this->assertFalse(FacultyBorrowing::teachersFor($this->borrowingDean->department_id)->contains('id', $this->borrowed->id));
    }

    public function test_only_the_lending_dean_can_revoke(): void
    {
        $loan = $this->loan('approved');

        $this->actingAs($this->borrowingDean)->patch(route('dean.borrowing.revoke', $loan))->assertForbidden();
        $this->assertSame('approved', $loan->fresh()->status);
    }

    public function test_page_lists_requests_for_both_deans(): void
    {
        $this->loan('pending');

        $this->actingAs($this->lendingDean)->get(route('dean.borrowing.index'))
            ->assertOk()->assertSee('Requests to your department')->assertSee($this->borrowed->name)->assertSee('Approve');

        $this->actingAs($this->borrowingDean)->get(route('dean.borrowing.index'))
            ->assertOk()->assertSee('Cancel request')->assertSee($this->borrowed->name);
    }

    public function test_assignment_page_labels_borrowed_teachers_and_hides_them_from_advisers(): void
    {
        $this->loan('approved');

        $this->actingAs($this->borrowingDean)->get(route('dean.assignments.index'))
            ->assertOk()->assertSee($this->borrowed->name . ' (borrowed from');

        $this->actingAs($this->borrowingDean)->get(route('dean.sections.index'))
            ->assertOk()->assertDontSee($this->borrowed->name . '</option>', false);
    }

    public function test_sidebar_badge_counts_requests_waiting_for_the_lending_dean_only(): void
    {
        $this->loan('pending');

        $this->actingAs($this->lendingDean)->get(route('dean.borrowing.index'))
            ->assertOk()->assertSee('Borrowed Faculty')->assertSee('data-nav-badge', false);

        $this->actingAs($this->borrowingDean)->get(route('dean.borrowing.index'))
            ->assertOk()->assertDontSee('data-nav-badge', false);
    }
}
