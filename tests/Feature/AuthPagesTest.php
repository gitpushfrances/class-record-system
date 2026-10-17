<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sign_in_page_uses_the_split_layout_without_the_old_decorations(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Eastern Samar State University')
            ->assertSee('Faculty Class Record System', false)
            ->assertSee('data-toggle="password"', false)
            ->assertDontSee('lottie', false)
            ->assertDontSee('card-wm', false);
    }

    public function test_register_page_groups_the_fields(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSeeInOrder(['Your name', 'Last name', 'First name', 'Middle name', 'Account', 'Email address', 'Password', 'Confirm password'])
            ->assertSee('autocomplete="family-name"', false);
    }
}
