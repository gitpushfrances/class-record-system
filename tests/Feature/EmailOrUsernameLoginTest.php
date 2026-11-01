<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The sign-in field takes the username or the email address. */
class EmailOrUsernameLoginTest extends TestCase
{
    use RefreshDatabase;

    private function teacher(array $overrides = []): User
    {
        return User::factory()->create($overrides + ['role' => 'teacher', 'status' => 'active', 'username' => 'juan.cruz', 'email' => 'juan.cruz@essu.edu.ph']);
    }

    public function test_the_sign_in_page_has_one_field_for_the_username_or_email(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Username or email')
            ->assertSee('name="login"', false)
            ->assertDontSee('name="username"', false)
            ->assertDontSee('name="email"', false);
    }

    public function test_the_email_signs_in_whatever_its_capitals_and_surrounding_spaces(): void
    {
        $user = $this->teacher();

        $this->post('/login', ['login' => '  Juan.Cruz@ESSU.edu.ph ', 'password' => 'password'])
            ->assertRedirect(route('teacher.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_username_still_signs_in(): void
    {
        $user = $this->teacher();

        $this->post('/login', ['login' => 'JUAN.cruz', 'password' => 'password'])->assertRedirect(route('teacher.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_fails_the_same_way_for_the_email_and_the_username(): void
    {
        $this->teacher();

        $this->post('/login', ['login' => 'juan.cruz@essu.edu.ph', 'password' => 'wrong'])
            ->assertSessionHasErrors(['login' => trans('auth.failed')]);
        $this->post('/login', ['login' => 'juan.cruz', 'password' => 'wrong'])
            ->assertSessionHasErrors(['login' => trans('auth.failed')]);

        $this->assertGuest();
    }

    public function test_an_unknown_email_gives_the_same_message_as_a_wrong_password(): void
    {
        $this->teacher();

        $this->post('/login', ['login' => 'nobody@essu.edu.ph', 'password' => 'password'])
            ->assertSessionHasErrors(['login' => trans('auth.failed')]);

        $this->assertGuest();
    }

    public function test_something_with_an_at_sign_is_never_looked_up_as_a_username(): void
    {
        $this->teacher(['email' => 'other@essu.edu.ph']);

        $this->post('/login', ['login' => 'juan.cruz@essu.edu.ph', 'password' => 'password'])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_an_account_without_an_email_signs_in_with_its_username_only(): void
    {
        $user = $this->teacher(['email' => null]);

        $this->post('/login', ['login' => 'juan.cruz', 'password' => 'password'])->assertRedirect(route('teacher.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_pending_account_cannot_sign_in_with_its_email(): void
    {
        User::factory()->create(['role' => null, 'status' => 'pending_review', 'email' => 'new.faculty@essu.edu.ph']);

        $this->post('/login', ['login' => 'new.faculty@essu.edu.ph', 'password' => 'password'])->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_too_many_failed_attempts_lock_the_login_whichever_way_it_was_typed(): void
    {
        $this->teacher();

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', ['login' => 'juan.cruz@essu.edu.ph', 'password' => 'wrong']);
        }

        // The right password is refused now: the lock counts the same account typed in any capitals.
        $this->post('/login', ['login' => ' JUAN.CRUZ@essu.edu.ph', 'password' => 'password'])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_emails_are_saved_in_lower_case(): void
    {
        $user = $this->teacher(['email' => 'Maria.Santos@ESSU.edu.ph']);

        $this->assertSame('maria.santos@essu.edu.ph', $user->fresh()->email);
    }

    public function test_the_forms_say_what_the_email_is_for(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('It can also be used to sign in')
            ->assertSee('The system sends no email');
    }
}
