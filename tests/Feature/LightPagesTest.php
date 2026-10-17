<?php

namespace Tests\Feature;

use App\Models\SectionTerm;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LightPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();
    }

    public function test_former_dark_pages_render_as_light_cards(): void
    {
        $admin       = User::where('email', 'admin@classrecord.test')->firstOrFail();
        $programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();
        $teacher     = User::where('email', 'teacher@classrecord.test')->firstOrFail();
        $term        = SectionTerm::where('status', 'active')->whereHas('section.program', fn ($q) => $q->where('code', 'BSCS'))->firstOrFail();
        $subject     = Subject::firstOrFail();
        $term->subjects()->attach($subject->id, ['teacher_id' => $teacher->id]);

        $pages = [
            [$admin, route('admin.backup.index')],
            [$admin, route('admin.users.create')],
            [$programHead, route('program-head.dashboard')],
            [$teacher, route('teacher.grades.config', [$term->section, $subject])],
        ];

        // Only the page content is checked: the sidebar and mobile top bar are dark by design.
        foreach ($pages as [$user, $url]) {
            $html = $this->actingAs($user)->get($url)->assertOk()->getContent();
            $this->assertSame(1, preg_match('#<main\b.*?</main>#s', $html, $main), "$url has no <main> region");
            $content = str_replace(' ', '', $main[0]);
            $this->assertStringNotContainsString('background:#072a07', $content, $url);
            $this->assertStringNotContainsString('color:#fbf3d0', $content, $url);
        }
    }
}
