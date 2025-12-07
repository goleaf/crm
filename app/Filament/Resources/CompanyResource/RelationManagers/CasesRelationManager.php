<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyResource\RelationManagers;

use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\CaseType;
use App\Filament\Resources\SupportCaseResource;
use App\Filament\Resources\SupportCaseResource\Forms\SupportCaseForm;
use App\Models\SupportCase;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

final class CasesRelationManager extends RelationManager
{
    protected static string $relationship = 'cases';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-lifebuoy';

    public function form(Schema $schema): Schema
    {
        return SupportCaseForm::get($schema, ['company_id']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->columns([
                TextColumn::make('case_number')
                    ->label(__('app.labels.case_number'))
                    ->toggleable(),
                TextColumn::make('subject')
                    ->label(__('app.labels.title'))
                    ->limit(40)
                    ->wrap(),
                TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->color(fn (CaseStatus|string|null $state): string => $state instanceof CaseStatus ? $state->getColor() : (CaseStatus::tryFrom((string) $state)?->getColor() ?? 'gray'))
                    ->formatStateUsing(fn (CaseStatus|string|null $state): string => $state instanceof CaseStatus ? $state->getLabel() : (CaseStatus::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state))),
                TextColumn::make('priority')
                    ->label(__('app.labels.priority'))
                    ->badge()
                    ->color(fn (CasePriority|string|null $state): string => $state instanceof CasePriority ? $state->getColor() : (CasePriority::tryFrom((string) $state)?->getColor() ?? 'gray'))
                    ->formatStateUsing(fn (CasePriority|string|null $state): string => $state instanceof CasePriority ? $state->getLabel() : (CasePriority::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state))),
                TextColumn::make('type')
                    ->label(__('app.labels.type'))
                    ->badge()
                    ->formatStateUsing(fn (CaseType|string|null $state): string => $state instanceof CaseType ? $state->getLabel() : (CaseType::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state))),
                TextColumn::make('assignee.name')
                    ->label(__('app.labels.assignee'))
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-plus')->size(Size::Small),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->url(fn (SupportCase $record): string => SupportCaseResource::getUrl('view', [$record]))
                        ->openUrlInNewTab(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = $this->ownerRecord->getKey();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mutateFormDataBeforeSave(array $data): array
    {
        $data['company_id'] = $this->ownerRecord->getKey();

        return $data;
    }
}
