<?php

declare(strict_types=1);

namespace App\View\Components\Mail;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class Button extends Component
{
    public function __construct(
        public string $url,
        public ?string $color = null,
    ) {
        $this->color = $color ?? 'primary';
    }

    public function render(): View
    {
        return view('mail-templates.html.button');
    }
}
