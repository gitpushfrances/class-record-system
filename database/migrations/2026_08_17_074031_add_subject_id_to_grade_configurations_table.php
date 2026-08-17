<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_configurations', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable()->after('section_id')->constrained()->cascadeOnDelete();
            $table->unique(['section_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::table('grade_configurations', function (Blueprint $table) {
            $table->dropUnique(['section_id', 'subject_id']);
            $table->dropForeign(['subject_id']);
            $table->dropColumn('subject_id');
        });
    }
};
