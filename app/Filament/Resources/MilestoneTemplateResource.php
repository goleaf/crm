<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\DependencyType;
use App\Enums\MilestonePriority;
use App\Enums\MilestoneType;
use App\Filament\Resources\MilestoneTemplateResource\Pages\CreateMilestoneTemplate;
use App\Filament\Resources\MilestoneTemplateResource\Pages\EditMilestoneTemplate;
use App\Filament\Resources\MilestoneTemplateResource\Pages\ListMilestoneTemplates;
use App\Models\MilestoneTemplate;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class MilestoneTemplateResource extends Resource
{
    protected static ?string $model = MilestoneTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?int $navigationSort = 12;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.milestone_templates');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('app.labels.template'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('app.labels.name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('category')
                        ->label(__('app.labels.category'))
                        ->maxLength(100),
                    Textarea::make('description')
                        ->label(__('app.labels.description'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make(__('app.labels.milestones'))
                ->schema([
                    Repeater::make('template_data.milestones')
                        ->label(__('app.labels.milestones'))
                        ->schema([
                            TextInput::make('title')
                                ->label(__('app.labels.title'))
                                ->required()
                                ->maxLength(255),
                            Textarea::make('description')
                                ->label(__('app.labels.description'))
                                ->rows(2)
                                ->columnSpanFull(),
                            TextInput::make('target_offset_days')
                                ->label(__('app.labels.target_offset_days'))
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->required(),
                            Select::make('milestone_type')
                                ->label(__('app.labels.milestone_type'))
                                ->options(MilestoneType::class)
                                ->required(),
                            Select::make('priority_level')
                                ->label(__('app.labels.priority'))
                                ->options(MilestonePriority::class)
                                ->required()
                                ->default(MilestonePriority::MEDIUM),
                            \Filament\Forms\Components\Toggle::make('is_critical')
                                ->label(__('app.labels.critical'))
                                ->default(false),
                            \Filament\Forms\Components\Toggle::make('requires_approval')
                                ->label(__('app.labels.requires_approval'))
                                ->default(false),
                            Repeater::make('deliverables')
                                ->label(__('app.labels.deliverables'))
                                ->schema([
                                    TextInput::make('name')
                                        ->label(__('app.labels.name'))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('due_offset_days')
                                        ->label(__('app.labels.due_offset_days'))
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->required(),
                                    Textarea::make('acceptance_criteria')
                                        ->label(__('app.labels.acceptance_criteria'))
                                        ->rows(2)
                                        ->columnSpanFull(),
                                    \Filament\Forms\Components\Toggle::make('requires_approval')
                                        ->label(__('app.labels.requires_approval'))
                                        ->default(false),
                                ])
                                ->collapsed()
                                ->columnSpanFull(),
                        ])
                        ->collapsed()
                        ->minItems(1)
                        ->required()
                        ->columnSpanFull(),
                ]),
            Section::make(__('app.labels.dependencies'))
                ->schema([
                    Repeater::make('template_data.dependencies')
                        ->label(__('app.labels.dependencies'))
                        ->schema([
                            Select::make('predecessor_index')
                                ->label(__('app.labels.predecessor_milestone'))
                                ->options(fn (Get $get): array => $this->milestoneIndexOptions($get))
                                ->required(),
                            Select::make('successor_index')
                                ->label(__('app.labels.successor_milestone'))
                                ->options(fn (Get $get): array => $this->milestoneIndexOptions($get))
                                ->required(),
                            Select::make('dependency_type')
                                ->label(__('app.labels.dependency_type'))
                                ->options(DependencyType::class)
                                ->required()
                                ->default(DependencyType::FINISH_TO_START),
                            TextInput::make('lag_days')
                                ->label(__('app.labels.lag_days'))
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->required(),
                        ])
                        ->columns(4)
                        ->collapsed()
                        ->columnSpanFull(),
                ])
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('category')
                    ->label(__('app.labels.category'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('usage_count')
                    ->label(__('app.labels.usage_count'))
                    ->sortable(),
                TextColumn::make('milestone_count')
                    ->label(__('app.labels.milestones'))
                    ->state(fn (MilestoneTemplate $record): int => count($record->template_data['milestones'] ?? []))
                    ->toggleable(),
                TextColumn::make('typical_duration')
                    ->label(__('app.labels.typical_duration'))
                    ->state(function (MilestoneTemplate $record): string {
                        $offsets = collect($record->template_data['milestones'] ?? [])
                            ->map(fn (array $m): int => (int) ($m['target_offset_days'] ?? 0));

                        return $offsets->isEmpty() ? '—' : ($offsets->max() . ' ' . __('app.labels.days'));
                    })
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMilestoneTemplates::route('/'),
            'create' => CreateMilestoneTemplate::route('/create'),
            'edit' => EditMilestoneTemplate::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<MilestoneTemplate>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * @return array<int, string>
     */
    private function milestoneIndexOptions(Get $get): array
    {
        $milestones = $get('template_data.milestones') ?? [];

        if (! is_array($milestones)) {
            return [];
        }

        $options = [];

        foreach ($milestones as $index => $milestone) {
            if (! is_array($milestone)) {
                continue;
            }

            $title = (string) ($milestone['title'] ?? ('Milestone ' . ($index + 1)));
            $options[$index] = "{$index}: {$title}";
        }

        return $options;
    }
}

