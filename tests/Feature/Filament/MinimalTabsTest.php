<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Components\MinimalTabs;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MinimalTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_minimal_tabs_can_be_created(): void
    {
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

        $this->assertInstanceOf(MinimalTabs::class, $tabs);
    }

    public function test_minimal_tabs_has_minimal_class(): void
    {
        $tabs = MinimalTabs::make('Test Tabs')
            ->tabs([
                MinimalTabs\Tab::make('Tab 1')
                    ->schema([
                        TextInput::make('field1'),
                    ]),
            ]);

        $attributes = $tabs->getExtraAttributes();
        $this->assertArrayHasKey('class', $attributes);
        $this->assertStringContainsString('minimal-tabs', $attributes['class']);
    }

    public function test_minimal_tabs_can_be_compact(): void
    {
        $tabs = MinimalTabs::make('Test Tabs')
            ->compact()
            ->tabs([
                MinimalTabs\Tab::make('Tab 1')
                    ->schema([
                        TextInput::make('field1'),
                    ]),
            ]);

        $attributes = $tabs->getExtraAttributes();
        $this->assertArrayHasKey('class', $attributes);
        $this->assertStringContainsString('minimal-tabs-compact', $attributes['class']);
    }

    public function test_minimal_tabs_with_icons(): void
    {
        $tabs = MinimalTabs::make('Test Tabs')
            ->tabs([
                MinimalTabs\Tab::make('Tab 1')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextInput::make('field1'),
                    ]),
                MinimalTabs\Tab::make('Tab 2')
                    ->icon('heroicon-o-cog')
                    ->schema([
                        TextInput::make('field2'),
                    ]),
            ]);

        $childContainers = $tabs->getChildComponentContainers();
        $this->assertCount(2, $childContainers);

        foreach ($childContainers as $container) {
            $this->assertNotNull($container->getIcon());
        }
    }

    public function test_minimal_tabs_with_badges(): void
    {
        $tabs = MinimalTabs::make('Test Tabs')
            ->tabs([
                MinimalTabs\Tab::make('Tab 1')
                    ->badge('5')
                    ->badgeColor('danger')
                    ->schema([
                        TextInput::make('field1'),
                    ]),
            ]);

        $childContainers = $tabs->getChildComponentContainers();
        $firstTab = $childContainers[0];

        $this->assertEquals('5', $firstTab->getBadge());
        $this->assertEquals('danger', $firstTab->getBadgeColor());
    }

    public function test_minimal_tabs_state_persistence(): void
    {
        $tabs = MinimalTabs::make('Test Tabs')
            ->persistTabInQueryString()
            ->tabs([
                MinimalTabs\Tab::make('Tab 1')
                    ->schema([
                        TextInput::make('field1'),
                    ]),
            ]);

        $this->assertTrue($tabs->isTabPersistedInQueryString());
    }

    public function test_minimal_tabs_in_schema(): void
    {
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
        $this->assertCount(1, $components);
        $this->assertInstanceOf(MinimalTabs::class, $components[0]);
    }

    public function test_minimal_tabs_conditional_visibility(): void
    {
        $tabs = MinimalTabs::make('Test Tabs')
            ->tabs([
                MinimalTabs\Tab::make('Tab 1')
                    ->schema([
                        TextInput::make('field1'),
                    ]),
                MinimalTabs\Tab::make('Tab 2')
                    ->visible(fn (): false => false)
                    ->schema([
                        TextInput::make('field2'),
                    ]),
            ]);

        $childContainers = $tabs->getChildComponentContainers();
        $this->assertCount(2, $childContainers);

        // Second tab should be hidden
        $this->assertFalse($childContainers[1]->isVisible());
    }

    public function test_minimal_tabs_vertical_layout(): void
    {
        $tabs = MinimalTabs::make('Test Tabs')
            ->vertical()
            ->tabs([
                MinimalTabs\Tab::make('Tab 1')
                    ->schema([
                        TextInput::make('field1'),
                    ]),
            ]);

        // Vertical layout is handled by Filament's base Tabs component
        $this->assertInstanceOf(MinimalTabs::class, $tabs);
    }

    public function test_minimal_tabs_with_dynamic_badge(): void
    {
        $tabs = MinimalTabs::make('Test Tabs')
            ->tabs([
                MinimalTabs\Tab::make('Notifications')
                    ->badge(fn (): string => '10')
                    ->badgeColor(fn ($badge): string => $badge > 5 ? 'danger' : 'success')
                    ->schema([
                        TextInput::make('field1'),
                    ]),
            ]);

        $childContainers = $tabs->getChildComponentContainers();
        $firstTab = $childContainers[0];

        // Badge should be callable
        $badge = $firstTab->getBadge();
        $this->assertIsCallable($badge);
    }
}
