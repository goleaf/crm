<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeArticleResource\Forms;

use App\Enums\Knowledge\ArticleStatus;
use App\Enums\Knowledge\ArticleVisibility;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class KnowledgeArticleForm
{
    public static function get(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Article Details')
                    ->schema([
                        TextInput::make('title')
                            ->label(__('app.labels.title'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('slug')
                            ->label(__('app.labels.slug'))
                            ->helperText('Generated automatically from the title; change only if you need a custom URL.')
                            ->maxLength(255),
                        Textarea::make('summary')
                            ->label(__('app.labels.summary'))
                            ->rows(3)
                            ->maxLength(1024)
                            ->columnSpanFull(),
                        RichEditor::make('content')
                            ->label(__('app.labels.content'))
                            ->disableToolbarButtons(['attachFiles'])
                            ->columnSpanFull()
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Workflow & Visibility')
                    ->schema([
                        Select::make('status')
                            ->label(__('app.labels.status'))
                            ->options(ArticleStatus::class)
                            ->default(ArticleStatus::DRAFT)
                            ->required(),
                        Select::make('visibility')
                            ->label(__('app.labels.visibility'))
                            ->options(ArticleVisibility::class)
                            ->default(ArticleVisibility::INTERNAL)
                            ->required(),
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->label(__('app.labels.category'))
                            ->searchable()
                            ->preload()
                            ->columnSpan(2),
                        Select::make('tags')
                            ->relationship('tags', 'name')
                            ->label(__('app.labels.tags'))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->columnSpan(2),
                        Select::make('author_id')
                            ->relationship('author', 'name')
                            ->label(__('app.labels.author'))
                            ->searchable()
                            ->preload(),
                        Select::make('approver_id')
                            ->relationship('approver', 'name')
                            ->label(__('app.labels.approver'))
                            ->searchable()
                            ->preload(),
                        DateTimePicker::make('review_due_at')
                            ->label(__('app.labels.review_due_at'))
                            ->seconds(false),
                        Toggle::make('allow_comments')
                            ->label(__('app.labels.allow_comments'))
                            ->default(true),
                        Toggle::make('allow_ratings')
                            ->label(__('app.labels.allow_ratings'))
                            ->default(true),
                        Toggle::make('is_featured')
                            ->label(__('app.labels.featured'))
                            ->default(false),
                    ])
                    ->columns(2),
                Section::make('SEO')
                    ->schema([
                        TextInput::make('meta_title')
                            ->label(__('app.labels.meta_title'))
                            ->maxLength(255),
                        Textarea::make('meta_description')
                            ->label(__('app.labels.meta_description'))
                            ->rows(3)
                            ->maxLength(1024),
                        TagsInput::make('meta_keywords')
                            ->label(__('app.labels.meta_keywords'))
                            ->placeholder('Add keyword'),
                    ])
                    ->columns(2),
                Section::make(__('app.labels.attachments'))
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('attachments')
                            ->collection('attachments')
                            ->multiple()
                            ->preserveFilenames()
                            ->appendFiles()
                            ->downloadable()
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }
}
