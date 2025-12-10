<?php

declare(strict_types=1);

use App\Filament\Components\MinimalTabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

uses()->group('filament', 'components');

it('can be created with tabs', function () {
    $tabs = MinimalTabs::make('Test Tabs')
        ->tabs([
            MinimalTabs\Tab::make('Tab 1')
                ->schema([
                    TextInput::make('field1'),
                ]),
            MinimalTabs\Tab::make('Tab 2')
                ->schema([
                    TextInput::make('field2'),
                ]),
        ]);

    expect($tabs)->toBeInstanceOf(MinimalTabs::class);
});

it('has minimal class by default', function () {
    $tabs = MinimalTabs::make('Test Tabs')
        ->tabs([
            MinimalTabs\Tab::make('Tab 1')
                ->schema([
                    TextInput::make('field1'),
                ]),
        ]);

    $attributes = $tabs->getExtraAttributes();
    
    expect($attributes)->toHaveKey('class');
    expect($attributes['class'])->toContain('minimal-tabs');
});

it('can be compact', function () {
    $tabs = MinimalTabs::make('Test Tabs')
        ->compact()
        ->tabs([
            MinimalTabs\Tab::make('Tab 1')
                ->schema([
                    TextInput::make('field1'),
                ]),
        ]);

    $attributes = $tabs->getExtraAttributes();
    
    expect($attributes)->toHaveKey('class');
    expect($attributes['class'])->toContain('minimal-tabs-compact');
});

it('supports state persistence in query string', function () {
    $tabs = MinimalTabs::make('Test Tabs')
        ->persistTabInQueryString()
        ->tabs([
            MinimalTabs\Tab::make('Tab 1')
                ->schema([
                    TextInput::make('field1'),
                ]),
        ]);

    expect($tabs->isTabPersistedInQueryString())->toBeTrue();
});

it('can be used in schema', function () {
    $schema = Schema::make()
        ->components([
            MinimalTabs::make('Test Tabs')
                ->tabs([
                    MinimalTabs\Tab::make('General')
                        ->icon('heroicon-o-cog')
                        ->schema([
                            TextInput::make('name')->required(),
                            TextInput::make('email')->email(),
                        ]),
                    MinimalTabs\Tab::make('Advanced')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->schema([
                            TextInput::make('api_key'),
                        ]),
                ])
                ->columnSpanFull(),
        ]);

    $components = $schema->getComponents();
    
    expect($components)->toHaveCount(1);
    expect($components[0])->toBeInstanceOf(MinimalTabs::class);
});

it('supports vertical layout', function () {
    $tabs = MinimalTabs::make('Test Tabs')
        ->vertical()
        ->tabs([
            MinimalTabs\Tab::make('Tab 1')
                ->schema([
                    TextInput::make('field1'),
                ]),
        ]);

    expect($tabs)->toBeInstanceOf(MinimalTabs::class);
    expect($tabs->isVertical())->toBeTrue();
});

it('supports contained layout', function () {
    $tabs = MinimalTabs::make('Test Tabs')
        ->contained()
        ->tabs([
            MinimalTabs\Tab::make('Tab 1')
                ->schema([
                    TextInput::make('field1'),
                ]),
        ]);

    expect($tabs->isContained())->toBeTrue();
});

it('maintains compatibility with Filament v4.3+ schemas', function () {
    // Test that MinimalTabs works with the new Schemas system
    $tabs = MinimalTabs::make('Test')
        ->tabs([
            MinimalTabs\Tab::make('Tab 1')
                ->schema([
                    TextInput::make('field1'),
                ]),
        ]);

    // Should extend from Filament\Schemas\Components\Tabs
    expect($tabs)->toBeInstanceOf(\Filament\Schemas\Components\Tabs::class);
    
    // Should have the correct view
    expect($tabs->getView())->toBe('filament.components.minimal-tabs');
});

it('can handle empty tabs gracefully', function () {
    $tabs = MinimalTabs::make('Empty Tabs')->tabs([]);
    
    expect($tabs)->toBeInstanceOf(MinimalTabs::class);
});

it('can chain multiple methods', function () {
    $tabs = MinimalTabs::make('Full Featured')
        ->tabs([
            MinimalTabs\Tab::make('Tab 1')
                ->icon('heroicon-o-user')
                ->badge('5')
                ->badgeColor('success')
                ->schema([
                    TextInput::make('name'),
                ]),
        ])
        ->contained()
        ->vertical()
        ->persistTabInQueryString()
        ->columnSpanFull()
        ->minimal()
        ->compact();

    expect($tabs->isContained())->toBeTrue();
    expect($tabs->isVertical())->toBeTrue();
    expect($tabs->isTabPersistedInQueryString())->toBeTrue();
    
    $attributes = $tabs->getExtraAttributes();
    expect($attributes)->toHaveKey('class');
    expect($attributes['class'])->toContain('minimal-tabs-compact');
});

