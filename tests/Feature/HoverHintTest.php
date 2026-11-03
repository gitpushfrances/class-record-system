<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class HoverHintTest extends TestCase
{
    public function test_the_hint_renders_a_focusable_icon_with_the_text_hidden_until_hover(): void
    {
        $html = Blade::render('<x-hint text="Saves it." />');

        $this->assertStringContainsString('class="hint"', $html);
        $this->assertStringContainsString('tabindex="0"', $html);
        $this->assertStringContainsString('fa-circle-info', $html);
        $this->assertStringContainsString('aria-label="Saves it."', $html);
        $this->assertMatchesRegularExpression('/<span class="hint-text" role="tooltip">Saves it\.<\/span>/', $html);
        $this->assertStringContainsString('.hint-text { display:none;', $html);
    }

    public function test_the_hint_escapes_its_text(): void
    {
        $html = Blade::render('<x-hint text="<b>x</b>" />');

        $this->assertStringNotContainsString('<b>x</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;x&lt;/b&gt;', $html);
    }

    public function test_the_style_and_script_are_added_only_once_per_page(): void
    {
        $html = Blade::render('<x-hint text="One." /><x-hint text="Two." />');

        $this->assertSame(1, substr_count($html, '.hint-text { display:none;'));
        $this->assertSame(1, substr_count($html, "addEventListener('mouseover'"));
        $this->assertSame(2, substr_count($html, 'class="hint"'));
        $this->assertSame(1, substr_count($html, 'window.setHint'));
    }
}
