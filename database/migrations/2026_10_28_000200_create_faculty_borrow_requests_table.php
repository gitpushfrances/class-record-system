<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A Dean asks another department's Dean to lend a teacher for one semester.
 * An approved request lets the borrowing department assign that teacher to its subjects in that term only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_borrow_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrowing_department_id')->constrained('departments');
            $table->foreignId('lending_department_id')->constrained('departments');
            $table->foreignId('teacher_id')->constrained('users');
            $table->string('academic_year', 20);
            $table->string('semester', 30);
            $table->string('status', 20)->default('pending'); // pending, approved, declined, cancelled, revoked
            $table->text('message')->nullable();
            $table->text('decision_note')->nullable();
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['borrowing_department_id', 'academic_year', 'semester', 'status'], 'borrow_requests_borrower_term_idx');
            $table->index(['lending_department_id', 'status'], 'borrow_requests_lender_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_borrow_requests');
    }
};
