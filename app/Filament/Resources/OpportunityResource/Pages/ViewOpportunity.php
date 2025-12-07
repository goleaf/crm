<?php

declare(strict_types=1);

namespace App\Filament\Resources\OpportunityResource\Pages;

use App\Enums\CustomFields\OpportunityField;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\OpportunityResource;
use App\Filament\Resources\PeopleResource;
use App\Models\Opportunity;
use App\Models\Order;
use App\Services\Opportunities\OpportunityMetricsService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Relaticle\CustomFields\Facades\CustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;

final class ViewOpportunity extends ViewRecord
{
    protected static string $resource = OpportunityResource::class;

    /**
     * @var array<string, CustomField|null>
     */
    private array $customFieldCache = [];

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('mark_won')
                    ->label('Mark Closed Won')
                    ->icon('heroicon-o-trophy')
                    ->color('success')
                    ->form([
                        Textarea::make('next_steps')
                            ->label('Next Steps')
                            ->rows(2),
                        Textarea::make('outcome_notes')
                            ->label('Win Notes')
                            ->rows(3),
                    ])
                    ->action(function (Opportunity $record, array $data): void {
                        $this->applyOutcome($record, $data, true);
                    }),
                Action::make('mark_lost')
                    ->label('Mark Closed Lost')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('competitors')
                            ->label('Competitors')
                            ->rows(2),
                        Textarea::make('outcome_notes')
                            ->label('Loss Reason')
                            ->rows(3)
                            ->required(),
                        Textarea::make('next_steps')
                            ->label('Next Steps')
                            ->rows(2),
                    ])
                    ->action(function (Opportunity $record, array $data): void {
                        $this->applyOutcome($record, $data, false);
                    }),
                Action::make('convert_to_order')
                    ->label('Convert to Order')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->form([
                        Textarea::make('order_notes')
                            ->label('Order Notes')
                            ->rows(3),
                    ])
                    ->action(function (Opportunity $record, array $data): void {
                        $this->convertToOrder($record, $data);
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                Flex::make([
                    TextEntry::make('name')->grow(true),
                    TextEntry::make('company.name')
                        ->label(__('app.labels.company'))
                        ->color('primary')
                        ->url(fn (Opportunity $record): ?string => $record->company ? CompanyResource::getUrl('view', [$record->company]) : null)
                        ->grow(false),
                    TextEntry::make('contact.name')
                        ->label('Point of Contact')
                        ->color('primary')
                        ->url(fn (Opportunity $record): ?string => $record->contact ? PeopleResource::getUrl('view', [$record->contact]) : null)
                        ->grow(false),
                    TextEntry::make('owner.name')
                        ->label(__('app.labels.owner'))
                        ->grow(false),
                ]),
                CustomFields::infolist()->forSchema($schema)->build()->columnSpanFull(),
            ])
                ->columnSpanFull(),
        ]);
    }

    /**
     * Apply win/loss updates to the opportunity and its custom fields.
     *
     * @param  array<string, mixed>  $data
     */
    private function applyOutcome(Opportunity $record, array $data, bool $isWin): void
    {
        $stageName = $isWin ? 'Closed Won' : 'Closed Lost';
        $probability = $isWin ? 100 : 0;
        $forecastCategory = 'Closed';

        if (! $this->saveSelectOption($record, OpportunityField::STAGE, $stageName)) {
            return;
        }

        $this->saveSelectOption($record, OpportunityField::FORECAST_CATEGORY, $forecastCategory);
        $this->saveValue($record, OpportunityField::PROBABILITY, $probability);
        $this->saveValue($record, OpportunityField::NEXT_STEPS, $data['next_steps'] ?? null);
        $this->saveValue($record, OpportunityField::OUTCOME_NOTES, $data['outcome_notes'] ?? null);

        if (! $isWin) {
            $this->saveValue($record, OpportunityField::COMPETITORS, $data['competitors'] ?? null);
        }

        $record->forceFill([
            'closed_at' => now(),
            'closed_by_id' => auth()->id(),
        ])->save();

        $record->activities()->create([
            'team_id' => $record->team_id,
            'event' => $isWin ? 'closed_won' : 'closed_lost',
            'causer_id' => auth()->id(),
            'changes' => [
                'stage' => $stageName,
                'probability' => $probability,
                'forecast_category' => $forecastCategory,
            ],
        ]);

        Notification::make()
            ->title($isWin ? 'Deal marked Closed Won' : 'Deal marked Closed Lost')
            ->success()
            ->send();
    }

    /**
     * Create an order from the opportunity with a single line item for the deal amount.
     *
     * @param  array<string, mixed>  $data
     */
    private function convertToOrder(Opportunity $record, array $data): void
    {
        if ($record->order()->exists()) {
            Notification::make()
                ->title('Order already exists')
                ->body('This deal has already been converted to an order.')
                ->warning()
                ->send();

            return;
        }

        /** @var OpportunityMetricsService $metrics */
        $metrics = app(OpportunityMetricsService::class);

        $amount = $metrics->amount($record);
        $expectedClose = $metrics->expectedCloseDate($record);

        $order = new Order([
            'team_id' => $record->team_id,
            'creator_id' => auth()->id(),
            'company_id' => $record->company_id,
            'contact_id' => $record->contact_id,
            'opportunity_id' => $record->getKey(),
            'ordered_at' => now(),
            'fulfillment_due_at' => $expectedClose,
            'notes' => $data['order_notes'] ?? null,
        ]);

        $order->save();

        if ($amount !== null && $amount > 0) {
            $order->lineItems()->create([
                'team_id' => $record->team_id,
                'name' => $record->name,
                'description' => $data['order_notes'] ?? 'Converted from opportunity',
                'quantity' => 1,
                'unit_price' => $amount,
                'tax_rate' => 0,
            ]);
        }

        $record->activities()->create([
            'team_id' => $record->team_id,
            'event' => 'converted_to_order',
            'causer_id' => auth()->id(),
            'changes' => [
                'order_id' => $order->getKey(),
                'order_number' => $order->number,
                'amount' => $amount,
            ],
        ]);

        Notification::make()
            ->title('Order created')
            ->body(fn (): string => $order->number ? "Order #{$order->number}" : 'Order successfully created.')
            ->success()
            ->send();
    }

    private function saveValue(Opportunity $record, OpportunityField $field, mixed $value): void
    {
        $customField = $this->customField($field->value);

        if (! $customField instanceof CustomField) {
            return;
        }

        $record->saveCustomFieldValue($customField, $value);
    }

    private function saveSelectOption(Opportunity $record, OpportunityField $field, string $optionName): bool
    {
        $customField = $this->customField($field->value);

        if (! $customField instanceof CustomField) {
            Notification::make()
                ->title("Missing {$field->getDisplayName()} field")
                ->body('Add the field to continue.')
                ->danger()
                ->send();

            return false;
        }

        $option = $this->optionForName($customField, $optionName);

        if (! $option instanceof CustomFieldOption) {
            Notification::make()
                ->title('Stage option not found')
                ->body("Add the {$optionName} stage option to proceed.")
                ->danger()
                ->send();

            return false;
        }

        $record->saveCustomFieldValue($customField, $option->getKey());

        return true;
    }

    private function optionForName(CustomField $field, string $optionName): ?CustomFieldOption
    {
        $field->loadMissing('options');

        return $field->options->firstWhere('name', $optionName);
    }

    private function customField(string $code): ?CustomField
    {
        if (! array_key_exists($code, $this->customFieldCache)) {
            $this->customFieldCache[$code] = CustomField::query()
                ->forEntity(Opportunity::class)
                ->where('code', $code)
                ->first();
        }

        return $this->customFieldCache[$code];
    }
}
