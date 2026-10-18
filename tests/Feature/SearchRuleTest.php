<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchRuleTest extends TestCase
{
    use RefreshDatabase;

    private User $dean;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->seed();
        ob_end_clean();

        $this->dean = User::where('email', 'dean@classrecord.test')->firstOrFail();
    }

    private function search(string $q)
    {
        return $this->actingAs($this->dean)->get(route('dean.students.index', ['search' => $q]))->assertOk();
    }

    public function test_letters_match_the_start_of_a_name_not_the_email_domain(): void
    {
        // Every sample e-mail ends in "student.test"; only "Dela Cruz" starts with "de".
        $this->search('de')
            ->assertSee('<mark class="search-hit">De</mark>la Cruz', false)
            ->assertDontSee('2021-00002')
            ->assertDontSee('2021-00003')
            ->assertDontSee('2021-00004')
            ->assertDontSee('2021-00005');
    }

    public function test_student_number_and_several_words(): void
    {
        $this->search('00003')->assertSee('2021-<mark class="search-hit">00003</mark>', false)->assertDontSee('2021-00001');
        $this->search('santos maria')->assertSee('2021-00002')->assertDontSee('2021-00001');
    }

    public function test_email_is_searched_when_the_word_has_an_at_sign(): void
    {
        $this->search('maria.santos@')->assertSee('2021-00002')->assertDontSee('2021-00001');
    }

    public function test_program_head_and_faculty_use_the_same_rule(): void
    {
        $programHead = User::where('email', 'programhead@classrecord.test')->firstOrFail();
        $this->actingAs($programHead)->get(route('program-head.students.index', ['search' => 'de']))
            ->assertOk()->assertSee('2021-00001')->assertDontSee('2021-00002');

        $this->actingAs($this->dean)->get(route('dean.faculty.index', ['search' => 'sam']))
            ->assertOk()->assertSee('<mark class="search-hit">Sam</mark>ple', false);
        $this->actingAs($this->dean)->get(route('dean.faculty.index', ['search' => 'ample']))
            ->assertOk()->assertDontSee('Teacher Sample');
    }
}
