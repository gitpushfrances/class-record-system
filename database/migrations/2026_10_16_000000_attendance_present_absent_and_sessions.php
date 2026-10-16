<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('attendance_records')->whereIn('status', ['late', 'excused'])->update(['status' => 'present']);
        DB::statement("ALTER TABLE attendance_records MODIFY status ENUM('present','absent') NOT NULL DEFAULT 'present'");

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->enum('session_type', ['lecture', 'laboratory'])->default('lecture')->after('date');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->unique(['enrollment_id', 'subject_id', 'date', 'session_type'], 'att_enrollment_subject_date_session_unique');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropUnique(['enrollment_id', 'subject_id', 'date']);
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->boolean('has_laboratory')->default(false)->after('units');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('has_laboratory');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->unique(['enrollment_id', 'subject_id', 'date']);
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropUnique('att_enrollment_subject_date_session_unique');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn('session_type');
        });

        DB::statement("ALTER TABLE attendance_records MODIFY status ENUM('present','absent','late','excused') NOT NULL DEFAULT 'present'");
    }
};
