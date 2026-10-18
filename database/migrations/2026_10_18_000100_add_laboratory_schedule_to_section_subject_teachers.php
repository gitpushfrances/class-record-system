<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Laboratory schedule of a subject with a laboratory, beside its class (lecture) schedule. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('section_subject_teachers', function (Blueprint $table) {
            $table->string('lab_days', 20)->nullable()->after('schedule_end');
            $table->time('lab_start')->nullable()->after('lab_days');
            $table->time('lab_end')->nullable()->after('lab_start');
        });
    }

    public function down(): void
    {
        Schema::table('section_subject_teachers', function (Blueprint $table) {
            $table->dropColumn(['lab_days', 'lab_start', 'lab_end']);
        });
    }
};
