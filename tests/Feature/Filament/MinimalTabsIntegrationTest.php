<?php

declare(strict_types=1);

use App\Filament\Components\MinimalTabs;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

uses()->group('filament', 'components', 'integration');

/**
 * Integration tests for MinimalTabs component with Filament v4.3+ schemas.
 */
it('integrates correctly with Filament v4.3+ schema system', function (): void {
    $schema = Schema::make()
        ->components([
            MinimalTabs::make('User Settings')
                ->tabs([
                    MinimalTabs\Tab::make('Profile')
                        ->icon('heroicon-o-user')
                        ->badge('3')
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('email')
                                ->email()
                                ->required(),
                            Toggle::make('active')
                                ->default(true),
                        ]),
                    MinimalTabs\Tab::make('Preferences')
                        ->icon('heroicon-o-cog')
                        ->schema([
                            Select::make('theme')
                                ->options([
                                    'light' => 'Light',
                                    'dark' => 'Dark',
                                    'auto' => 'Auto',
                                ])
                                ->default('auto'),
                            Toggle::make('notifications')
                                ->default(true),
                        ]),
                    MinimalTabs\Tab::make('Security')
                        ->icon('heroicon-o-shield-check')
                        ->badge('1')
                        ->badgeColor('danger')
                        ->schema([
                            TextInput::make('current_password')
                                ->password()
                                ->required(),
                            TextInput::make('new_password')
                                ->password()
                                ->confirmed(),
                            TextInput::make('new_password_confirmation')
                                ->password(),
                        ]),
                ])
                ->compact()
                ->contained()
                ->persistTabInQueryString()
                ->columnSpanFull(),
        ]);

    $components = $schema->getComponents();

    expect($components)->toHaveCount(1);
    expect($components[0])->toBeInstanceOf(MinimalTabs::class);

    $tabs = $components[0];
    expect($tabs->isContained())->toBeTrue();
    expect($tabs->isTabPersistedInQueryString())->toBeTrue();

    // Check CSS classes
    $classes = $tabs->getExtraAttributes()['class'];
    expect($classes)->toContain('minimal-tabs');
    expect($classes)->toContain('minimal-tabs-compact');
});

it('works correctly with nested schemas and complex layouts', function (): void {
    $schema = Schema::make()
        ->components([
            MinimalTabs::make('Application Settings')
                ->tabs([
                    MinimalTabs\Tab::make('Database')
                        ->schema([
                            Schema::make()
                                ->components([
                                    TextInput::make('db_host')
                                        ->label('Database Host')
                                        ->default('localhost'),
                                    TextInput::make('db_port')
                                        ->label('Database Port')
                                        ->numeric()
                                        ->default(3306),
                                    TextInput::make('db_name')
                                        ->label('Database Name')
                                        ->required(),
                                ]),
                        ]),
                    MinimalTabs\Tab::make('Cache')
                        ->schema([
                            Select::make('cache_driver')
                                ->options([
                                    'file' => 'File',
                                    'redis' => 'Redis',
                                    'memcached' => 'Memcached',
                                ])
                                ->default('file'),
                            TextInput::make('cache_ttl')
                                ->label('Cache TTL (seconds)')
                                ->numeric()
                                ->default(3600),
                        ]),
                ])
                ->minimal()
                ->vertical(),
        ]);

    $components = $schema->getComponents();
    $tabs = $components[0];

    expect($tabs)->toBeInstanceOf(MinimalTabs::class);
    expect($tabs->isVertical())->toBeTrue();

    $classes = $tabs->getExtraAttributes()['class'];
    expect($classes)->toContain('minimal-tabs');
});

it('maintains performance with deeply nested tab structures', function (): void {
    $startTime = microtime(true);

    $nestedTabs = [];
    for ($i = 0; $i < 50; $i++) {
        $nestedTabs[] = MinimalTabs\Tab::make("Tab {$i}")
            ->icon('heroicon-o-document')
            ->badge((string) $i)
            ->schema([
                TextInput::make("field_{$i}")
                    ->label("Field {$i}")
                    ->default("Default value {$i}"),
                Toggle::make("toggle_{$i}")
                    ->label("Toggle {$i}")
                    ->default($i % 2 === 0),
            ]);
    }

    $schema = Schema::make()
        ->components([
            MinimalTabs::make('Large Tab Set')
                ->tabs($nestedTabs)
                ->minimal()
                ->compact()
                ->contained(),
        ]);

    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;

    // Should complete in reasonable time
    expect($executionTime)->toBeLessThan(1.0);

    $components = $schema->getComponents();
    expect($components)->toHaveCount(1);
    expect($components[0])->toBeInstanceOf(MinimalTabs::class);
});

