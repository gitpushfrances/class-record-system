<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Programs can be deactivated (hidden from new use, records kept): adds "inactive" to programs.status. */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE programs MODIFY status ENUM('pending', 'approved', 'rejected', 'inactive') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::table('programs')->where('status', 'inactive')->update(['status' => 'approved']);
        DB::statement("ALTER TABLE programs MODIFY status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
    }
};
