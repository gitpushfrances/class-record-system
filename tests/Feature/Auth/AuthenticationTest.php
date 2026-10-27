<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create(['role' => 'teacher', 'status' => 'active', 'username' => 'juan.cruz']);

        $response = $this->post('/login', [
            'username' => 'juan.cruz',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/teacher/dashboard');
    }

    public function test_username_is_not_case_sensitive(): void
    {
        $user = User::factory()->create(['role' => 'teacher', 'status' => 'active', 'username' => 'juan.cruz']);

        $this->post('/login', ['username' => '  Juan.Cruz ', 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_email_address_no_longer_signs_in(): void
    {
        $user = User::factory()->create(['role' => 'teacher', 'status' => 'active']);

        $this->post('/login', ['username' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_pending_users_can_not_sign_in(): void
    {
        $user = User::factory()->create(['role' => null, 'status' => 'pending_review']);

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
