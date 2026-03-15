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
use Illuminate\Support\Facades\Route;

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
    }
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Super Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'status', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [SuperAdminDashboard::class, 'index'])->name('dashboard');

    Route::get('/subjects', [AdminSubject::class, 'index'])->name('subjects.index');
    Route::post('/subjects/{subject}/approve', [AdminSubject::class, 'approve'])->name('subjects.approve');
    Route::post('/subjects/{subject}/reject', [AdminSubject::class, 'reject'])->name('subjects.reject');

    Route::resource('deans', AdminUser::class)->except(['show', 'destroy'])->parameters(['deans' => 'dean']);

    Route::get('/academic', [AdminAcademic::class, 'index'])->name('academic.index');
    Route::post('/academic', [AdminAcademic::class, 'store'])->name('academic.store');
    Route::patch('/academic/{period}/set-active', [AdminAcademic::class, 'setActive'])->name('academic.setActive');
    Route::delete('/academic/{period}', [AdminAcademic::class, 'destroy'])->name('academic.destroy');
    Route::patch('/deans/{dean}/deactivate', [AdminUser::class, 'deactivate'])->name('deans.deactivate');
    Route::patch('/deans/{dean}/activate', [AdminUser::class, 'activate'])->name('deans.activate');
});

/*
|--------------------------------------------------------------------------
| Dean Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'status', 'role:dean'])->prefix('dean')->name('dean.')->group(function () {
    Route::get('/dashboard', [DeanDashboard::class, 'index'])->name('dashboard');

    Route::get('/teachers/pending', [DeanTeacherApproval::class, 'index'])->name('teachers.pending');
    Route::post('/teachers/{user}/approve', [DeanTeacherApproval::class, 'approve'])->name('teachers.approve');
    Route::delete('/teachers/{user}/reject', [DeanTeacherApproval::class, 'reject'])->name('teachers.reject');

    Route::resource('sections', DeanSection::class);
    Route::post('/sections/{section}/change-adviser', [DeanSection::class, 'changeAdviser'])->name('sections.change-adviser');

    Route::resource('students', DeanStudent::class);
    Route::resource('subjects', DeanSubject::class);

    Route::get('/enrollments', [DeanEnrollment::class, 'index'])->name('enrollments.index');
    Route::get('/sections/{section}/enrollments', [DeanEnrollment::class, 'show'])->name('enrollments.show');
    Route::post('/sections/{section}/enrollments', [DeanEnrollment::class, 'store'])->name('enrollments.store');
    Route::delete('/sections/{section}/enrollments/{enrollment}', [DeanEnrollment::class, 'destroy'])->name('enrollments.destroy');
});

/*
|--------------------------------------------------------------------------
| Teacher Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'status', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherDashboard::class, 'index'])->name('dashboard');

    // Class overview
    Route::get('/classes/{section}', [ClassController::class, 'show'])->name('classes.show');

    // Grade configuration
    Route::get('/classes/{section}/grade-config', [GradeController::class, 'config'])->name('grades.config');
    Route::post('/classes/{section}/grade-config', [GradeController::class, 'storeConfig'])->name('grades.config.store');

    // Grade items
    Route::get('/classes/{section}/grade-items', [GradeController::class, 'items'])->name('grades.items');
    Route::post('/classes/{section}/grade-items', [GradeController::class, 'storeItem'])->name('grades.items.store');
    Route::delete('/classes/{section}/grade-items/{gradeItem}', [GradeController::class, 'destroyItem'])->name('grades.items.destroy');

    // Score entry
    Route::get('/classes/{section}/grade-items/{gradeItem}/scores', [GradeController::class, 'scores'])->name('grades.scores');
    Route::post('/classes/{section}/grade-items/{gradeItem}/scores', [GradeController::class, 'storeScores'])->name('grades.scores.store');

    // Final grades
    Route::get('/classes/{section}/final-grades', [GradeController::class, 'finalGrades'])->name('grades.final');
    Route::post('/classes/{section}/final-grades/compute', [GradeController::class, 'computeGrades'])->name('grades.final.compute');
    Route::post('/classes/{section}/final-grades/lock', [GradeController::class, 'lockGrades'])->name('grades.final.lock');

    // Attendance
    Route::get('/classes/{section}/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/classes/{section}/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/classes/{section}/attendance/summary', [AttendanceController::class, 'summary'])->name('attendance.summary');

    Route::get('/classes/{section}/record', [ClassController::class, 'record'])->name('classes.record');
    Route::get('/classes/{section}/record/print', [ClassController::class, 'record'])->name('classes.record.print');
    Route::get('/classes/{section}/record/export', [ClassController::class, 'export'])->name('classes.record.export');
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
