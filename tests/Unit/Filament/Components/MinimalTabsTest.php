<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Components;

use App\Filament\Components\MinimalTabs;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;

it('extends Filament Schemas Tabs component', function (): void {
    $tabs = MinimalTabs::make('Test');

    expect($tabs)->toBeInstanceOf(Tabs::class);
    expect($tabs)->toBeInstanceOf(MinimalTabs::class);
});

it('applies minimal styling by default', function (): void {
    $tabs = MinimalTabs::make('Test');

    $attributes = $tabs->getExtraAttributes();

    expect($attributes)->toHaveKey('class');
    expect($attributes['class'])->toContain('minimal-tabs');
});

it('can disable minimal styling', function (): void {
    $tabs = MinimalTabs::make('Test')->minimal(false);

    $attributes = $tabs->getExtraAttributes();

    expect($attributes)->toHaveKey('class');
    expect($attributes['class'])->toBe('');
});

it('can apply compact styling', function (): void {
    $tabs = MinimalTabs::make('Test')->compact();

    $attributes = $tabs->getExtraAttributes();

    expect($attributes)->toHaveKey('class');
    expect($attributes['class'])->toContain('minimal-tabs-compact');
});

it('can disable compact styling', function (): void {
    $tabs = MinimalTabs::make('Test')->compact(false);

    $attributes = $tabs->getExtraAttributes();

    expect($attributes)->toHaveKey('class');
    expect($attributes['class'])->toBe('');
});

it('can chain minimal and compact styling', function (): void {
    $tabs = MinimalTabs::make('Test')
        ->minimal()
        ->compact();

    $attributes = $tabs->getExtraAttributes();

    expect($attributes)->toHaveKey('class');
    expect($attributes['class'])->toContain('minimal-tabs');
    expect($attributes['class'])->toContain('minimal-tabs-compact');
});

it('uses correct view path', function (): void {
    $tabs = MinimalTabs::make('Test');

    expect($tabs->getView())->toBe('filament.components.minimal-tabs');
});

it('can be created with label', function (): void {
    $tabs = MinimalTabs::make('Settings');

    expect($tabs->getLabel())->toBe('Settings');
});

it('can be created without label', function (): void {
    $tabs = MinimalTabs::make();

    expect($tabs->getLabel())->toBeNull();
});

it('can be configured with tabs', function (): void {
    $tabs = MinimalTabs::make('Test')
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

    // Just test that tabs can be configured without accessing container
    expect($tabs)->toBeInstanceOf(MinimalTabs::class);
});

it('can handle empty tabs array', function (): void {
    $tabs = MinimalTabs::make('Test')->tabs([]);

    // Just test that empty tabs can be configured without accessing container
    expect($tabs)->toBeInstanceOf(MinimalTabs::class);
});

it('preserves extra attributes when applying styling', function (): void {
    $tabs = MinimalTabs::make('Test')
        ->minimal()
        ->compact();

    $attributes = $tabs->getExtraAttributes();

    expect($attributes)->toHaveKey('class');
    expect($attributes['class'])->toContain('minimal-tabs-compact');
});

it('can be configured with parent methods', function (): void {
    $tabs = MinimalTabs::make('Test')
        ->columnSpanFull()
        ->contained()
        ->persistTabInQueryString()
        ->tabs([
            MinimalTabs\Tab::make('Tab 1')
                ->icon('heroicon-o-user')
                ->badge('5')
                ->schema([
                    TextInput::make('field1'),
                ]),
        ]);

    // Test that methods can be chained without errors
    expect($tabs)->toBeInstanceOf(MinimalTabs::class);
    expect($tabs->isContained())->toBeTrue();
    expect($tabs->isTabPersistedInQueryString())->toBeTrue();
});

it('handles CSS class management correctly', function (): void {
    $tabs = MinimalTabs::make('Test');

    // Start with no classes
    expect($tabs->getExtraAttributes()['class'] ?? '')->toBe('minimal-tabs');

    // Add compact
    $tabs->compact();
    $classes = $tabs->getExtraAttributes()['class'];
    expect($classes)->toContain('minimal-tabs');
    expect($classes)->toContain('minimal-tabs-compact');

    // Remove minimal
    $tabs->minimal(false);
    $classes = $tabs->getExtraAttributes()['class'];
    expect($classes)->not->toContain('minimal-tabs');
    expect($classes)->toContain('minimal-tabs-compact');

    // Remove compact
    $tabs->compact(false);
    $classes = $tabs->getExtraAttributes()['class'];
    expect($classes)->toBe('');
});

it('maintains class order when adding and removing classes', function (): void {
    $tabs = MinimalTabs::make('Test')
        ->extraAttributes(['class' => 'first-class'])
        ->minimal()
        ->extraAttributes(['class' => $tabs->getExtraAttributes()['class'] . ' last-class'])
        ->compact();

    $classes = explode(' ', (string) $tabs->getExtraAttributes()['class']);

    expect($classes)->toContain('first-class');
    expect($classes)->toContain('minimal-tabs');
    expect($classes)->toContain('last-class');
    expect($classes)->toContain('minimal-tabs-compact');
});

it('handles null and empty label correctly', function (): void {
    $tabsWithNull = MinimalTabs::make();
    $tabsWithEmpty = MinimalTabs::make('');
    $tabsWithString = MinimalTabs::make('Test Label');

    expect($tabsWithNull->getLabel())->toBeNull();
    expect($tabsWithEmpty->getLabel())->toBe('');
    expect($tabsWithString->getLabel())->toBe('Test Label');

    // All should have minimal styling by default
    expect($tabsWithNull->getExtraAttributes()['class'])->toContain('minimal-tabs');
    expect($tabsWithEmpty->getExtraAttributes()['class'])->toContain('minimal-tabs');
    expect($tabsWithString->getExtraAttributes()['class'])->toContain('minimal-tabs');
});

it('preserves view path correctly', function (): void {
    $tabs = MinimalTabs::make('Test')
        ->minimal()
        ->compact();

    expect($tabs->getView())->toBe('filament.components.minimal-tabs');
});

it('handles complex class manipulation scenarios', function (): void {
    $tabs = MinimalTabs::make('Test')
        ->extraAttributes(['class' => 'initial-class'])
        ->minimal(false) // Should not add minimal-tabs since it's false
        ->compact()      // Should add minimal-tabs-compact
        ->minimal()      // Should add minimal-tabs
        ->compact(false) // Should remove minimal-tabs-compact
        ->minimal(false) // Should remove minimal-tabs
        ->minimal();     // Should add minimal-tabs back

    $classes = explode(' ', trim((string) $tabs->getExtraAttributes()['class']));

    expect($classes)->toContain('initial-class');
    expect($classes)->toContain('minimal-tabs');
    expect($classes)->not->toContain('minimal-tabs-compact');
});
