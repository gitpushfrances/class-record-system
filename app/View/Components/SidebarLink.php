<?php

namespace App\View\Components;

use Illuminate\View\Component;

class SidebarLink extends Component
{
    public function __construct(
        public string $href,
        public bool $active = false,
        public string $icon = 'circle',
    ) {}

    public function render()
    {
        return view('components.sidebar-link');
    }
}
