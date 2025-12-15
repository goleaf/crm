<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\NoteCategory;
use App\Enums\NoteVisibility;
use App\Models\Note;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

final class RecentNotes extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('app.labels.notes'))
            ->query(
                Note::query()
                    ->with(['creator', 'companies', 'people'])
                    ->latest('created_at'),
            )
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([5, 10, 25])
            ->columns([
                TextColumn::make('title')
                    ->label(__('app.labels.title'))
                    ->wrap()
                    ->limit(50)
                    ->searchable()
                    ->url(fn (Note $record): string => route('filament.app.resources.notes.index', [
                        'tableSearch' => $record->title,
                    ])),

                TextColumn::make('category')
                    ->label(__('app.labels.category'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => NoteCategory::tryFrom((string) $state)?->label() ?? 'General',
                    )
                    ->color(fn (?string $state): string => NoteCategory::tryFrom((string) $state)?->color() ?? 'gray',
                    ),

                TextColumn::make('visibility')
                    ->label(__('app.labels.visibility'))
                    ->badge()
                    ->formatStateUsing(fn (NoteVisibility|string|null $state): string => $state instanceof NoteVisibility
                            ? $state->getLabel()
                            : (NoteVisibility::tryFrom((string) $state)?->getLabel() ?? 'Internal'),
                    )
                    ->color(fn (NoteVisibility|string|null $state): string => $state instanceof NoteVisibility
                            ? $state->color()
                            : (NoteVisibility::tryFrom((string) $state)?->color() ?? 'primary'),
                    ),

                IconColumn::make('is_template')
                    ->label(__('app.labels.template'))
                    ->boolean()
                    ->trueIcon('heroicon-o-document-duplicate')
                    ->falseIcon('heroicon-o-document-text')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                TextColumn::make('companies.name')
                    ->label(__('app.labels.companies'))
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList(),

                TextColumn::make('people.name')
                    ->label(__('app.labels.people'))
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList(),

                TextColumn::make('creator.name')
                    ->label(__('app.labels.created_by'))
                    ->placeholder('System')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->since()
                    ->sortable(),
            ])
            ->emptyStateHeading('No notes yet')
            ->emptyStateDescription('Create your first note to see it here.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}
