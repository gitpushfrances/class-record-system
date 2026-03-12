<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: convert status to varchar, update data, then set final enum
        DB::statement("ALTER TABLE subjects MODIFY COLUMN status VARCHAR(20) DEFAULT 'active'");
        DB::statement("UPDATE subjects SET status = 'approved' WHERE status IN ('active', 'inactive')");
        DB::statement("ALTER TABLE subjects MODIFY COLUMN status ENUM('pending','approved','rejected') DEFAULT 'pending'");

        // Step 2: add new columns
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('department')->nullable()->after('units');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete()->after('department');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('requested_by');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejected_reason')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['department', 'approved_at', 'rejected_reason']);
        });

        DB::statement("ALTER TABLE subjects MODIFY COLUMN status ENUM('active','inactive') DEFAULT 'active'");
    }
};
