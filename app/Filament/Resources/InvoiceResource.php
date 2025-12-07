<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\InvoiceRecurrenceFrequency;
use App\Enums\InvoiceStatus;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\Resources\InvoiceResource\Pages\ViewInvoice;
use App\Filament\Resources\InvoiceResource\RelationManagers\LineItemsRelationManager;
use App\Filament\Resources\InvoiceResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\InvoiceResource\RelationManagers\RemindersRelationManager;
use App\Filament\Resources\InvoiceResource\RelationManagers\StatusHistoriesRelationManager;
use App\Models\Invoice;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $recordTitleAttribute = 'number';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?int $navigationSort = 7;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columns(12)
                    ->schema([
                        Section::make('Invoice Details')
                            ->schema([
                                TextInput::make('number')
                                    ->label('Invoice #')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->helperText('Generated when the invoice is saved'),
                                Select::make('company_id')
                                    ->relationship('company', 'name')
                                    ->label('Company')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                Select::make('contact_id')
                                    ->relationship('contact', 'name')
                                    ->label('Contact')
                                    ->searchable()
                                    ->preload(),
                                Select::make('opportunity_id')
                                    ->relationship('opportunity', 'name')
                                    ->label('Opportunity')
                                    ->searchable()
                                    ->preload(),
                                Select::make('order_id')
                                    ->relationship('order', 'id')
                                    ->label('Order')
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Link this invoice to an order if applicable'),
                                Select::make('status')
                                    ->options(InvoiceStatus::options())
                                    ->enum(InvoiceStatus::class)
                                    ->label('Status')
                                    ->default(InvoiceStatus::DRAFT)
                                    ->native(false),
                                DatePicker::make('issue_date')
                                    ->label('Issue Date')
                                    ->default(now()),
                                DatePicker::make('due_date')
                                    ->label('Due Date')
                                    ->required(),
                                TextInput::make('payment_terms')
                                    ->label('Payment Terms')
                                    ->placeholder('Net 30'),
                                Select::make('currency_code')
                                    ->label('Currency')
                                    ->options(config('company.currency_codes'))
                                    ->default(config('company.default_currency', 'USD'))
                                    ->required()
                                    ->native(false),
                                TextInput::make('fx_rate')
                                    ->label('FX Rate')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(1)
                                    ->step(0.000001)
                                    ->helperText('Snapshot of FX when the invoice was issued'),
                                TextInput::make('late_fee_rate')
                                    ->label('Late Fee %')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.1)
                                    ->helperText('Applied once the invoice is overdue'),
                                Select::make('template_key')
                                    ->label('Template')
                                    ->options(config('invoices.templates'))
                                    ->default(config('invoices.default_template', 'standard'))
                                    ->native(false),
                            ])
                            ->columns(3)
                            ->columnSpan(12),
                        Section::make('Line Items')
                            ->schema([
                                Repeater::make('lineItems')
                                    ->relationship()
                                    ->orderColumn('sort_order')
                                    ->addActionLabel('Add line item')
                                    ->schema([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Textarea::make('description')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                        TextInput::make('quantity')
                                            ->numeric()
                                            ->required()
                                            ->default(1)
                                            ->minValue(0.01)
                                            ->step(0.01),
                                        TextInput::make('unit_price')
                                            ->numeric()
                                            ->required()
                                            ->default(0)
                                            ->minValue(0)
                                            ->step(0.01),
                                        TextInput::make('tax_rate')
                                            ->label('Tax %')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->step(0.01),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ])
                            ->collapsible()
                            ->columnSpan(12),
                        Section::make('Recurring')
                            ->schema([
                                Toggle::make('is_recurring_template')
                                    ->label('Make recurring')
                                    ->inline(false),
                                Select::make('recurring_frequency')
                                    ->label('Frequency')
                                    ->options(InvoiceRecurrenceFrequency::options())
                                    ->enum(InvoiceRecurrenceFrequency::class)
                                    ->native(false)
                                    ->visible(fn ($get): bool => (bool) $get('is_recurring_template')),
                                TextInput::make('recurring_interval')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->step(1)
                                    ->label('Repeat every')
                                    ->visible(fn ($get): bool => (bool) $get('is_recurring_template')),
                                DatePicker::make('recurring_starts_at')
                                    ->label('Starts at')
                                    ->visible(fn ($get): bool => (bool) $get('is_recurring_template')),
                                DatePicker::make('recurring_ends_at')
                                    ->label('Ends at')
                                    ->visible(fn ($get): bool => (bool) $get('is_recurring_template')),
                            ])
                            ->columns(3)
                            ->collapsed()
                            ->columnSpan(12),
                        Section::make('Notes & Terms')
                            ->schema([
                                Textarea::make('notes')
                                    ->rows(3),
                                Textarea::make('terms')
                                    ->rows(4),
                            ])
                            ->columns(2)
                            ->columnSpan(12),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => fn (Invoice $record): bool => $record->status === InvoiceStatus::DRAFT,
                        'info' => fn (Invoice $record): bool => $record->status === InvoiceStatus::SENT,
                        'warning' => fn (Invoice $record): bool => $record->status === InvoiceStatus::PARTIAL,
                        'success' => fn (Invoice $record): bool => $record->status === InvoiceStatus::PAID,
                        'danger' => fn (Invoice $record): bool => $record->status === InvoiceStatus::OVERDUE,
                    ])
                    ->formatStateUsing(fn (InvoiceStatus $state): string => $state->label()),
                TextColumn::make('issue_date')
                    ->date()
                    ->label('Issued')
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date()
                    ->label('Due')
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money(fn (Invoice $record): string => $record->currency_code ?? 'USD')
                    ->sortable(),
                TextColumn::make('balance_due')
                    ->label('Balance')
                    ->money(fn (Invoice $record): string => $record->currency_code ?? 'USD')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(InvoiceStatus::options())
                    ->label('Status'),
                Filter::make('overdue')
                    ->label('Overdue')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereDate('due_date', '<', now())
                        ->where('status', '!=', InvoiceStatus::PAID->value)),
                TernaryFilter::make('is_recurring_template')
                    ->label('Recurring'),
            ])
            ->recordActions([
                ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                    EditAction::make(),
                    \Filament\Actions\RestoreAction::make(),
                    \Filament\Actions\DeleteAction::make(),
                    \Filament\Actions\ForceDeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            LineItemsRelationManager::class,
            PaymentsRelationManager::class,
            RemindersRelationManager::class,
            StatusHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'view' => ViewInvoice::route('/{record}'),
        ];
    }

    /**
     * @return Builder<Invoice>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
