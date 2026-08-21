<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_periods', function (Blueprint $table) {
            $table->date('midterm_cutoff_date')->nullable()->after('semester');
            $table->date('finals_cutoff_date')->nullable()->after('midterm_cutoff_date');
        });

        Schema::table('section_terms', function (Blueprint $table) {
            $table->dropColumn('midterm_cutoff_date');
        });
    }

    public function down(): void
    {
        Schema::table('academic_periods', function (Blueprint $table) {
            $table->dropColumn(['midterm_cutoff_date', 'finals_cutoff_date']);
        });

        Schema::table('section_terms', function (Blueprint $table) {
            $table->date('midterm_cutoff_date')->nullable();
        });
    }
};
