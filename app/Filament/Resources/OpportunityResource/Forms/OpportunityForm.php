<?php

declare(strict_types=1);

namespace App\Filament\Resources\OpportunityResource\Forms;

use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Relaticle\CustomFields\Facades\CustomFields;

final class OpportunityForm
{
    public static function get(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->placeholder(__('app.placeholders.enter_opportunity_title'))
                    ->columnSpanFull(),
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpan(2),
                Select::make('owner_id')
                    ->relationship('owner', 'name')
                    ->label(__('app.labels.owner'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->default(fn () => auth()->id())
                    ->columnSpan(2),
                Select::make('collaborators')
                    ->relationship('collaborators', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->helperText('Add teammates collaborating on this deal')
                    ->columnSpan(2),
                Select::make('contact_id')
                    ->relationship('contact', 'name')
                    ->searchable()
                    ->preload()
                    ->columnSpan(2),
                Select::make('tags')
                    ->label('Tags')
                    ->relationship(
                        'tags',
                        'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->when(
                            Auth::user()?->currentTeam,
                            fn (Builder $builder, $team): Builder => $builder->where('team_id', $team->getKey())
                        )
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')->required()->maxLength(120),
                        ColorPicker::make('color')->label('Color')->nullable(),
                    ])
                    ->createOptionAction(fn (Action $action): Action => $action->mutateFormDataUsing(
                        fn (array $data): array => [
                            ...$data,
                            'team_id' => Auth::user()?->currentTeam?->getKey(),
                        ]
                    ))
                    ->columnSpan(2),
                CustomFields::form()->forSchema($schema)->build()->columnSpanFull(),
            ])
            ->columns(4);
    }
}
