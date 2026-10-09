<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique('subjects_code_unique');
            $table->index('code');
        });
    }

    // Rolling back fails if duplicate codes already exist, by design.
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropIndex(['code']);
            $table->unique('code');
        });
    }
};