it('can be configured with complex nested schemas', function () {
    $tabs = MinimalTabs::make('Settings')
        ->tabs([
            MinimalTabs\Tab::make('General')
                ->icon('heroicon-o-cog')
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
            MinimalTabs\Tab::make('Advanced')
                ->icon('heroicon-o-adjustments-horizontal')
                ->schema([
                    TextInput::make('api_key')
                        ->password()
                        ->revealable(),
                    TextInput::make('webhook_url')
                        ->url(),
                ]),
        ])
        ->columnSpanFull()
        ->persistTabInQueryString();

    expect($tabs)->toBeInstanceOf(MinimalTabs::class);
    expect($tabs->isTabPersistedInQueryString())->toBeTrue();
});

it('applies both minimal and compact styling when chained', function () {
    $tabs = MinimalTabs::make('Test')
        ->minimal()
        ->compact();
    
    $attributes = $tabs->getExtraAttributes();
    
    expect($attributes)->toHaveKey('class');
    expect($attributes['class'])->toContain('minimal-tabs');
    expect($attributes['class'])->toContain('minimal-tabs-compact');
});

it('can disable styling conditionally', function () {
    $tabs = MinimalTabs::make('Test')
        ->minimal(false)
        ->compact(false);
    
    $attributes = $tabs->getExtraAttributes();
    
    expect($attributes)->toHaveKey('class');
    expect($attributes['class'])->toBe('');
});

it('preserves existing CSS classes when adding minimal styling', function () {
    $tabs = MinimalTabs::make('Test')
        ->extraAttributes(['class' => 'existing-class'])
        ->minimal();
    
    $attributes = $tabs->getExtraAttributes();
    
    expect($attributes['class'])->toContain('existing-class');
    expect($attributes['class'])->toContain('minimal-tabs');
});

it('preserves existing CSS classes when adding compact styling', function () {
    $tabs = MinimalTabs::make('Test')
        ->extraAttributes(['class' => 'existing-class another-class'])
        ->compact();
    
    $attributes = $tabs->getExtraAttributes();
    
    expect($attributes['class'])->toContain('existing-class');
    expect($attributes['class'])->toContain('another-class');
    expect($attributes['class'])->toContain('minimal-tabs-compact');
});

it('does not duplicate CSS classes when applied multiple times', function () {
    $tabs = MinimalTabs::make('Test')
        ->minimal()
        ->minimal()
        ->compact()
        ->compact();
    
    $attributes = $tabs->getExtraAttributes();
    $classes = explode(' ', $attributes['class']);
    
    expect(array_count_values($classes)['minimal-tabs'])->toBe(1);
    expect(array_count_values($classes)['minimal-tabs-compact'])->toBe(1);
});

it('can remove minimal styling after applying it', function () {
    $tabs = MinimalTabs::make('Test')
        ->minimal()
        ->minimal(false);
    
    $attributes = $tabs->getExtraAttributes();
    
    expect($attributes['class'])->not->toContain('minimal-tabs');
});

it('can remove compact styling after applying it', function () {
    $tabs = MinimalTabs::make('Test')
        ->compact()
        ->compact(false);
    
    $attributes = $tabs->getExtraAttributes();
    
    expect($attributes['class'])->not->toContain('minimal-tabs-compact');
});

it('handles empty class attribute gracefully', function () {
    $tabs = MinimalTabs::make('Test')
        ->extraAttributes(['class' => ''])
        ->minimal()
        ->compact();
    
    $attributes = $tabs->getExtraAttributes();
    
    expect($attributes['class'])->toBe('minimal-tabs minimal-tabs-compact');
});

it('handles whitespace in class attributes correctly', function () {
    $tabs = MinimalTabs::make('Test')
        ->extraAttributes(['class' => '  existing-class   another-class  '])
        ->minimal();
    
    $attributes = $tabs->getExtraAttributes();
    $classes = explode(' ', trim($attributes['class']));
    
    expect($classes)->toContain('existing-class');
    expect($classes)->toContain('another-class');
    expect($classes)->toContain('minimal-tabs');
    expect($classes)->not->toContain(''); // No empty strings
});
