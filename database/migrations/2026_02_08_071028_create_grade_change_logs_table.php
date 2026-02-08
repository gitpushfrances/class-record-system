<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_grade_id')->constrained()->onDelete('cascade');
            $table->decimal('old_score', 8, 2);
            $table->decimal('new_score', 8, 2);
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_change_logs');
    }
};
