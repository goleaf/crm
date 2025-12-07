<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\CreationSource;
use App\Enums\CustomFields\NoteField;
use App\Enums\NoteCategory;
use App\Enums\NoteVisibility;
use App\Filament\Exports\NoteExporter;
use App\Filament\Resources\NoteResource\Forms\NoteForm;
use App\Filament\Resources\NoteResource\Pages\ManageNotes;
use App\Models\Note;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Override;

final class NoteResource extends Resource
{
    protected static ?string $model = Note::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 5;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function form(Schema $schema): Schema
    {
        return NoteForm::get($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('category')
                    ->label(__('app.labels.category'))
                    ->badge()
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(fn (?string $state): string => NoteCategory::tryFrom((string) $state)?->label() ?? 'General')
                    ->color(fn (?string $state): string => NoteCategory::tryFrom((string) $state)?->color() ?? 'gray'),
                TextColumn::make('visibility')
                    ->label(__('app.labels.visibility'))
                    ->badge()
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(fn (NoteVisibility|string|null $state): string => $state instanceof NoteVisibility ? $state->getLabel() : (NoteVisibility::tryFrom((string) $state)?->getLabel() ?? 'Internal'))
                    ->color(fn (NoteVisibility|string|null $state): string => $state instanceof NoteVisibility ? $state->color() : (NoteVisibility::tryFrom((string) $state)?->color() ?? 'primary')),
                TextColumn::make('is_template')
                    ->label('Template')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Template' : 'Note')
                    ->color(fn (bool $state): string => $state ? 'primary' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('companies.name')
                    ->label(__('app.labels.companies'))
                    ->toggleable(),
                TextColumn::make('people.name')
                    ->label(__('app.labels.people'))
                    ->toggleable(),
                TextColumn::make('attachments_count')
                    ->label(__('app.labels.attachments'))
                    ->counts('attachments')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                TextColumn::make('body_preview')
                    ->label('Body')
                    ->getStateUsing(fn (Note $record): string => $record->plainBody())
                    ->wrap()
                    ->limit(80)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(
                        query: fn (Builder $query, string $search): Builder => $query->where(function (Builder $builder) use ($search): void {
                            $builder
                                ->where('title', 'like', "%{$search}%")
                                ->orWhereHas(
                                    'customFieldValues',
                                    function (Builder $cfv) use ($search): void {
                                        $cfv->whereHas(
                                            'customField',
                                            fn (Builder $cf): Builder => $cf->where('code', NoteField::BODY->value)
                                        )->where(function (Builder $cfvQuery) use ($search): void {
                                            $cfvQuery->where('string_value', 'like', "%{$search}%")
                                                ->orWhere('text_value', 'like', "%{$search}%");
                                        });
                                    }
                                );
                        })
                    ),
                TextColumn::make('creator.name')
                    ->label(__('app.labels.created_by'))
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn (Note $record): string => $record->created_by)
                    ->color(fn (Note $record): string => $record->isSystemCreated() ? 'secondary' : 'primary'),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('app.labels.updated_at'))
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('creation_source')
                    ->label(__('app.labels.creation_source'))
                    ->options(CreationSource::class)
                    ->multiple(),
                SelectFilter::make('category')
                    ->label(__('app.labels.category'))
                    ->options(NoteCategory::options()),
                SelectFilter::make('visibility')
                    ->label(__('app.labels.visibility'))
                    ->options(NoteVisibility::options()),
                SelectFilter::make('is_template')
                    ->label('Templates')
                    ->options([
                        1 => 'Templates',
                        0 => 'Notes',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    \Filament\Actions\Action::make('print')
                        ->label('Print')
                        ->icon('heroicon-o-printer')
                        ->url(fn (Note $record): string => route('notes.print', $record))
                        ->openUrlInNewTab(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(NoteExporter::class),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ManageNotes::route('/'),
        ];
    }

    /**
     * @return Builder<Note>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('attachments')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
