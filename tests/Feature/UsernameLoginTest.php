<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Panel recommendation: sign in with a username, not a Gmail/email address.
 */
class UsernameLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_sign_in_page_asks_for_a_username_and_has_no_forgot_password_link(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('name="username"', false)
            ->assertDontSee('name="email"', false)
            ->assertDontSee('Forgot password?')
            ->assertSee('Ask your administrator to reset it');
    }

    public function test_password_reset_by_email_is_gone(): void
    {
        foreach (['password.request', 'password.email', 'password.reset', 'password.store'] as $name) {
            $this->assertFalse(Route::has($name), "{$name} should be removed");
        }
        $this->get('/forgot-password')->assertNotFound();
    }

    public function test_usernames_are_built_from_the_email_and_made_unique(): void
    {
        $this->assertSame('juan.cruz', User::uniqueUsername('Juan.Cruz@essu.edu.ph'));
        $this->assertSame('user', User::uniqueUsername('x@essu.edu.ph'));

        User::factory()->create(['username' => 'juan.cruz']);
        User::factory()->create(['username' => 'juan.cruz2']);

        $this->assertSame('juan.cruz3', User::uniqueUsername('juan.cruz@essu.edu.ph'));
    }

    public function test_an_account_created_without_a_username_gets_one(): void
    {
        $user = User::factory()->create(['email' => 'maria.santos@essu.edu.ph']);

        $this->assertSame('maria.santos', $user->username);
    }

    public function test_the_seeded_accounts_sign_in_with_their_username(): void
    {
        ob_start();
        $this->seed();
        ob_end_clean();

        $this->post('/login', ['username' => 'dean', 'password' => 'password'])->assertRedirect(route('dean.dashboard'));
    }

    public function test_admin_creates_an_account_with_a_username_and_no_email_and_resets_its_password(): void
    {
        ob_start();
        $this->seed();
        ob_end_clean();

        $admin = User::where('role', 'super_admin')->firstOrFail();
        $ccs   = Department::where('code', 'CCS')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'last_name' => 'Reyes', 'first_name' => 'Ana', 'username' => 'ana.reyes',
            'password' => 'password1', 'password_confirmation' => 'password1',
            'role' => 'teacher', 'department_id' => $ccs->id,
        ])->assertRedirect(route('admin.deans.index'));

        $teacher = User::where('username', 'ana.reyes')->firstOrFail();
        $this->assertNull($teacher->email);
        $this->post('/logout');

        $this->post('/login', ['username' => 'ana.reyes', 'password' => 'password1'])->assertRedirect(route('teacher.dashboard'));
        $this->post('/logout');

        $this->actingAs($admin)->put(route('admin.deans.update', $teacher), [
            'last_name' => 'Reyes', 'first_name' => 'Ana', 'username' => 'ana.reyes',
            'password' => 'newpass99', 'password_confirmation' => 'newpass99',
            'role' => 'teacher', 'department_id' => $ccs->id,
        ])->assertSessionHasNoErrors();
        $this->post('/logout');

        $this->post('/login', ['username' => 'ana.reyes', 'password' => 'newpass99'])->assertRedirect(route('teacher.dashboard'));
    }

    public function test_a_user_without_an_email_can_confirm_their_password(): void
    {
        $user = User::factory()->create(['email' => null, 'username' => 'no.email']);

        $this->actingAs($user)->post(route('password.confirm'), ['password' => 'password'])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('password.confirm'), ['password' => 'wrong'])->assertSessionHasErrors('password');
    }

    public function test_two_accounts_cannot_share_a_username(): void
    {
        ob_start();
        $this->seed();
        ob_end_clean();

        $admin = User::where('role', 'super_admin')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'last_name' => 'Dup', 'first_name' => 'Dean', 'username' => 'DEAN',
            'password' => 'password1', 'password_confirmation' => 'password1', 'role' => 'teacher',
        ])->assertSessionHasErrors('username');
    }

    public function test_profile_shows_and_changes_the_username(): void
    {
        $user = User::factory()->create(['username' => 'old.name']);

        $this->actingAs($user)->get('/profile')->assertOk()->assertSee('value="old.name"', false);

        $this->actingAs($user)->patch('/profile', [
            'last_name' => 'User', 'first_name' => 'Test', 'username' => 'new.name', 'email' => '',
        ])->assertSessionHasNoErrors();

        $this->assertSame('new.name', $user->refresh()->username);
        $this->assertNull($user->email);
    }
}
