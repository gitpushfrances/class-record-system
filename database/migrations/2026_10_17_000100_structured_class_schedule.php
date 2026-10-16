<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('section_subject_teachers', function (Blueprint $table) {
            $table->string('schedule_days', 20)->nullable()->after('teacher_id');
            $table->time('schedule_start')->nullable()->after('schedule_days');
            $table->time('schedule_end')->nullable()->after('schedule_start');
        });

        Schema::table('section_subject_teachers', function (Blueprint $table) {
            $table->dropColumn('schedule');
        });
    }

    public function down(): void
    {
        Schema::table('section_subject_teachers', function (Blueprint $table) {
            $table->string('schedule', 100)->nullable()->after('teacher_id');
        });

        Schema::table('section_subject_teachers', function (Blueprint $table) {
            $table->dropColumn(['schedule_days', 'schedule_start', 'schedule_end']);
        });
    }
};
