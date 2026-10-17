<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The automatic student-number counter was never used (student numbers are entered or imported). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('student_number_counters');
    }

    public function down(): void
    {
        Schema::create('student_number_counters', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });
    }
};
