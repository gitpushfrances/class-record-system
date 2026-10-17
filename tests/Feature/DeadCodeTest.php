<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeadCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_removed_routes_and_table_are_gone(): void
    {
        foreach (['admin.deans.create', 'admin.deans.store', 'teacher.classes.record.print'] as $name) {
            $this->assertFalse(Route::has($name), "{$name} should be removed");
        }
        $this->assertTrue(Route::has('admin.users.create'));
        $this->assertFalse(Schema::hasTable('student_number_counters'));
    }

    public function test_home_redirect_reaches_the_role_dashboard(): void
    {
        ob_start();
        $this->seed();
        ob_end_clean();

        $this->assertSame('/', RouteServiceProvider::HOME);

        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $this->actingAs($teacher)->post(route('password.confirm'), ['password' => 'password'])->assertRedirect('/');
        $this->actingAs($teacher)->get('/')->assertRedirect(route('teacher.dashboard'));
    }
}
