<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\Knowledge\ArticleVisibility;
use App\Models\KnowledgeTemplateResponse;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Str;

final class RecentKnowledgeTemplateResponses extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('app.labels.recent_template_responses'))
            ->query(
                KnowledgeTemplateResponse::query()
                    ->with(['category', 'creator'])
                    ->latest('updated_at'),
            )
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([5, 10, 25])
            ->columns([
                TextColumn::make('title')
                    ->label(__('app.labels.title'))
                    ->wrap()
                    ->limit(50)
                    ->searchable()
                    ->url(fn (KnowledgeTemplateResponse $record): string => route('filament.app.resources.knowledge-template-responses.edit', [
                        'record' => $record,
                    ])),

                TextColumn::make('category.name')
                    ->label(__('app.labels.category'))
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),

                TextColumn::make('visibility')
                    ->label(__('app.labels.visibility'))
                    ->badge()
                    ->formatStateUsing(fn (ArticleVisibility|string|null $state): string => $state instanceof ArticleVisibility
                            ? $state->getLabel()
                            : (ArticleVisibility::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state)),
                    )
                    ->color(fn (ArticleVisibility|string|null $state): string => $state instanceof ArticleVisibility
                            ? $state->getColor()
                            : (ArticleVisibility::tryFrom((string) $state)?->getColor() ?? 'gray'),
                    ),

                IconColumn::make('is_active')
                    ->label(__('app.labels.active'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('creator.name')
                    ->label(__('app.labels.created_by'))
                    ->placeholder('System')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label(__('app.labels.updated_at'))
                    ->since()
                    ->sortable(),
            ])
            ->emptyStateHeading(__('app.messages.no_template_responses'))
            ->emptyStateDescription(__('app.messages.create_first_template_response'))
            ->emptyStateIcon('heroicon-o-sparkles');
    }
}
