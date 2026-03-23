<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('subjects', function (Blueprint $table) {
        $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete()->after('department');
    });
}

public function down(): void
{
    Schema::table('subjects', function (Blueprint $table) {
        $table->dropForeignIdFor(\App\Models\User::class, 'teacher_id');
        $table->dropColumn('teacher_id');
    });
}
};
