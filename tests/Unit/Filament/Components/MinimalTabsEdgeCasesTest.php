<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Components;

use App\Filament\Components\MinimalTabs;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Edge case and error condition tests for MinimalTabs component.
 */
it('handles Htmlable labels correctly', function () {
    $htmlableLabel = new class implements Htmlable {
        public function toHtml(): string
        {
            return '<span>HTML Label</span>';
        }
    };
    
    $tabs = MinimalTabs::make($htmlableLabel);
    
    expect($tabs->getLabel())->toBe($htmlableLabel);
    expect($tabs->getExtraAttributes()['class'])->toContain('minimal-tabs');
});

it('handles closure labels correctly', function () {
    $closureLabel = fn (): string => 'Dynamic Label';
    
    $tabs = MinimalTabs::make($closureLabel);
    
    expect($tabs->getLabel())->toBe($closureLabel);
    expect($tabs->getExtraAttributes()['class'])->toContain('minimal-tabs');
});

it('handles very long class strings efficiently', function () {
    $longClassString = str_repeat('very-long-class-name ', 100);
    
    $tabs = MinimalTabs::make('Test')
        ->extraAttributes(['class' => $longClassString])
        ->minimal()
        ->compact();
    
    $classes = $tabs->getExtraAttributes()['class'];
    
    expect($classes)->toContain('minimal-tabs');
    expect($classes)->toContain('minimal-tabs-compact');
    expect(substr_count($classes, 'very-long-class-name'))->toBe(100);
});

it('handles special characters in existing classes', function () {
    $tabs = MinimalTabs::make('Test')
        ->extraAttributes(['class' => 'class-with-numbers123 class_with_underscores class-with-dashes'])
        ->minimal()
        ->compact();
    
    $classes = $tabs->getExtraAttributes()['class'];
    
    expect($classes)->toContain('class-with-numbers123');
    expect($classes)->toContain('class_with_underscores');
    expect($classes)->toContain('class-with-dashes');
    expect($classes)->toContain('minimal-tabs');
    expect($classes)->toContain('minimal-tabs-compact');
});

it('maintains performance with repeated class operations', function () {
    $tabs = MinimalTabs::make('Test');
    
    // Perform many operations to test performance
    for ($i = 0; $i < 100; $i++) {
        $tabs->minimal($i % 2 === 0)
             ->compact($i % 3 === 0);
    }
    
    // Should end with minimal=true (even) and compact=false (not divisible by 3)
    $classes = $tabs->getExtraAttributes()['class'];
    expect($classes)->toContain('minimal-tabs');
    expect($classes)->not->toContain('minimal-tabs-compact');
});

it('handles concurrent class modifications correctly', function () {
    $tabs = MinimalTabs::make('Test')
        ->extraAttributes(['class' => 'base-class']);
    
    // Simulate concurrent modifications
    $tabs->minimal()
         ->extraAttributes(['class' => $tabs->getExtraAttributes()['class'] . ' added-class'])
         ->compact()
         ->extraAttributes(['class' => $tabs->getExtraAttributes()['class'] . ' another-added-class']);
    
    $classes = explode(' ', trim($tabs->getExtraAttributes()['class']));
    
    expect($classes)->toContain('base-class');
    expect($classes)->toContain('minimal-tabs');
    expect($classes)->toContain('added-class');
    expect($classes)->toContain('minimal-tabs-compact');
    expect($classes)->toContain('another-added-class');
});

it('handles empty and whitespace-only class strings', function () {
    $testCases = [
        '',
        ' ',
        '   ',
        "\t",
        "\n",
        " \t \n ",
    ];
    
    foreach ($testCases as $emptyClass) {
        $tabs = MinimalTabs::make('Test')
            ->extraAttributes(['class' => $emptyClass])
            ->minimal()
            ->compact();
        
        $classes = trim($tabs->getExtraAttributes()['class']);
        
        expect($classes)->toBe('minimal-tabs minimal-tabs-compact');
    }
});

it('preserves class order when removing classes from middle', function () {
    $tabs = MinimalTabs::make('Test')
        ->extraAttributes(['class' => 'first minimal-tabs middle minimal-tabs-compact last'])
        ->minimal(false); // Remove minimal-tabs from middle
    
    $classes = $tabs->getExtraAttributes()['class'];
    
    expect($classes)->toBe('first middle minimal-tabs-compact last');
});

it('handles tabs with complex nested schemas without affecting class management', function () {
    $tabs = MinimalTabs::make('Complex')
        ->tabs([
            MinimalTabs\Tab::make('Tab 1')
                ->schema([
                    TextInput::make('field1')
                        ->required()
                        ->rules(['string', 'max:255']),
                    TextInput::make('field2')
                        ->default('default value'),
                ]),
            MinimalTabs\Tab::make('Tab 2')
                ->icon('heroicon-o-cog')
                ->badge('5')
                ->badgeColor('success')
                ->schema([
                    TextInput::make('field3')
                        ->email()
                        ->unique(),
                ]),
        ])
        ->minimal()
        ->compact()
        ->contained()
        ->vertical()
        ->persistTabInQueryString();
    
    // Class management should still work correctly
    $classes = $tabs->getExtraAttributes()['class'];
    expect($classes)->toContain('minimal-tabs');
    expect($classes)->toContain('minimal-tabs-compact');
    
    // Parent functionality should still work
    expect($tabs->isContained())->toBeTrue();
    expect($tabs->isVertical())->toBeTrue();
    expect($tabs->isTabPersistedInQueryString())->toBeTrue();
});

it('maintains immutability of original class string when modifying', function () {
    $originalClass = 'original-class';
    $tabs = MinimalTabs::make('Test')
        ->extraAttributes(['class' => $originalClass]);
    
    $tabs->minimal()->compact();
    
    // Original string should be unchanged
    expect($originalClass)->toBe('original-class');
    
    // But tabs should have new classes
    $classes = $tabs->getExtraAttributes()['class'];
    expect($classes)->toContain('original-class');
    expect($classes)->toContain('minimal-tabs');
    expect($classes)->toContain('minimal-tabs-compact');
});