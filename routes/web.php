<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboard;
use App\Http\Controllers\SuperAdmin\TeacherApprovalController as AdminTeacherApproval;
use App\Http\Controllers\SuperAdmin\StudentController as AdminStudent;
use App\Http\Controllers\SuperAdmin\SubjectController as AdminSubject;
use App\Http\Controllers\Dean\DashboardController as DeanDashboard;
use App\Http\Controllers\Dean\TeacherApprovalController as DeanTeacherApproval;
use App\Http\Controllers\Dean\SectionController as DeanSection;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboard;
use App\Http\Controllers\Dean\EnrollmentController as DeanEnrollment;
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

    // Teacher approval routes
    Route::get('/teachers/pending', [AdminTeacherApproval::class, 'index'])->name('teachers.pending');
    Route::post('/teachers/{user}/approve', [AdminTeacherApproval::class, 'approve'])->name('teachers.approve');
    Route::delete('/teachers/{user}/reject', [AdminTeacherApproval::class, 'reject'])->name('teachers.reject');

    // Student routes
    Route::resource('students', AdminStudent::class);

    // Subject routes
    Route::resource('subjects', AdminSubject::class);
});

/*
|--------------------------------------------------------------------------
| Dean Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'status', 'role:dean'])->prefix('dean')->name('dean.')->group(function () {
    Route::get('/dashboard', [DeanDashboard::class, 'index'])->name('dashboard');

    // Teacher approval routes
    Route::get('/teachers/pending', [DeanTeacherApproval::class, 'index'])->name('teachers.pending');
    Route::post('/teachers/{user}/approve', [DeanTeacherApproval::class, 'approve'])->name('teachers.approve');
    Route::delete('/teachers/{user}/reject', [DeanTeacherApproval::class, 'reject'])->name('teachers.reject');

    // Section routes
    Route::resource('sections', DeanSection::class);

    // Enrollment routes
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
