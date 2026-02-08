<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboard;
use App\Http\Controllers\Dean\DashboardController as DeanDashboard;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboard;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Super Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'status', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [SuperAdminDashboard::class, 'index'])->name('dashboard');

    // Future routes:
    // Route::resource('deans', DeanController::class);
    // Route::resource('teachers', TeacherController::class);
    // Route::resource('students', StudentController::class);
    // Route::resource('subjects', SubjectController::class);
    // Route::resource('sections', SectionController::class);
});

/*
|--------------------------------------------------------------------------
| Dean Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'status', 'role:dean'])->prefix('dean')->name('dean.')->group(function () {
    Route::get('/dashboard', [DeanDashboard::class, 'index'])->name('dashboard');

    // Future routes:
    // Route::get('/teachers/pending', [TeacherApprovalController::class, 'index'])->name('teachers.pending');
    // Route::post('/teachers/{user}/approve', [TeacherApprovalController::class, 'approve'])->name('teachers.approve');
    // Route::post('/teachers/{user}/reject', [TeacherApprovalController::class, 'reject'])->name('teachers.reject');
});

/*
|--------------------------------------------------------------------------
| Teacher Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'status', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherDashboard::class, 'index'])->name('dashboard');

    // Future routes:
    // Route::get('/classes/{section}', [ClassController::class, 'show'])->name('classes.show');
    // Route::get('/classes/{section}/students', [ClassController::class, 'students'])->name('classes.students');
    // Route::get('/classes/{section}/attendance', [AttendanceController::class, 'index'])->name('classes.attendance');
    // Route::get('/classes/{section}/quizzes', [QuizController::class, 'index'])->name('classes.quizzes');
    // Route::get('/classes/{section}/exams', [ExamController::class, 'index'])->name('classes.exams');
    // Route::get('/classes/{section}/projects', [ProjectController::class, 'index'])->name('classes.projects');
    // Route::get('/classes/{section}/assessments', [AssessmentController::class, 'index'])->name('classes.assessments');
    // Route::get('/classes/{section}/final-grades', [FinalGradeController::class, 'index'])->name('classes.final-grades');
});

/*
|--------------------------------------------------------------------------
| Profile Routes (All Authenticated Users)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