it('handles dynamic tab content correctly', function (): void {
    $dynamicTabs = [];
    $tabCount = random_int(3, 10);

    for ($i = 0; $i < $tabCount; $i++) {
        $fieldCount = random_int(1, 5);
        $fields = [];

        for ($j = 0; $j < $fieldCount; $j++) {
            $fields[] = TextInput::make("dynamic_field_{$i}_{$j}")
                ->label("Dynamic Field {$i}-{$j}");
        }

        $dynamicTabs[] = MinimalTabs\Tab::make("Dynamic Tab {$i}")
            ->schema($fields);
    }

    $tabs = MinimalTabs::make('Dynamic Tabs')
        ->tabs($dynamicTabs)
        ->minimal()
        ->compact();

    expect($tabs)->toBeInstanceOf(MinimalTabs::class);

    $classes = $tabs->getExtraAttributes()['class'];
    expect($classes)->toContain('minimal-tabs');
    expect($classes)->toContain('minimal-tabs-compact');
});

it('preserves tab state and configuration through method chaining', function (): void {
    $tabs = MinimalTabs::make('Stateful Tabs')
        ->tabs([
            MinimalTabs\Tab::make('Tab 1')
                ->icon('heroicon-o-home')
                ->badge('New')
                ->badgeColor('success')
                ->schema([
                    TextInput::make('field1'),
                ]),
        ])
        ->contained()
        ->vertical()
        ->persistTabInQueryString()
        ->minimal()
        ->compact()
        ->minimal(false)  // Remove minimal
        ->compact(false)  // Remove compact
        ->minimal()       // Add minimal back
        ->compact();      // Add compact back

    // All parent configurations should be preserved
    expect($tabs->isContained())->toBeTrue();
    expect($tabs->isVertical())->toBeTrue();
    expect($tabs->isTabPersistedInQueryString())->toBeTrue();

    // Final CSS classes should be correct
    $classes = $tabs->getExtraAttributes()['class'];
    expect($classes)->toContain('minimal-tabs');
    expect($classes)->toContain('minimal-tabs-compact');
});

it('works correctly with conditional tab visibility', function (): void {
    $showAdvanced = true;

    $tabsArray = [
        MinimalTabs\Tab::make('Basic')
            ->schema([
                TextInput::make('name')->required(),
            ]),
    ];

    $tabsArray[] = MinimalTabs\Tab::make('Advanced')
        ->schema([
            TextInput::make('api_key')->password(),
        ]);

    $tabs = MinimalTabs::make('Conditional Tabs')
        ->tabs($tabsArray)
        ->minimal()
        ->compact();

    expect($tabs)->toBeInstanceOf(MinimalTabs::class);

    $classes = $tabs->getExtraAttributes()['class'];
    expect($classes)->toContain('minimal-tabs');
    expect($classes)->toContain('minimal-tabs-compact');
});

it('maintains compatibility with all Filament v4.3+ tab features', function (): void {
    $tabs = MinimalTabs::make('Full Feature Test')
        ->tabs([
            MinimalTabs\Tab::make('Complete Tab')
                ->icon('heroicon-o-star')
                ->iconPosition('after')
                ->badge('99+')
                ->badgeColor('warning')
                ->schema([
                    TextInput::make('test_field')
                        ->label('Test Field')
                        ->helperText('This is a helper text')
                        ->placeholder('Enter value here')
                        ->required()
                        ->maxLength(100),
                ]),
        ])
        ->contained(true)
        ->vertical(false)
        ->persistTabInQueryString(true)
        ->columnSpanFull()
        ->minimal()
        ->compact();

    // Test all features work together
    expect($tabs)->toBeInstanceOf(MinimalTabs::class);
    expect($tabs->isContained())->toBeTrue();
    expect($tabs->isVertical())->toBeFalse();
    expect($tabs->isTabPersistedInQueryString())->toBeTrue();

    $classes = $tabs->getExtraAttributes()['class'];
    expect($classes)->toContain('minimal-tabs');
    expect($classes)->toContain('minimal-tabs-compact');
});
