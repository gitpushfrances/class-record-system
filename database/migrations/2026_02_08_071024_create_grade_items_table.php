<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->onDelete('cascade');
            $table->enum('component_type', ['quiz', 'exam', 'project', 'assessment']);
            $table->string('name'); // e.g., "Quiz 1", "Midterm Exam"
            $table->decimal('max_score', 8, 2); // e.g., 50.00
            $table->date('date_given')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_items');
    }
};
