<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->enum('year_level', ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'])->nullable()->after('units');
            $table->enum('semester', ['1st Semester', '2nd Semester', 'Summer'])->nullable()->after('year_level');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['year_level', 'semester']);
        });
    }
};
