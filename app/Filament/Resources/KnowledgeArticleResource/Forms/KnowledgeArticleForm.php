<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeArticleResource\Forms;

use App\Enums\Knowledge\ArticleStatus;
use App\Enums\Knowledge\ArticleVisibility;
use App\Filament\Components\MinimalTabs;
use App\Filament\Support\SlugHelper;
use App\Models\KnowledgeArticle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class KnowledgeArticleForm
{
    public static function get(Schema $schema): Schema
    {
        $lockSlug = static fn (?string $operation, ?KnowledgeArticle $record): bool => $operation === 'edit'
            && $record instanceof \App\Models\KnowledgeArticle
            && in_array($record->status, [ArticleStatus::PUBLISHED, ArticleStatus::ARCHIVED], true);

        return $schema
            ->components([
                MinimalTabs::make('Article')
                    ->tabs([
                        MinimalTabs\Tab::make(__('app.labels.content'))
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                TextInput::make('title')
                                    ->label(__('app.labels.title'))
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(SlugHelper::updateSlug(lockCondition: $lockSlug))
                                    ->columnSpanFull(),
                                TextInput::make('slug')
                                    ->label(__('app.labels.slug'))
                                    ->helperText('Generated automatically from the title; change only if you need a custom URL.')
                                    ->rules(['nullable', 'slug'])
                                    ->maxLength(255)
                                    ->disabled(fn (?string $operation, ?KnowledgeArticle $record): bool => SlugHelper::isLocked($operation, $record, $lockSlug))
                                    ->dehydrated(fn (?string $operation, ?KnowledgeArticle $record): bool => ! SlugHelper::isLocked($operation, $record, $lockSlug)),
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
                        MinimalTabs\Tab::make(__('app.labels.settings'))
                            ->icon('heroicon-o-cog-6-tooth')
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
                                Select::make('taxonomyCategories')
                                    ->label(__('app.labels.category'))
                                    ->options(fn () => \App\Models\Taxonomy::query()
                                        ->where('type', 'knowledge_category')
                                        ->orderBy('name')
                                        ->pluck('name', 'id'))
                                    ->relationship('taxonomyCategories')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->columnSpan(2),
                                Select::make('taxonomyTags')
                                    ->label(__('app.labels.tags'))
                                    ->options(fn () => \App\Models\Taxonomy::query()
                                        ->where('type', 'knowledge_tag')
                                        ->orderBy('name')
                                        ->pluck('name', 'id'))
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->relationship('taxonomyTags')
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
                        MinimalTabs\Tab::make('SEO')
                            ->icon('heroicon-o-magnifying-glass')
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
                        MinimalTabs\Tab::make(__('app.labels.attachments'))
                            ->icon('heroicon-o-paper-clip')
                            ->badge(fn (?KnowledgeArticle $record): ?string => $record?->getMedia('attachments')->count() > 0 ? (string) $record->getMedia('attachments')->count() : null)
                            ->schema([
                                \App\Filament\Support\UploadConstraints::apply(
                                    SpatieMediaLibraryFileUpload::make('attachments')
                                        ->collection('attachments')
                                        ->multiple()
                                        ->preserveFilenames()
                                        ->appendFiles()
                                        ->downloadable()
                                        ->columnSpanFull(),
                                    types: ['documents', 'images', 'archives'],
                                ),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ])
            ->columns(1);
    }
}
