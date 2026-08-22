<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboard;
use App\Http\Controllers\SuperAdmin\UserController as AdminUser;
use App\Http\Controllers\SuperAdmin\AcademicPeriodController as AdminAcademic;
use App\Http\Controllers\Dean\StudentController as DeanStudent;
use App\Http\Controllers\SuperAdmin\SubjectController as AdminSubject;
use App\Http\Controllers\Dean\SubjectController as DeanSubject;
use App\Http\Controllers\Dean\DashboardController as DeanDashboard;
use App\Http\Controllers\Dean\TeacherApprovalController as DeanTeacherApproval;
use App\Http\Controllers\Dean\SectionController as DeanSection;
use App\Http\Controllers\Dean\EnrollmentController as DeanEnrollment;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboard;
use App\Http\Controllers\Teacher\ClassController;
use App\Http\Controllers\Teacher\GradeController;
use App\Http\Controllers\Teacher\AttendanceController;
use App\Http\Controllers\ProgramHead\ProgramHeadController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdmin\BackupController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->role === 'super_admin') return redirect()->route('admin.dashboard');
        if ($user->role === 'dean')        return redirect()->route('dean.dashboard');
        if ($user->role === 'teacher')     return redirect()->route('teacher.dashboard');
        if ($user->role === 'program_head') return redirect()->route('program-head.dashboard');
    }
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Super Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'status', 'role:super_admin', 'no.cache'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [SuperAdminDashboard::class, 'index'])->name('dashboard');

    Route::get('/subjects', [AdminSubject::class, 'index'])->name('subjects.index');
    Route::post('/subjects/{subject}/approve', [AdminSubject::class, 'approve'])->name('subjects.approve');
    Route::post('/subjects/{subject}/reject', [AdminSubject::class, 'reject'])->name('subjects.reject');

    Route::resource('deans', AdminUser::class)->except(['show', 'destroy'])->parameters(['deans' => 'dean']);

    Route::get('/academic', [AdminAcademic::class, 'index'])->name('academic.index');
    Route::post('/academic', [AdminAcademic::class, 'store'])->name('academic.store');
    Route::patch('/academic/{period}/set-active', [AdminAcademic::class, 'setActive'])->name('academic.setActive');
    Route::delete('/academic/{period}', [AdminAcademic::class, 'destroy'])->name('academic.destroy');
    Route::get('/users/create', [AdminUser::class, 'createAccount'])->name('users.create');
    Route::post('/users', [AdminUser::class, 'storeAccount'])->name('users.store');
    Route::post('/deans/{dean}/approve-request', [AdminUser::class, 'approveRequest'])->name('deans.approve-request');
    Route::post('/deans/{dean}/reject-request', [AdminUser::class, 'rejectRequest'])->name('deans.reject-request');

    Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
    Route::post('/backup/run', [BackupController::class, 'run'])->name('backup.run');
    Route::get('/backup/download/{filename}', [BackupController::class, 'download'])->name('backup.download');
    Route::delete('/backup/{filename}', [BackupController::class, 'delete'])->name('backup.delete');
    Route::post('/backup/restore', [BackupController::class, 'restore'])->name('backup.restore');

    Route::patch('/deans/{dean}/deactivate', [AdminUser::class, 'deactivate'])->name('deans.deactivate');
    Route::patch('/deans/{dean}/activate', [AdminUser::class, 'activate'])->name('deans.activate');
});

