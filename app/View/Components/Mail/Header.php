<?php

declare(strict_types=1);

namespace App\View\Components\Mail;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class Header extends Component
{
    public function __construct(
        public string $url
    ) {}

    public function render(): View
    {
        return view('mail-templates.html.header');
    }
}
