<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('final_grades', function (Blueprint $table) {
            $table->dropForeign(['enrollment_id']);
            $table->dropUnique(['enrollment_id']);
        });

        Schema::table('final_grades', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable()->after('enrollment_id')
                ->constrained()->onDelete('cascade');
            $table->foreign('enrollment_id')->references('id')->on('enrollments')->onDelete('cascade');
            $table->unique(['enrollment_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::table('final_grades', function (Blueprint $table) {
            $table->dropUnique(['enrollment_id', 'subject_id']);
            $table->dropForeign(['enrollment_id']);
            $table->dropConstrainedForeignId('subject_id');
        });

        Schema::table('final_grades', function (Blueprint $table) {
            $table->foreign('enrollment_id')->references('id')->on('enrollments')->onDelete('cascade');
            $table->unique('enrollment_id');
        });
    }
};
