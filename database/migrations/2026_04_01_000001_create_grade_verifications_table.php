<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_term_id')->constrained('section_terms')->onDelete('cascade');
            $table->foreignId('verified_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('verified_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique('section_term_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_verifications');
    }
};
