<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Sign-in uses a username instead of the email address. Existing accounts get the part of their
 * email before the "@" (made unique); the email stays as an optional contact address.
 * MySQL: the column changes use raw statements, like the other MODIFY migrations here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable()->unique()->after('email');
        });

        $taken = [];
        foreach (DB::table('users')->orderBy('id')->get(['id', 'email', 'first_name', 'last_name']) as $user) {
            $seed = $user->email ?: trim("{$user->first_name}.{$user->last_name}", '.');
            $base = Str::of($seed)->before('@')->lower()->replaceMatches('/[^a-z0-9._-]+/', '')->limit(25, '')->toString();
            $base = strlen($base) >= 3 ? $base : 'user';

            $name = $base;
            for ($i = 2; isset($taken[$name]); $i++) {
                $name = $base . $i;
            }
            $taken[$name] = true;

            DB::table('users')->where('id', $user->id)->update(['username' => $name]);
        }

        DB::statement('ALTER TABLE users MODIFY username VARCHAR(30) NOT NULL');
        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::table('users')->whereNull('email')->update(['email' => DB::raw("CONCAT(username, '@no-email.invalid')")]);
        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
