<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('academic_rank', 100)->nullable()->after('middle_name');
            $table->string('credentials', 100)->nullable()->after('academic_rank');
        });

        Schema::table('section_subject_teachers', function (Blueprint $table) {
            $table->string('schedule', 100)->nullable()->after('teacher_id');
        });
    }

    public function down(): void
    {
        Schema::table('section_subject_teachers', function (Blueprint $table) {
            $table->dropColumn('schedule');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['academic_rank', 'credentials']);
        });

        Schema::dropIfExists('settings');
    }
};
