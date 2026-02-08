<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->onDelete('cascade');
            $table->decimal('quiz_score', 5, 2)->default(0); // Weighted score
            $table->decimal('exam_score', 5, 2)->default(0);
            $table->decimal('project_score', 5, 2)->default(0);
            $table->decimal('assessment_score', 5, 2)->default(0);
            $table->decimal('attendance_score', 5, 2)->default(0);
            $table->decimal('final_grade', 5, 2); // Sum of all weighted scores
            $table->decimal('numerical_grade', 3, 2)->nullable(); // Philippine 1.00-5.00 scale
            $table->string('letter_grade', 10)->nullable(); // e.g., "1.25", "3.00"
            $table->enum('remarks', ['passed', 'failed', 'incomplete'])->default('passed');
            $table->boolean('is_locked')->default(false);
            $table->foreignId('computed_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // One final grade per enrollment
            $table->unique('enrollment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_grades');
    }
};
