<?php

declare(strict_types=1);

namespace App\Filament\Resources\TaskResource\Forms;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Relaticle\CustomFields\Facades\CustomFields;

final class TaskForm
{
    /**
     * @param  array<string>  $excludeFields
     *
     * @throws \Exception
     */
    public static function get(Schema $schema, array $excludeFields = []): Schema
    {
        $components = [
            TextInput::make('title')
                ->required()
                ->columnSpanFull(),
        ];

        $components[] = Select::make('parent_id')
            ->label('Parent task')
            ->relationship('parent', 'title')
            ->searchable()
            ->preload()
            ->nullable();

        if (! in_array('companies', $excludeFields)) {
            $components[] = Select::make('companies')
                ->label(__('app.labels.companies'))
                ->multiple()
                ->relationship('companies', 'name')
                ->columnSpanFull();
        }

        if (! in_array('people', $excludeFields)) {
            $components[] = Select::make('people')
                ->label(__('app.labels.people'))
                ->multiple()
                ->relationship('people', 'name')
                ->nullable();
        }

        if (! in_array('leads', $excludeFields)) {
            $components[] = Select::make('leads')
                ->label(__('app.labels.leads'))
                ->multiple()
                ->relationship('leads', 'name')
                ->nullable();
        }

        $components[] = Select::make('assignees')
            ->label(__('app.labels.assignees'))
            ->multiple()
            ->relationship('assignees', 'name')
            ->nullable();

        $components[] = Select::make('dependencies')
            ->label('Dependencies')
            ->helperText('Tasks that must be completed first')
            ->multiple()
            ->relationship('dependencies', 'title')
            ->searchable()
            ->preload()
            ->columnSpanFull();

        $components[] = Select::make('taskTaxonomies')
            ->label(__('app.labels.categories'))
            ->options(fn () => \App\Models\Taxonomy::query()
                ->where('type', 'task_category')
                ->orderBy('name')
                ->pluck('name', 'id'))
            ->multiple()
            ->preload()
            ->searchable()
            ->relationship('taskTaxonomies')
            ->columnSpanFull();

        $components[] = CustomFields::form()->forSchema($schema)->except($excludeFields)->build()->columnSpanFull();

        return $schema
            ->components($components)
            ->columns(2);
    }
}
