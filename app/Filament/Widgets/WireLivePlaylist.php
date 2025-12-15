<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

final class WireLivePlaylist extends Widget
{
    protected string $view = 'filament.widgets.wire-live-playlist';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<int, array{title: string, speaker: string, url: string}>
     */
    public function getPlaylist(): array
    {
        $playlistUrl = 'https://www.youtube.com/playlist?list=PLH3DZfpF7H73EXPI_AhwUBud22VufndZV';

        return [
            [
                'title' => 'Livewire 4 Keynote',
                'speaker' => 'Caleb Porzio',
                'url' => $playlistUrl,
            ],
            [
                'title' => 'Build mobile apps with Livewire',
                'speaker' => 'Shane Rosenthal',
                'url' => $playlistUrl,
            ],
            [
                'title' => 'Making the most of Alpine in Livewire',
                'speaker' => 'Ryan Chandler',
                'url' => $playlistUrl,
            ],
            [
                'title' => 'Bind: Modern JS in Laravel Blade',
                'speaker' => 'Filip Ganyicz',
                'url' => $playlistUrl,
            ],
            [
                'title' => 'The business case for Livewire',
                'speaker' => 'Matt Stauffer',
                'url' => $playlistUrl,
            ],
            [
                'title' => 'The observer pattern',
                'speaker' => 'Mary Perry',
                'url' => $playlistUrl,
            ],
            [
                'title' => 'Courage in the small moments',
                'speaker' => 'Katie Wright',
                'url' => $playlistUrl,
            ],
            [
                'title' => 'The edge of nonsense',
                'speaker' => 'Josh Cirre',
                'url' => $playlistUrl,
            ],
            [
                'title' => 'Testing Livewire',
                'speaker' => 'Jason McCreary',
                'url' => $playlistUrl,
            ],
            [
                'title' => 'Livewire.pdf and other atrocities',
                'speaker' => 'Daniel Coulbourne',
                'url' => $playlistUrl,
            ],
            [
                'title' => 'Filament’s use of Livewire + Alpine',
                'speaker' => 'Dan Harrin',
                'url' => $playlistUrl,
            ],
            [
                'title' => 'Livewire in production',
                'speaker' => 'Andy Newhouse',
                'url' => $playlistUrl,
            ],
            [
                'title' => 'From fired to founder',
                'speaker' => 'Kevin McKee',
                'url' => $playlistUrl,
            ],
            [
                'title' => 'Livewire 4 Q&A',
                'speaker' => 'Caleb Porzio',
                'url' => $playlistUrl,
            ],
        ];
    }
}
