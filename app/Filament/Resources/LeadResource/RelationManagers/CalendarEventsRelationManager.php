<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Enums\CalendarEventStatus;
use App\Enums\CalendarEventType;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Awcodes\BadgeableColumn\Components\Badge;
use Awcodes\BadgeableColumn\Components\BadgeableColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

final class CalendarEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'calendarEvents';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-calendar-days';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('app.labels.activity');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('app.labels.activity_details'))
                ->schema([
                    TextInput::make('title')
                        ->label(__('app.labels.title'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Select::make('type')
                        ->label(__('app.labels.activity'))
                        ->options(self::activityTypeOptions())
                        ->default(CalendarEventType::MEETING)
                        ->native(false)
                        ->required(),
                    Select::make('status')
                        ->label(__('app.labels.status'))
                        ->options(CalendarEventStatus::class)
                        ->default(CalendarEventStatus::SCHEDULED)
                        ->hidden(),
                    Textarea::make('notes')
                        ->label(__('app.labels.description'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make(__('app.labels.schedule'))
                ->schema([
                    DateTimePicker::make('start_at')
                        ->label(__('app.labels.schedule_from'))
                        ->seconds(false)
                        ->required(),
                    DateTimePicker::make('end_at')
                        ->label(__('app.labels.schedule_to'))
                        ->seconds(false)
                        ->required()
                        ->rule('after_or_equal:start_at'),
                    TextInput::make('location')
                        ->label(__('app.labels.location'))
                        ->maxLength(255),
                ])
                ->columns(3),
            Section::make(__('app.labels.participants'))
                ->schema([
                    Repeater::make('attendees')
                        ->label(__('app.labels.participants'))
                        ->schema([
                            TextInput::make('name')
                                ->label(__('app.labels.name'))
                                ->required(),
                            TextInput::make('email')
                                ->label(__('app.labels.email'))
                                ->email(),
                        ])
                        ->defaultItems(0)
                        ->columns(2)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                ])
                ->collapsible(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                BadgeableColumn::make('title')
                    ->label(__('app.labels.title'))
                    ->searchable()
                    ->wrap()
                    ->suffixBadges([
                        Badge::make('type')
                            ->label(fn (Model $record): ?string => $record->type instanceof CalendarEventType
                                ? $record->type->getLabel()
                                : CalendarEventType::tryFrom((string) $record->type)?->getLabel())
                            ->color(fn (Model $record): ?string => $record->type instanceof CalendarEventType
                                ? $record->type->color()
                                : CalendarEventType::tryFrom((string) $record->type)?->color())
                            ->visible(fn (Model $record): bool => filled($record->type)),
                        Badge::make('status')
                            ->label(fn (Model $record): ?string => $record->status instanceof CalendarEventStatus
                                ? $record->status->getLabel()
                                : CalendarEventStatus::tryFrom((string) $record->status)?->getLabel())
                            ->color(fn (Model $record): ?string => $record->status instanceof CalendarEventStatus
                                ? $record->status->getColor()
                                : CalendarEventStatus::tryFrom((string) $record->status)?->getColor())
                            ->visible(fn (Model $record): bool => filled($record->status)),
                    ])
                    ->separator('•'),
                TextColumn::make('start_at')
                    ->label(__('app.labels.schedule_from'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label(__('app.labels.schedule_to'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location')
                    ->label(__('app.labels.location'))
                    ->toggleable(),
                TextColumn::make('creator.name')
                    ->label(__('app.labels.created_by'))
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('app.labels.activity'))
                    ->options(self::activityTypeOptions()),
                Filter::make('activity_filters')
                    ->label(__('app.labels.activity_filters'))
                    ->form([
                        TextInput::make('title')
                            ->label(__('app.labels.title')),
                        Select::make('creator_id')
                            ->label(__('app.labels.created_by'))
                            ->relationship('creator', 'name')
                            ->searchable()
                            ->preload(),
                        DateTimePicker::make('schedule_from')
                            ->label(__('app.labels.schedule_from'))
                            ->seconds(false),
                        DateTimePicker::make('schedule_to')
                            ->label(__('app.labels.schedule_to'))
                            ->seconds(false),
                        DateTimePicker::make('created_from')
                            ->label(__('app.labels.created_from'))
                            ->seconds(false),
                        DateTimePicker::make('created_to')
                            ->label(__('app.labels.created_to'))
                            ->seconds(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['title'] ?? null,
                                fn (Builder $query, string $title): Builder => $query->where('title', 'like', "%{$title}%"),
                            )
                            ->when(
                                $data['creator_id'] ?? null,
                                fn (Builder $query, int $creator): Builder => $query->where('creator_id', $creator),
                            )
                            ->when(
                                $data['schedule_from'] ?? null,
                                fn (Builder $query, string $from): Builder => $query->where('start_at', '>=', $from),
                            )
                            ->when(
                                $data['schedule_to'] ?? null,
                                fn (Builder $query, string $to): Builder => $query->where('start_at', '<=', $to),
                            )
                            ->when(
                                $data['created_from'] ?? null,
                                fn (Builder $query, string $from): Builder => $query->where('created_at', '>=', $from),
                            )
                            ->when(
                                $data['created_to'] ?? null,
                                fn (Builder $query, string $to): Builder => $query->where('created_at', '<=', $to),
                            );
                    }),
            ])
            ->defaultSort('start_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label(__('app.actions.create_event'))
                    ->icon('heroicon-o-plus')
                    ->modalWidth('3xl'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $lead = $this->getOwnerRecord();

        $data['team_id'] = $lead->team_id;
        $data['creator_id'] ??= Auth::id();
        $data['related_id'] = $lead->getKey();
        $data['related_type'] = $lead->getMorphClass();
        $data['status'] ??= CalendarEventStatus::SCHEDULED->value;

        if (empty($data['end_at']) && !empty($data['start_at'])) {
            $data['end_at'] = Date::parse($data['start_at'])->addHour();
        }

        $data['attendees'] ??= [];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->mutateFormDataBeforeCreate($data);
    }

    /**
     * @return array<string, string>
     */
    private static function activityTypeOptions(): array
    {
        return collect([
            CalendarEventType::CALL,
            CalendarEventType::LUNCH,
            CalendarEventType::MEETING,
        ])->mapWithKeys(fn (CalendarEventType $type): array => [$type->value => $type->getLabel()])
            ->all();
    }
}