/*
|--------------------------------------------------------------------------
| Dean Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'status', 'role:dean', 'no.cache'])->prefix('dean')->name('dean.')->group(function () {
    Route::get('/dashboard', [DeanDashboard::class, 'index'])->name('dashboard');

    Route::get('/teachers/pending', [DeanTeacherApproval::class, 'index'])->name('teachers.pending');
    Route::post('/teachers/{user}/approve', [DeanTeacherApproval::class, 'approve'])->name('teachers.approve');
    Route::delete('/teachers/{user}/reject', [DeanTeacherApproval::class, 'reject'])->name('teachers.reject');

    Route::resource('sections', DeanSection::class);
    Route::post('/sections/{section}/change-adviser', [DeanSection::class, 'changeAdviser'])->name('sections.change-adviser');
    Route::post('/sections/{section}/attach-subject', [DeanSection::class, 'attachSubject'])->name('sections.attach-subject');
    Route::post('/sections/{section}/change-subject-teacher', [DeanSection::class, 'changeSubjectTeacher'])->name('sections.change-subject-teacher');

    Route::resource('students', DeanStudent::class)->except(['show']);
    Route::get('/subjects', [DeanSubject::class, 'index'])->name('subjects.index');
    Route::post('/subjects/{subject}/approve', [DeanSubject::class, 'approve'])->name('subjects.approve');
    Route::post('/subjects/{subject}/reject', [DeanSubject::class, 'reject'])->name('subjects.reject');

    Route::get('/assignments', [App\Http\Controllers\Dean\AssignmentController::class, 'index'])->name('assignments.index');
    Route::post('/assignments', [App\Http\Controllers\Dean\AssignmentController::class, 'store'])->name('assignments.store');
    Route::delete('/assignments/{id}', [App\Http\Controllers\Dean\AssignmentController::class, 'destroy'])->name('assignments.destroy');

    Route::get('/enrollments', [DeanEnrollment::class, 'index'])->name('enrollments.index');
    Route::get('/section-terms/{sectionTerm}/enrollments', [DeanEnrollment::class, 'show'])->name('enrollments.show');
    Route::post('/section-terms/{sectionTerm}/enrollments', [DeanEnrollment::class, 'store'])->name('enrollments.store');
    Route::delete('/section-terms/{sectionTerm}/enrollments/{enrollment}', [DeanEnrollment::class, 'destroy'])->name('enrollments.destroy');

    Route::get('/program-heads', [App\Http\Controllers\Dean\ProgramAssignmentController::class, 'index'])->name('program-heads.index');
    Route::post('/program-heads/{programHead}/assign', [App\Http\Controllers\Dean\ProgramAssignmentController::class, 'assign'])->name('program-heads.assign');
    Route::delete('/program-heads/{programHead}/remove', [App\Http\Controllers\Dean\ProgramAssignmentController::class, 'remove'])->name('program-heads.remove');
});

/*
|--------------------------------------------------------------------------
| Teacher Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'status', 'role:teacher', 'no.cache'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherDashboard::class, 'index'])->name('dashboard');
    Route::get('/advisory', [TeacherDashboard::class, 'advisory'])->name('advisory');
    Route::get('/teaching', [TeacherDashboard::class, 'teaching'])->name('teaching');

    // Class overview
    Route::get('/classes/{section}', [ClassController::class, 'show'])->name('classes.show');
Route::post('/classes/{section}/enroll', [ClassController::class, 'enrollStudent'])->name('classes.enroll');
Route::delete('/classes/{section}/enroll/{enrollment}', [ClassController::class, 'unenrollStudent'])->name('classes.unenroll');

    // Grade configuration
    Route::get('/classes/{section}/subjects/{subject}/grade-config', [GradeController::class, 'config'])->name('grades.config');
    Route::post('/classes/{section}/subjects/{subject}/grade-config', [GradeController::class, 'storeConfig'])->name('grades.config.store');

    // Grade items
    Route::get('/classes/{section}/subjects/{subject}/grade-items', [GradeController::class, 'items'])->name('grades.items');
    Route::post('/classes/{section}/subjects/{subject}/grade-items', [GradeController::class, 'storeItem'])->name('grades.items.store');
    Route::delete('/classes/{section}/subjects/{subject}/grade-items/{gradeItem}', [GradeController::class, 'destroyItem'])->name('grades.items.destroy');

    // Score entry
    Route::get('/classes/{section}/subjects/{subject}/grade-items/{gradeItem}/scores', [GradeController::class, 'scores'])->name('grades.scores');
    Route::post('/classes/{section}/subjects/{subject}/grade-items/{gradeItem}/scores', [GradeController::class, 'storeScores'])->name('grades.scores.store');

    // Final grades
    Route::get('/classes/{section}/subjects/{subject}/final-grades', [GradeController::class, 'finalGrades'])->name('grades.final');
    Route::post('/classes/{section}/subjects/{subject}/final-grades/submit', [GradeController::class, 'submitForVerification'])->name('grades.submit');


    // Attendance
    Route::get('/classes/{section}/subjects/{subject}/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/classes/{section}/subjects/{subject}/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/classes/{section}/subjects/{subject}/attendance/summary', [AttendanceController::class, 'summary'])->name('attendance.summary');

    Route::get('/classes/{section}/subjects/{subject}/record', [ClassController::class, 'record'])->name('classes.record');
    Route::get('/classes/{section}/subjects/{subject}/record/print', [ClassController::class, 'record'])->name('classes.record.print');
    Route::get('/classes/{section}/subjects/{subject}/record/export', [ClassController::class, 'export'])->name('classes.record.export');
});

/*
|--------------------------------------------------------------------------
| Program Head Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'status', 'role:program_head', 'no.cache'])->prefix('program-head')->name('program-head.')->group(function () {
    Route::get('/dashboard', [ProgramHeadController::class, 'dashboard'])->name('dashboard');
    Route::post('/verify/{sectionTerm}/{subject}', [ProgramHeadController::class, 'verify'])->name('verify');
    Route::post('/reject/{sectionTerm}/{subject}', [ProgramHeadController::class, 'reject'])->name('reject');
    Route::delete('/unverify/{sectionTerm}/{subject}', [ProgramHeadController::class, 'unverify'])->name('unverify');

    Route::get('/students', [App\Http\Controllers\ProgramHead\StudentController::class, 'index'])->name('students.index');
    Route::get('/students/create', [App\Http\Controllers\ProgramHead\StudentController::class, 'create'])->name('students.create');
    Route::post('/students', [App\Http\Controllers\ProgramHead\StudentController::class, 'store'])->name('students.store');
    Route::get('/students/{student}/edit', [App\Http\Controllers\ProgramHead\StudentController::class, 'edit'])->name('students.edit');
    Route::put('/students/{student}', [App\Http\Controllers\ProgramHead\StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{student}', [App\Http\Controllers\ProgramHead\StudentController::class, 'destroy'])->name('students.destroy');

    Route::get('/subjects', [App\Http\Controllers\ProgramHead\SubjectController::class, 'index'])->name('subjects.index');
    Route::get('/subjects/create', [App\Http\Controllers\ProgramHead\SubjectController::class, 'create'])->name('subjects.create');
    Route::post('/subjects', [App\Http\Controllers\ProgramHead\SubjectController::class, 'store'])->name('subjects.store');
    Route::get('/subjects/{subject}/edit', [App\Http\Controllers\ProgramHead\SubjectController::class, 'edit'])->name('subjects.edit');
    Route::put('/subjects/{subject}', [App\Http\Controllers\ProgramHead\SubjectController::class, 'update'])->name('subjects.update');
    Route::delete('/subjects/{subject}', [App\Http\Controllers\ProgramHead\SubjectController::class, 'destroy'])->name('subjects.destroy');

    Route::get('/sections', [App\Http\Controllers\ProgramHead\SectionController::class, 'index'])->name('sections.index');
    Route::get('/sections/create', [App\Http\Controllers\ProgramHead\SectionController::class, 'create'])->name('sections.create');
    Route::post('/sections', [App\Http\Controllers\ProgramHead\SectionController::class, 'store'])->name('sections.store');
    Route::get('/sections/{section}', [App\Http\Controllers\ProgramHead\SectionController::class, 'show'])->name('sections.show');
    Route::get('/sections/{section}/edit', [App\Http\Controllers\ProgramHead\SectionController::class, 'edit'])->name('sections.edit');
    Route::put('/sections/{section}', [App\Http\Controllers\ProgramHead\SectionController::class, 'update'])->name('sections.update');
    Route::delete('/sections/{section}', [App\Http\Controllers\ProgramHead\SectionController::class, 'destroy'])->name('sections.destroy');
    Route::post('/sections/{section}/attach-subject', [App\Http\Controllers\ProgramHead\SectionController::class, 'attachSubject'])->name('sections.attach-subject');
    Route::post('/sections/{section}/change-subject-teacher', [App\Http\Controllers\ProgramHead\SectionController::class, 'changeSubjectTeacher'])->name('sections.change-subject-teacher');
    Route::post('/sections/{section}/change-adviser', [App\Http\Controllers\ProgramHead\SectionController::class, 'changeAdviser'])->name('sections.change-adviser');
});

/*
|--------------------------------------------------------------------------
| Profile Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
