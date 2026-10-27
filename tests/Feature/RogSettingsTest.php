<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RogSettingsTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    }

    private function form(array $extra = []): array
    {
        return array_merge([
            'rog_registrar_name' => 'REGISTRAR NAME, DBA',
            'rog_form_code'      => 'ESSU-ACAD-712.b',
            'rog_form_version'   => 'Version 6',
            'rog_effectivity'    => 'June 1, 2026',
        ], $extra);
    }

    public function test_defaults_follow_the_official_form(): void
    {
        $settings = Setting::rog();

        $this->assertSame('ESSU-ACAD-712.b', $settings['rog_form_code']);
        $this->assertSame('Version 5', $settings['rog_form_version']);
        $this->assertSame('March 15, 2024', $settings['rog_effectivity']);
        $this->assertStringEndsWith('images/rog/essu-banner.png', Setting::rogLogoUrl('left'));
    }

    public function test_admin_can_open_the_settings_page(): void
    {
        $this->withoutVite()->actingAs($this->admin())->get(route('admin.rog.index'))->assertOk();
    }

    public function test_admin_can_save_settings(): void
    {
        $this->actingAs($this->admin())->put(route('admin.rog.update'), $this->form())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.rog.index'));

        $settings = Setting::rog();
        $this->assertSame('Version 6', $settings['rog_form_version']);
        $this->assertSame('REGISTRAR NAME, DBA', $settings['rog_registrar_name']);
    }

    public function test_uploaded_logo_replaces_the_default_and_can_be_reset(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $logo  = UploadedFile::fake()->createWithContent('logo.png', base64_decode(self::PNG));

        $this->actingAs($admin)->put(route('admin.rog.update'), $this->form(['rog_logo_left' => $logo]))
            ->assertSessionHasNoErrors();

        $path = Setting::rog()['rog_logo_left'];
        Storage::disk('public')->assertExists($path);
        $this->assertStringEndsWith('storage/' . $path, Setting::rogLogoUrl('left'));

        $this->actingAs($admin)->put(route('admin.rog.update'), $this->form(['reset_logo_left' => 1]))
            ->assertSessionHasNoErrors();

        $this->assertNull(Setting::rog()['rog_logo_left']);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);

        $this->actingAs($teacher)->get(route('admin.rog.index'))->assertForbidden();
        $this->actingAs($teacher)->put(route('admin.rog.update'), $this->form())->assertForbidden();
    }

    public function test_faculty_can_save_rank_and_credentials(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);

        $this->actingAs($teacher)->patch('/profile', [
            'last_name'     => 'User',
            'first_name'    => 'Test',
            'username'      => $teacher->username,
            'email'         => $teacher->email,
            'academic_rank' => 'Asst. Professor III',
            'credentials'   => 'MIT, PCpE',
        ])->assertSessionHasNoErrors();

        $teacher->refresh();
        $this->assertSame('Asst. Professor III', $teacher->academic_rank);
        $this->assertSame('MIT, PCpE', $teacher->credentials);
    }
}
