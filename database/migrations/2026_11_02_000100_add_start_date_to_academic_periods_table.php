<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The first day of a semester. With the midterm and finals cutoffs it bounds the dates a teacher
 * can record attendance and grade items for. Existing periods keep it empty, and recording stays
 * closed for their classes until the Administrator fills it in (Academic Periods, "Edit dates").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_periods', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('semester');
        });
    }

    public function down(): void
    {
        Schema::table('academic_periods', function (Blueprint $table) {
            $table->dropColumn('start_date');
        });
    }
};
