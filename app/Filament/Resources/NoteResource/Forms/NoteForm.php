<?php

declare(strict_types=1);

namespace App\Filament\Resources\NoteResource\Forms;

use App\Enums\CustomFields\NoteField;
use App\Enums\NoteCategory;
use App\Enums\NoteVisibility;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Relaticle\CustomFields\Facades\CustomFields;

final class NoteForm
{
    /**
     * @param  array<string>  $excludeFields  Fields to exclude from the form.
     * @return Schema The modified form instance with the schema applied.
     *
     * @throws \Exception
     */
    public static function get(Schema $schema, array $excludeFields = []): Schema
    {
        $templates = collect(config('notes.templates', []))->keyBy('key');

        $components = [
            Select::make('note_template')
                ->label('Template')
                ->options($templates->mapWithKeys(fn (array $template): array => [$template['key'] => $template['label']])->toArray())
                ->placeholder('Select a template')
                ->dehydrated(false)
                ->live()
                ->afterStateUpdated(function (Set $set, ?string $state) use ($templates): void {
                    $template = $state ? $templates->get($state) : null;

                    if ($template === null) {
                        return;
                    }

                    $set('title', $template['title'] ?? '');
                    $set('category', $template['category'] ?? NoteCategory::GENERAL->value);
                    $set('visibility', $template['visibility'] ?? NoteVisibility::INTERNAL->value);
                    if (isset($template['body'])) {
                        $set('custom_fields.'.NoteField::BODY->value, $template['body']);
                    }
                })
                ->columnSpanFull(),
            TextInput::make('title')
                ->label(__('app.labels.title'))
                ->rules(['max:255'])
                ->columnSpanFull()
                ->required(),
            Select::make('category')
                ->label('Category')
                ->options(NoteCategory::options())
                ->default(NoteCategory::GENERAL->value)
                ->searchable(),
            Select::make('visibility')
                ->label('Visibility')
                ->options(NoteVisibility::options())
                ->default(NoteVisibility::INTERNAL->value)
                ->required(),
            Toggle::make('is_template')
                ->label('Save as template')
                ->inline(false)
                ->helperText('Mark this note as a reusable template for future entries.'),
        ];

        if (! in_array('companies', $excludeFields)) {
            $components[] = Select::make('companies')
                ->label(__('app.labels.companies'))
                ->multiple()
                ->relationship('companies', 'name');
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

        if (! in_array('opportunities', $excludeFields)) {
            $components[] = Select::make('opportunities')
                ->label(__('app.labels.opportunities'))
                ->multiple()
                ->relationship('opportunities', 'name')
                ->nullable();
        }

        if (! in_array('cases', $excludeFields)) {
            $components[] = Select::make('cases')
                ->label(__('app.labels.cases'))
                ->multiple()
                ->relationship('cases', 'subject')
                ->nullable();
        }

        if (! in_array('tasks', $excludeFields)) {
            $components[] = Select::make('tasks')
                ->label(__('app.labels.tasks'))
                ->multiple()
                ->relationship('tasks', 'title')
                ->nullable();
        }

        $components[] = SpatieMediaLibraryFileUpload::make('attachments')
            ->collection('attachments')
            ->label('Attachments')
            ->preserveFilenames()
            ->appendFiles()
            ->multiple()
            ->downloadable()
            ->columnSpanFull();

        $components[] = CustomFields::form()->forSchema($schema)->build()
            ->columnSpanFull()
            ->columns(1);

        return $schema
            ->components($components)
            ->columns(2);
    }
}
