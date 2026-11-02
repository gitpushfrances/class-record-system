<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** People sign in with their username only. The email, when an account has one, is not a way to sign in. */
class UsernameOnlyLoginTest extends TestCase
{
    use RefreshDatabase;

    private function teacher(array $overrides = []): User
    {
        return User::factory()->create($overrides + ['role' => 'teacher', 'status' => 'active', 'username' => 'juan.cruz', 'email' => 'juan.cruz@essu.edu.ph']);
    }

    public function test_the_sign_in_page_asks_for_the_username_only(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('name="username"', false)
            ->assertDontSee('name="login"', false)
            ->assertDontSee('name="email"', false)
            ->assertDontSee('Username or email')
            ->assertDontSee('email address');
    }

    public function test_the_username_signs_in_whatever_its_capitals_and_surrounding_spaces(): void
    {
        $user = $this->teacher();

        $this->post('/login', ['username' => '  JUAN.cruz ', 'password' => 'password'])->assertRedirect(route('teacher.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_email_does_not_sign_in_even_with_the_right_password(): void
    {
        $this->teacher();

        $this->post('/login', ['username' => 'juan.cruz@essu.edu.ph', 'password' => 'password'])
            ->assertSessionHasErrors(['username' => trans('auth.failed')]);

        $this->assertGuest();
    }

    public function test_a_wrong_password_and_an_unknown_username_fail_the_same_way(): void
    {
        $this->teacher();

        $this->post('/login', ['username' => 'juan.cruz', 'password' => 'wrong'])
            ->assertSessionHasErrors(['username' => trans('auth.failed')]);
        $this->post('/login', ['username' => 'nobody', 'password' => 'password'])
            ->assertSessionHasErrors(['username' => trans('auth.failed')]);

        $this->assertGuest();
    }

    public function test_an_account_without_an_email_signs_in(): void
    {
        $user = $this->teacher(['email' => null]);

        $this->post('/login', ['username' => 'juan.cruz', 'password' => 'password'])->assertRedirect(route('teacher.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_pending_account_cannot_sign_in(): void
    {
        User::factory()->create(['role' => null, 'status' => 'pending_review', 'username' => 'new.faculty']);

        $this->post('/login', ['username' => 'new.faculty', 'password' => 'password'])->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_too_many_failed_attempts_lock_the_login_whichever_way_it_was_typed(): void
    {
        $this->teacher();

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', ['username' => 'juan.cruz', 'password' => 'wrong']);
        }

        // The right password is refused now: the lock counts the same account typed in other capitals and with spaces.
        $this->post('/login', ['username' => ' JUAN.CRUZ ', 'password' => 'password'])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_emails_are_saved_in_lower_case(): void
    {
        $user = $this->teacher(['email' => 'Maria.Santos@ESSU.edu.ph', 'username' => 'maria.santos']);

        $this->assertSame('maria.santos@essu.edu.ph', $user->fresh()->email);
    }

    public function test_the_forms_say_the_email_is_not_used_to_sign_in(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('It is not used to sign in')
            ->assertSee('The system sends no email')
            ->assertDontSee('can also be used to sign in');
    }
}
