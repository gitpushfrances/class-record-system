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
            $table->decimal('midterm_percentage', 5, 2)->default(0);
            $table->decimal('midterm_numerical', 3, 2)->nullable();
            $table->decimal('final_percentage', 5, 2)->default(0);
            $table->decimal('final_numerical', 3, 2)->nullable();
            $table->decimal('average_numerical', 3, 2)->nullable();
            $table->decimal('final_grade', 5, 2)->default(0);
            $table->decimal('numerical_grade', 3, 2)->nullable();
            $table->string('letter_grade', 10)->nullable();
            $table->enum('remarks', ['passed', 'failed', 'incomplete'])->default('passed');
            $table->boolean('is_locked')->default(false);
            $table->foreignId('computed_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('enrollment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_grades');
    }
};
