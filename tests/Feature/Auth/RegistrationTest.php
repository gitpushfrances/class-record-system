<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function details(array $overrides = []): array
    {
        return array_merge([
            'last_name' => 'User',
            'first_name' => 'Test',
            'username' => 'test.user',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_without_an_email(): void
    {
        $response = $this->post('/register', $this->details());

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', [
            'username' => 'test.user',
            'email' => null,
            'name' => 'User, Test',
            'role' => null,
            'status' => 'pending_review',
        ]);
    }

    public function test_the_email_is_kept_when_given(): void
    {
        $this->post('/register', $this->details(['email' => 'test@example.com']))->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', ['username' => 'test.user', 'email' => 'test@example.com']);
    }

    public function test_a_username_must_be_free_and_well_formed(): void
    {
        User::factory()->create(['username' => 'taken.name']);

        $this->post('/register', $this->details(['username' => 'Taken.Name']))->assertSessionHasErrors('username');
        $this->post('/register', $this->details(['username' => 'ab']))->assertSessionHasErrors('username');
        $this->post('/register', $this->details(['username' => 'has space']))->assertSessionHasErrors('username');
        $this->post('/register', $this->details(['username' => '']))->assertSessionHasErrors('username');

        $this->assertDatabaseMissing('users', ['first_name' => 'Test']);
    }
}
