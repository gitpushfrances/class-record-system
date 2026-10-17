<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_section_term_id')->constrained('section_terms')->cascadeOnDelete();
            $table->foreignId('target_section_term_id')->constrained('section_terms')->cascadeOnDelete();
            $table->string('year_level', 20);
            $table->json('student_ids');
            $table->json('skipped_ids');
            $table->foreignId('promoted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
