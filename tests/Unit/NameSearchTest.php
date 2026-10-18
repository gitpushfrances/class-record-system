<?php

namespace Tests\Unit;

use App\Services\NameSearch;
use Tests\TestCase;

class NameSearchTest extends TestCase
{
    public function test_words_are_split_on_spaces_and_commas(): void
    {
        $this->assertSame(['dela', 'cruz', 'juan'], NameSearch::terms(' Dela  Cruz, Juan '));
        $this->assertSame([], NameSearch::terms('   '));
    }

    public function test_highlight_marks_only_the_start_of_words(): void
    {
        $this->assertSame('<mark class="search-hit">De</mark>la Cruz, Juan M.', (string) NameSearch::highlight('Dela Cruz, Juan M.', 'de'));
        $this->assertSame('Andes, Ana', (string) NameSearch::highlight('Andes, Ana', 'de'));
        $this->assertSame('2021-<mark class="search-hit">00001</mark>', (string) NameSearch::highlight('2021-00001', '00001'));
    }

    public function test_highlight_escapes_html(): void
    {
        $this->assertSame('&lt;b&gt;<mark class="search-hit">Pe</mark>ña&lt;/b&gt;', (string) NameSearch::highlight('<b>Peña</b>', 'pe'));
    }

    public function test_email_is_marked_only_for_words_with_an_at_sign(): void
    {
        $this->assertSame('maria.santos@student.test', (string) NameSearch::highlight('maria.santos@student.test', 'maria', true));
        $this->assertSame('<mark class="search-hit">maria.santos@</mark>student.test', (string) NameSearch::highlight('maria.santos@student.test', 'maria.santos@', true));
    }
}
