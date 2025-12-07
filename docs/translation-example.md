# Translation Usage Example

This document shows practical examples of how to use translations in your Filament resources.

## Complete Resource Example

```php
<?php

namespace App\Filament\Resources;

use App\Models\Lead;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    // Translate navigation label
    public static function getNavigationLabel(): string
    {
        return __('ui.navigation.leads');
    }

    // Translate model label
    public static function getModelLabel(): string
    {
        return __('app.labels.lead');
    }

    // Translate plural model label
    public static function getPluralModelLabel(): string
    {
        return __('app.labels.leads');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('ui.labels.basic_information'))
                    ->description(__('ui.messages.enter_basic_details'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('ui.labels.name'))
                            ->placeholder(__('ui.placeholders.enter_name'))
                            ->required()
                            ->helperText(__('ui.messages.field_required')),

                        TextInput::make('email')
                            ->label(__('ui.labels.email'))
                            ->placeholder(__('ui.placeholders.enter_email'))
                            ->email()
                            ->required(),

                        TextInput::make('phone')
                            ->label(__('ui.labels.phone'))
                            ->placeholder(__('ui.placeholders.enter_phone'))
                            ->tel(),

                        Select::make('status')
                            ->label(__('ui.labels.status'))
                            ->placeholder(__('ui.placeholders.select_status'))
                            ->options([
                                'new' => __('ui.status.new'),
                                'contacted' => __('ui.status.contacted'),
                                'qualified' => __('ui.status.qualified'),
                            ])
                            ->required(),

                        Select::make('priority')
                            ->label(__('ui.labels.priority'))
                            ->placeholder(__('ui.placeholders.select_priority'))
                            ->options([
                                'low' => __('ui.priority.low'),
                                'medium' => __('ui.priority.medium'),
                                'high' => __('ui.priority.high'),
                                'urgent' => __('ui.priority.urgent'),
                            ]),

                        Textarea::make('notes')
                            ->label(__('ui.labels.notes'))
                            ->placeholder(__('ui.placeholders.enter_notes'))
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('ui.labels.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('ui.labels.email'))
                    ->searchable(),

                TextColumn::make('phone')
                    ->label(__('ui.labels.phone')),

                TextColumn::make('status')
                    ->label(__('ui.labels.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __("ui.status.{$state}")),

                TextColumn::make('created_at')
                    ->label(__('ui.labels.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // Filters with translations
            ])
            ->actions([
                // Actions with translations
            ])
            ->bulkActions([
                // Bulk actions with translations
            ]);
    }
}
```

## Form Components with Translations

### TextInput

```php
TextInput::make('name')
    ->label(__('ui.labels.name'))
    ->placeholder(__('ui.placeholders.enter_name'))
    ->helperText(__('ui.messages.name_helper'))
    ->required()
    ->validationMessages([
        'required' => __('ui.validation.name_required'),
    ])
```

### Select

```php
Select::make('status')
    ->label(__('ui.labels.status'))
    ->placeholder(__('ui.placeholders.select_status'))
    ->options([
        'active' => __('ui.status.active'),
        'inactive' => __('ui.status.inactive'),
    ])
    ->searchPrompt(__('ui.placeholders.search'))
    ->noSearchResultsMessage(__('ui.placeholders.no_results'))
```

### DatePicker

```php
DatePicker::make('due_date')
    ->label(__('ui.labels.due_date'))
    ->placeholder(__('ui.placeholders.select_date'))
    ->displayFormat('d/m/Y')
```

### Textarea

```php
Textarea::make('description')
    ->label(__('ui.labels.description'))
    ->placeholder(__('ui.placeholders.enter_description'))
    ->rows(4)
```

## Table Columns with Translations

### TextColumn

```php
TextColumn::make('name')
    ->label(__('ui.labels.name'))
    ->searchable()
    ->sortable()
    ->description(fn ($record) => __('ui.messages.created_by', [
        'name' => $record->creator->name
    ]))
```

### BadgeColumn

```php
BadgeColumn::make('status')
    ->label(__('ui.labels.status'))
    ->formatStateUsing(fn ($state) => __("ui.status.{$state}"))
    ->colors([
        'success' => 'active',
        'danger' => 'inactive',
    ])
```

### BooleanColumn

