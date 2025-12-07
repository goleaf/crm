<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\CustomFields\OpportunityField as OpportunityCustomField;
use App\Filament\Resources\OpportunityResource\Forms\OpportunityForm;
use App\Models\Opportunity;
use App\Models\Team;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use League\CommonMark\Exception\InvalidArgumentException;
use Relaticle\CustomFields\Facades\CustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Components\CardFlex;
use Throwable;
use UnitEnum;

final class OpportunitiesBoard extends BoardPage
{
    protected static ?string $navigationLabel = null;

    protected static ?string $title = null;

    protected static ?string $navigationParentItem = null;

    protected static string|null|UnitEnum $navigationGroup = null;

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.board');
    }

    public function getTitle(): string
    {
        return __('app.labels.opportunities');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('app.labels.opportunities');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-view-columns';

    /**
     * Configure the board using the new Filament V4 architecture.
     */
    public function board(Board $board): Board
    {
        return $board
            ->query(
                Opportunity::query()
                    ->leftJoin('custom_field_values as cfv', function (\Illuminate\Database\Query\JoinClause $join): void {
                        $join->on('opportunities.id', '=', 'cfv.entity_id')
                            ->where('cfv.custom_field_id', '=', $this->stageCustomField()->getKey());
                    })
                    ->select('opportunities.*', 'cfv.integer_value')
                    ->with(['company', 'contact'])
                    ->withCustomFieldValues()
            )
            ->recordTitleAttribute('name')
            ->columnIdentifier('cfv.integer_value')
            ->positionIdentifier('order_column')
            ->searchable(['name'])
            ->columns($this->getColumns())
            ->cardSchema(function (Schema $schema): Schema {
                $summaryFields = $this->customFieldEntries($schema, [
                    OpportunityCustomField::AMOUNT->value,
                    OpportunityCustomField::PROBABILITY->value,
                    OpportunityCustomField::CLOSE_DATE->value,
                    OpportunityCustomField::FORECAST_CATEGORY->value,
                ]);

                $nextSteps = CustomFields::infolist()
                    ->forSchema($schema)
                    ->only([OpportunityCustomField::NEXT_STEPS->value])
                    ->hiddenLabels()
                    ->visibleWhenFilled()
                    ->withoutSections()
                    ->values()
                    ->first();

                if ($nextSteps !== null) {
                    $nextSteps = $nextSteps
                        ->columnSpanFull()
                        ->formatStateUsing(fn (string $state): string => str($state)->stripTags()->limit(140)->toString());
                }

                $components = [
                    CardFlex::make([
                        TextEntry::make('company.name')
                            ->label(__('app.labels.company'))
                            ->visible(filled(...)),
                        TextEntry::make('contact.name')
                            ->label(__('app.labels.contact'))
                            ->visible(filled(...)),
                    ]),
                    CardFlex::make($summaryFields),
                ];

                if ($nextSteps !== null) {
                    $components[] = $nextSteps;
                }

                return $schema->components($components);
            })
            ->columnActions([
                CreateAction::make()
                    ->label(__('app.actions.add_opportunity'))
                    ->icon('heroicon-o-plus')
                    ->iconButton()
                    ->modalWidth(Width::Large)
                    ->slideOver(false)
                    ->model(Opportunity::class)
                    ->schema(OpportunityForm::get(...))
                    ->using(function (array $data, array $arguments): Opportunity {
                        $collaboratorIds = $this->pullCollaborators($data);

                        /** @var Team $currentTeam */
                        $currentTeam = Auth::guard('web')->user()->currentTeam;

                        /** @var Opportunity $opportunity */
                        $opportunity = $currentTeam->opportunities()->create($data);

                        $stageField = $this->stageCustomField();
                        $opportunity->saveCustomFieldValue($stageField, $arguments['column']);
                        $opportunity->order_column = $this->getBoardPositionInColumn((string) $arguments['column']);
                        $opportunity->collaborators()->sync($collaboratorIds);

                        return $opportunity;
                    }),
            ])
            ->cardActions([
                Action::make('edit')
                    ->label(__('app.actions.edit'))
                    ->slideOver()
                    ->modalWidth(Width::ExtraLarge)
                    ->icon('heroicon-o-pencil-square')
                    ->schema(OpportunityForm::get(...))
                    ->fillForm(fn (Opportunity $record): array => [
                        'name' => $record->name,
                        'company_id' => $record->company_id,
                        'contact_id' => $record->contact_id,
                        'owner_id' => $record->owner_id,
                        'collaborators' => $record->collaborators->pluck('id')->all(),
                    ])
                    ->action(function (Opportunity $record, array $data): void {
                        $collaboratorIds = $this->pullCollaborators($data);

                        $record->update($data);
                        $record->collaborators()->sync($collaboratorIds);
                    }),
                Action::make('quickUpdate')
                    ->label('Quick update')
                    ->icon('heroicon-o-bolt')
                    ->slideOver()
                    ->modalWidth(Width::Large)
                    ->schema(fn (Schema $schema): Schema => $schema->components(
                        CustomFields::form()
                            ->forSchema($schema)
                            ->only([
                                OpportunityCustomField::PROBABILITY->value,
                                OpportunityCustomField::CLOSE_DATE->value,
                                OpportunityCustomField::FORECAST_CATEGORY->value,
                                OpportunityCustomField::NEXT_STEPS->value,
                            ])
                            ->withoutSections()
                            ->values()
                            ->all()
                    ))
                    ->fillForm(fn (Opportunity $record): array => [
                        'custom_fields' => $this->prefillCustomFields($record, [
                            OpportunityCustomField::PROBABILITY,
                            OpportunityCustomField::CLOSE_DATE,
                            OpportunityCustomField::FORECAST_CATEGORY,
                            OpportunityCustomField::NEXT_STEPS,
                        ]),
                    ])
                    ->action(function (Opportunity $record, array $data): void {
                        $this->updateCustomFields($record, $data, [
                            OpportunityCustomField::PROBABILITY,
                            OpportunityCustomField::CLOSE_DATE,
                            OpportunityCustomField::FORECAST_CATEGORY,
                            OpportunityCustomField::NEXT_STEPS,
                        ]);
                    }),
                Action::make('delete')
                    ->label(__('app.actions.delete'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Opportunity $record): void {
                        $record->delete();
                    }),
            ])
            ->filters([
                SelectFilter::make('companies')
                    ->label(__('app.labels.company'))
                    ->relationship('company', 'name')
                    ->multiple(),
                SelectFilter::make('contacts')
                    ->label(__('app.labels.contact'))
                    ->relationship('contact', 'name')
                    ->multiple(),
                SelectFilter::make('creator_id')
                    ->label(__('app.labels.owner'))
                    ->relationship('creator', 'name')
                    ->multiple(),
                SelectFilter::make('stage')
                    ->label(__('app.labels.status'))
                    ->options($this->stageOptions())
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $builder, $stageId): Builder => $builder->where('cfv.integer_value', $stageId))),
                SelectFilter::make('board_view')
                    ->label('Board view')
                    ->options([
                        'mine' => 'My deals',
                        'closing_30' => 'Closing in 30 days',
                        'stalled' => 'Stalled (14+ days)',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $this->applyBoardView($query, $data['value'] ?? null)),
            ])
            ->filtersFormWidth(Width::Medium);
    }

    /**
     * Move card to new position using Rank-based positioning.
     *
     * @throws Throwable
     */
    public function moveCard(
        string $cardId,
        string $targetColumnId,
        ?string $afterCardId = null,
        ?string $beforeCardId = null
    ): void {
        $board = $this->getBoard();
        $query = $board->getQuery();

        if (! $query instanceof \Illuminate\Database\Eloquent\Builder) {
            throw new InvalidArgumentException('Board query not available');
        }

        $card = (clone $query)->find($cardId);
        if (! $card) {
            throw new InvalidArgumentException("Card not found: {$cardId}");
        }

        // Calculate new position using Rank service
        $newPosition = $this->calculatePositionBetweenCards($afterCardId, $beforeCardId, $targetColumnId);

        // Use transaction for data consistency
        DB::transaction(function () use ($card, $board, $targetColumnId, $newPosition): void {
            $columnIdentifier = $board->getColumnIdentifierAttribute();
            $columnValue = $this->resolveStatusValue($card, $columnIdentifier, $targetColumnId);
            $positionIdentifier = $board->getPositionIdentifierAttribute();

            $card->update([$positionIdentifier => $newPosition]);

            /** @var Opportunity $card */
            $card->saveCustomFieldValue($this->stageCustomField(), $columnValue);
        });

        // Emit success event after successful transaction
        $this->dispatch('kanban-card-moved', [
            'cardId' => $cardId,
            'columnId' => $targetColumnId,
            'position' => $newPosition,
        ]);
    }

    /**
     * Extract collaborator IDs from the form payload to avoid mass-assigning pivot data.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, int|string>
     */
    private function pullCollaborators(array &$data): array
    {
        $collaborators = $data['collaborators'] ?? [];

        unset($data['collaborators']);

        return array_filter($collaborators);
    }

    /**
     * Get columns for the board.
     *
     * @return array<Column>
     *
     * @throws Exception
     */
    private function getColumns(): array
    {
        return $this->stages()->map(fn (array $stage): \Relaticle\Flowforge\Column => Column::make((string) $stage['id'])
            ->color($stage['color'])
            ->label($stage['name'])
        )->toArray();
    }

    private function stageCustomField(): ?CustomField
    {
        /** @var CustomField|null */
        return CustomField::query()
            ->forEntity(Opportunity::class)
            ->where('code', OpportunityCustomField::STAGE)
            ->first();
    }

    /**
     * @return Collection<int, array{id: mixed, custom_field_id: mixed, name: mixed, color: string}>
     */
    private function stages(): Collection
    {
        $field = $this->stageCustomField();

        if (! $field instanceof CustomField) {
            return collect();
        }

        // Check if color options are enabled for this field
        $colorsEnabled = $field->settings->enable_option_colors ?? false;

        return $field->options->map(fn (CustomFieldOption $option): array => [
            'id' => $option->getKey(),
            'custom_field_id' => $option->getAttribute('custom_field_id'),
            'name' => $option->getAttribute('name'),
            'color' => $colorsEnabled ? ($option->settings->color ?? 'gray') : 'gray',
        ]);
    }

    public static function canAccess(): bool
    {
        return (new self)->stageCustomField() instanceof CustomField;
    }

    /**
     * @return array<int|string, string>
     */
    private function stageOptions(): array
    {
        return $this->stages()
            ->mapWithKeys(fn (array $stage): array => [$stage['id'] => $stage['name']])
            ->all();
    }

    /**
     * @return array<int, \Filament\Infolists\Components\Entry>
     */
    private function customFieldEntries(Schema $schema, array $fieldCodes): array
    {
        return CustomFields::infolist()
            ->forSchema($schema)
            ->only($fieldCodes)
            ->hiddenLabels()
            ->visibleWhenFilled()
            ->withoutSections()
            ->values()
            ->all();
    }

    /**
     * @param  list<OpportunityCustomField>  $fields
     */
    private function prefillCustomFields(Opportunity $record, array $fields): array
    {
        $customFields = $this->customFieldsByCodes($fields);

        return collect($fields)
            ->mapWithKeys(function (OpportunityCustomField $field) use ($record, $customFields): array {
                $customField = $customFields[$field->value] ?? null;

                return $customField ? [$field->value => $record->getCustomFieldValue($customField)] : [];
            })
            ->all();
    }

    /**
     * @param  list<OpportunityCustomField>  $fields
     */
    private function updateCustomFields(Opportunity $record, array $data, array $fields): void
    {
        $customFields = $this->customFieldsByCodes($fields);
        $values = $data['custom_fields'] ?? [];

        foreach ($fields as $field) {
            if (! isset($customFields[$field->value])) {
                continue;
            }

            if (! array_key_exists($field->value, $values)) {
                continue;
            }

            $record->saveCustomFieldValue($customFields[$field->value], $values[$field->value]);
        }
    }

    /**
     * @param  list<OpportunityCustomField>  $codes
     * @return array<string, CustomField>
     */
    private function customFieldsByCodes(array $codes): array
    {
        $codeValues = array_map(fn (OpportunityCustomField $field): string => $field->value, $codes);

        return CustomField::query()
            ->forEntity(Opportunity::class)
            ->whereIn('code', $codeValues)
            ->get()
            ->keyBy('code')
            ->all();
    }

    private function applyBoardView(Builder $query, ?string $view): Builder
    {
        return match ($view) {
            'mine' => $query->when(
                Auth::id(),
                fn (Builder $builder, int $userId): Builder => $builder->where('creator_id', $userId)
            ),
            'closing_30' => $this->applyCloseDateWindow($query, 30),
            'stalled' => $query->where('updated_at', '<=', now()->subDays(14)),
            default => $query,
        };
    }

    private function applyCloseDateWindow(Builder $query, int $days): Builder
    {
        $closeDateField = $this->customFieldsByCodes([OpportunityCustomField::CLOSE_DATE])[OpportunityCustomField::CLOSE_DATE->value] ?? null;

        if (! $closeDateField instanceof CustomField) {
            return $query;
        }

        return $query->whereHas(
            'customFieldValues',
            fn (Builder $builder): Builder => $builder
                ->where('custom_field_id', $closeDateField->getKey())
                ->whereDate('date_value', '<=', now()->addDays($days)->toDateString())
        );
    }
}
