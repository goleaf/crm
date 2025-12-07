<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Filament\Resources\LeadResource\Forms\LeadForm;
use App\Models\Lead;
use App\Models\Team;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use League\CommonMark\Exception\InvalidArgumentException;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Components\CardFlex;
use Throwable;
use UnitEnum;

final class LeadsBoard extends BoardPage
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
        return __('app.labels.leads');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('app.labels.leads');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-view-columns';

    public function board(Board $board): Board
    {
        return $board
            ->query(
                Lead::query()
                    ->whereNull('deleted_at')
                    ->with(['company', 'assignedTo'])
            )
            ->recordTitleAttribute('name')
            ->columnIdentifier('status')
            ->positionIdentifier('order_column')
            ->searchable(['name', 'company_name', 'email'])
            ->columns($this->columns())
            ->cardSchema(fn (Schema $schema): Schema => $schema->components([
                CardFlex::make([
                    TextEntry::make('company_name')
                        ->label(__('app.labels.company'))
                        ->visible(filled(...)),
                    TextEntry::make('email')
                        ->label(__('app.labels.email'))
                        ->copyable()
                        ->visible(filled(...)),
                    TextEntry::make('phone')
                        ->label(__('app.labels.phone'))
                        ->copyable()
                        ->visible(filled(...)),
                ]),
            ]))
            ->columnActions([
                CreateAction::make()
                    ->label(__('app.actions.add_lead'))
                    ->icon('heroicon-o-plus')
                    ->iconButton()
                    ->modalWidth(Width::ThreeExtraLarge)
                    ->model(Lead::class)
                    ->schema(LeadForm::get(...))
                    ->using(function (array $data, array $arguments): Lead {
                        /** @var Team $currentTeam */
                        $currentTeam = Auth::guard('web')->user()->currentTeam;

                        $status = LeadStatus::tryFrom((string) ($arguments['column'] ?? '')) ?? LeadStatus::tryFrom((string) ($data['status'] ?? '')) ?? LeadStatus::NEW;

                        /** @var Lead $lead */
                        $lead = $currentTeam->leads()->create([
                            ...$data,
                            'status' => $status,
                            'order_column' => $this->getBoardPositionInColumn($status->value),
                        ]);

                        return $lead;
                    }),
            ])
            ->cardActions([
                Action::make('edit')
                    ->label(__('app.actions.edit'))
                    ->slideOver()
                    ->modalWidth(Width::ThreeExtraLarge)
                    ->icon('heroicon-o-pencil-square')
                    ->schema(LeadForm::get(...))
                    ->fillForm(fn (Lead $record): array => $record->toArray())
                    ->action(function (Lead $record, array $data): void {
                        $record->update($data);
                    }),
                Action::make('delete')
                    ->label(__('app.actions.delete'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Lead $record): void {
                        $record->delete();
                    }),
            ])
            ->filters([
                SelectFilter::make('assigned_to_id')
                    ->label(__('app.labels.assignee'))
                    ->relationship('assignedTo', 'name')
                    ->multiple(),
                SelectFilter::make('source')
                    ->label(__('app.labels.source'))
                    ->options(LeadSource::options())
                    ->multiple(),
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

        /** @var Lead|null $card */
        $card = (clone $query)->find($cardId);
        if (! $card) {
            throw new InvalidArgumentException("Card not found: {$cardId}");
        }

        $status = LeadStatus::tryFrom($targetColumnId);
        if (! $status instanceof LeadStatus) {
            throw new InvalidArgumentException("Invalid lead status: {$targetColumnId}");
        }

        $newPosition = $this->calculatePositionBetweenCards($afterCardId, $beforeCardId, $targetColumnId);

        DB::transaction(function () use ($card, $status, $newPosition): void {
            $card->update([
                'order_column' => $newPosition,
                'status' => $status,
            ]);
        });

        $this->dispatch('kanban-card-moved', [
            'cardId' => $cardId,
            'columnId' => $targetColumnId,
            'position' => $newPosition,
        ]);
    }

    /**
     * @return array<Column>
     */
    private function columns(): array
    {
        return collect(LeadStatus::cases())
            ->map(fn (LeadStatus $status): Column => Column::make($status->value)
                ->label($status->getLabel())
                ->color($status->color()))
            ->toArray();
    }
}