```php
BooleanColumn::make('is_active')
    ->label(__('ui.labels.active'))
    ->trueLabel(__('ui.status.active'))
    ->falseLabel(__('ui.status.inactive'))
```

## Actions with Translations

### Create Action

```php
Actions\CreateAction::make()
    ->label(__('ui.actions.create'))
    ->modalHeading(__('ui.actions.create_new', ['model' => __('app.labels.lead')]))
    ->successNotificationTitle(__('ui.messages.success.created'))
```

### Edit Action

```php
Actions\EditAction::make()
    ->label(__('ui.actions.edit'))
    ->modalHeading(__('ui.actions.edit_item', ['model' => __('app.labels.lead')]))
    ->successNotificationTitle(__('ui.messages.success.updated'))
```

### Delete Action

```php
Actions\DeleteAction::make()
    ->label(__('ui.actions.delete'))
    ->modalHeading(__('ui.messages.confirm.delete'))
    ->modalDescription(__('ui.messages.confirm.delete_description'))
    ->modalSubmitActionLabel(__('ui.actions.confirm'))
    ->modalCancelActionLabel(__('ui.actions.cancel'))
    ->successNotificationTitle(__('ui.messages.success.deleted'))
```

## Custom Actions with Translations

```php
Action::make('convert')
    ->label(__('ui.actions.convert'))
    ->icon('heroicon-o-arrow-right')
    ->requiresConfirmation()
    ->modalHeading(__('ui.actions.convert_to_customer'))
    ->modalDescription(__('ui.messages.confirm.convert'))
    ->modalSubmitActionLabel(__('ui.actions.confirm'))
    ->action(function ($record) {
        // Action logic
    })
    ->successNotificationTitle(__('ui.messages.success.converted'))
```

## Notifications with Translations

```php
use Filament\Notifications\Notification;

// Success notification
Notification::make()
    ->title(__('ui.messages.success.created'))
    ->body(__('ui.messages.lead_created_successfully'))
    ->success()
    ->send();

// Error notification
Notification::make()
    ->title(__('ui.messages.error.generic'))
    ->body(__('ui.messages.error.could_not_save'))
    ->danger()
    ->send();

// Info notification
Notification::make()
    ->title(__('ui.messages.info.processing'))
    ->body(__('ui.messages.please_wait'))
    ->info()
    ->send();
```

## Navigation with Translations

```php
// In Resource
protected static ?string $navigationLabel = null;

public static function getNavigationLabel(): string
{
    return __('ui.navigation.leads');
}

// Navigation group
protected static ?string $navigationGroup = null;

public static function getNavigationGroup(): ?string
{
    return __('ui.navigation.sales');
}
```

## Widgets with Translations

```php
class StatsOverviewWidget extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make(__('ui.labels.total_leads'), Lead::count())
                ->description(__('ui.messages.all_time'))
                ->color('success'),

            Card::make(__('ui.labels.new_this_month'), Lead::thisMonth()->count())
                ->description(__('ui.messages.compared_to_last_month'))
                ->color('primary'),
        ];
    }
}
```

## Validation Messages with Translations

```php
// In Form Request
public function messages(): array
{
    return [
        'name.required' => __('ui.validation.name_required'),
        'email.required' => __('ui.validation.email_required'),
        'email.email' => __('ui.validation.email_invalid'),
    ];
}

// In Form Component
TextInput::make('email')
    ->label(__('ui.labels.email'))
    ->required()
    ->email()
    ->validationMessages([
        'required' => __('ui.validation.email_required'),
        'email' => __('ui.validation.email_invalid'),
    ])
```

## Dynamic Translations with Parameters

```php
// With single parameter
__('ui.messages.welcome', ['name' => $user->name])
// Output: "Welcome, John!"

// With multiple parameters
__('ui.messages.showing_results', [
    'from' => 1,
    'to' => 10,
    'total' => 100
])
// Output: "Showing 1 to 10 of 100 results"

// In translation file
'messages' => [
    'welcome' => 'Welcome, :name!',
    'showing_results' => 'Showing :from to :to of :total results',
]
```

## Pluralization

```php
// In translation file (en)
'messages' => [
    'items' => '{0} No items|{1} One item|[2,*] :count items',
]

// Usage
trans_choice('ui.messages.items', 0) // "No items"
trans_choice('ui.messages.items', 1) // "One item"
trans_choice('ui.messages.items', 5) // "5 items"
```
