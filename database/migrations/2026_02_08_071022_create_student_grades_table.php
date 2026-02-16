<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->onDelete('cascade');
            $table->foreignId('grade_item_id')->constrained()->onDelete('cascade');
            $table->decimal('score', 8, 2); // e.g., 45.00
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
            
            // Prevent duplicate grades for same student on same item
            $table->unique(['enrollment_id', 'grade_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_grades');
    }
};