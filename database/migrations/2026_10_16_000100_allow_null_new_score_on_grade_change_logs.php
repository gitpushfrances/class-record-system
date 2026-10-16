<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE grade_change_logs MODIFY new_score DECIMAL(8,2) NULL');
    }

    public function down(): void
    {
        DB::table('grade_change_logs')->whereNull('new_score')->delete();
        DB::statement('ALTER TABLE grade_change_logs MODIFY new_score DECIMAL(8,2) NOT NULL');
    }
};
