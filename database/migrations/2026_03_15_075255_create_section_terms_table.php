<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->onDelete('cascade');
            $table->foreignId('adviser_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('academic_year');
            $table->enum('semester', ['1st Semester', '2nd Semester', 'Summer']);
            $table->enum('status', ['active', 'completed', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['section_id', 'academic_year', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_terms');
    }
};
