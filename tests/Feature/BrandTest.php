<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sign_in_page_uses_the_essu_seal_and_palette(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('images/rog/essu-seal.png', false)
            ->assertSee('--essu-green', false)
            ->assertDontSee('fa-book-open', false);
    }

    public function test_app_pages_use_the_essu_seal_and_palette(): void
    {
        ob_start();
        $this->seed();
        ob_end_clean();

        $this->actingAs(User::where('email', 'teacher@classrecord.test')->firstOrFail())
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('images/rog/essu-seal.png', false)
            ->assertSee('#0a5b05', false)
            ->assertDontSee('#1c1814', false)
            ->assertDontSee('fa-book-open', false);
    }

    public function test_signed_in_pages_have_the_essu_background_and_sign_in_does_not(): void
    {
        ob_start();
        $this->seed();
        ob_end_clean();

        $this->actingAs(User::where('email', 'teacher@classrecord.test')->firstOrFail())
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('<body class="font-sans antialiased bg-stone-100 app-shell"', false)
            ->assertSee('body.app-shell main::before', false);

        auth()->logout();

        // The sign-in page keeps its own brand panel: the rules are scoped to body.app-shell.
        $this->get(route('login'))->assertOk()->assertDontSee('class="font-sans antialiased bg-stone-100 app-shell"', false);
    }
}
