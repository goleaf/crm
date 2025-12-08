<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\FeatureFlagResource\Pages;
use App\Models\FeatureFlagSegment;
use App\Models\Team;
use App\Support\Helpers\ArrayHelper;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\Unique;

final class FeatureFlagResource extends Resource
{
    protected static ?string $model = FeatureFlagSegment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cursor-arrow-ripple';

    protected static ?int $navigationSort = 910;

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function canViewAny(): bool
    {
        $tenant = Filament::getTenant();
        $user = Filament::auth()->user();

        return $tenant !== null
            && $user !== null
            && $user->hasVerifiedEmail()
            && $user->hasTeamRole($tenant, 'admin');
    }

    public static function getNavigationGroup(): ?string
    {
        return __(config('filament-feature-flags.panel.group'));
    }

    public static function getNavigationLabel(): string
    {
        return __(config('filament-feature-flags.panel.label'));
    }

    public static function getPluralModelLabel(): string
    {
        return __(config('filament-feature-flags.panel.title'));
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.feature_flag_segment');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $tenantId = Filament::getTenant()?->getKey();

        if ($tenantId !== null) {
            $query->whereJsonContains('values', $tenantId);
        }

        return $query->latest();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('feature')
                ->label(__('app.labels.feature'))
                ->required()
                ->options(FeatureFlagSegment::featureOptionsList())
                ->searchable()
                ->columnSpanFull(),

            Select::make('scope')
                ->label(__('app.labels.feature_scope'))
                ->default(config('filament-feature-flags.segments.0.column'))
                ->disabled()
                ->dehydrated()
                ->columnSpanFull()
                ->options([
                    config('filament-feature-flags.segments.0.column') => __('app.labels.team'),
                ]),

            Select::make('values')
                ->label(__('app.labels.feature_flag_targets'))
                ->required()
                ->multiple()
                ->preload()
                ->searchable()
                ->default(self::defaultTarget())
                ->options(fn (): array => self::teamOptions())
                ->getSearchResultsUsing(fn (string $search): array => self::teamOptions($search))
                ->getOptionLabelsUsing(fn (array $values): array => Team::query()
                    ->whereKey($values)
                    ->pluck('name', 'id')
                    ->all())
                ->columnSpanFull(),

            Select::make('active')
                ->label(__('app.labels.feature_flag_status'))
                ->options([
                    true => __('app.actions.activate'),
                    false => __('app.actions.deactivate'),
                ])
                ->default(true)
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule, Get $get) => $rule
                        ->where('feature', $get('feature'))
                        ->where('scope', $get('scope'))
                        ->where('active', $get('active'))
                )
                ->required()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('app.labels.feature'))
                    ->searchable(['feature'])
                    ->sortable(['feature']),

                Tables\Columns\TextColumn::make('values')
                    ->label(__('app.labels.feature_flag_targets'))
                    ->badge()
                    ->wrap()
                    ->formatStateUsing(fn (array|string|null $state): string => self::formatTargets($state)),

                Tables\Columns\TextColumn::make('active')
                    ->label(__('app.labels.feature_flag_status'))
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn (bool $state): string => $state
                        ? __('app.labels.activated')
                        : __('app.labels.deactivated')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('app.labels.updated_at'))
                    ->dateTime()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('feature')
                    ->label(__('app.labels.feature'))
                    ->options(FeatureFlagSegment::featureOptionsList()),

                Tables\Filters\TernaryFilter::make('active')
                    ->label(__('app.labels.feature_flag_status')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(__('app.actions.edit'))
                    ->after(fn (FeatureFlagSegment $record) => event(new \Stephenjude\FilamentFeatureFlag\Events\FeatureSegmentModified($record, Filament::auth()->user()))),
                Tables\Actions\DeleteAction::make()
                    ->label(__('app.actions.delete'))
                    ->before(fn (FeatureFlagSegment $record) => event(new \Stephenjude\FilamentFeatureFlag\Events\RemovingFeatureSegment($record, Filament::auth()->user())))
                    ->after(fn () => event(new \Stephenjude\FilamentFeatureFlag\Events\FeatureSegmentRemoved(Filament::auth()->user()))),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFeatureFlags::route('/'),
        ];
    }

    private static function defaultTarget(): array
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Team) {
            return [];
        }

        return [$tenant->getKey()];
    }

    private static function teamOptions(?string $search = null): array
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Team) {
            return [];
        }

        $query = Team::query()->whereKey($tenant->getKey());

        if ($search !== null && $search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->pluck('name', 'id')->all();
    }

    private static function formatTargets(array|string|null $state): string
    {
        if (in_array($state, [null, '', []], true)) {
            return '—';
        }

        $values = Collection::wrap($state)->filter()->all();

        if ($values === []) {
            return '—';
        }

        $names = Team::query()
            ->whereKey($values)
            ->pluck('name', 'id')
            ->all();

        $labels = Collection::make($values)
            ->map(fn (int|string $id): string => $names[(int) $id] ?? (string) $id)
            ->all();

        return ArrayHelper::joinList($labels) ?? '—';
    }
}
