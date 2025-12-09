<?php

declare(strict_types=1);

namespace App\View\Components\Mail;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class Layout extends Component
{
    public function __construct(
        public ?string $theme = null,
    ) {
        $this->theme = $theme ?? config('mail.markdown.theme', 'default');
    }

    public function render(): View
    {
        return view('mail-templates.html.layout');
    }
}
