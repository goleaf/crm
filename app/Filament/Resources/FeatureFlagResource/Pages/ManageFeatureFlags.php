<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeatureFlagResource\Pages;

use App\Filament\Resources\FeatureFlagResource;
use App\Models\FeatureFlagSegment;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;

final class ManageFeatureFlags extends ManageRecords
{
    protected static string $resource = FeatureFlagResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return FeatureFlagResource::canViewAny();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalWidth('md')
                ->label(__('app.actions.segment_feature'))
                ->modalHeading(__('app.labels.feature_flag_segment'))
                ->modalSubmitActionLabel(__('app.actions.save'))
                ->after(fn (FeatureFlagSegment $record) => $this->afterCreate($record)),

            Action::make('activate_for_all')
                ->label(__('app.actions.activate_for_all'))
                ->modalHeading(__('app.actions.activate_for_all'))
                ->modalDescription(__('app.messages.feature_flags.activate_for_all'))
                ->schema([
                    Select::make('feature')
                        ->label(__('app.labels.feature'))
                        ->required()
                        ->options(FeatureFlagSegment::featureOptionsList())
                        ->columnSpanFull(),
                ])
                ->modalSubmitActionLabel(__('app.actions.activate'))
                ->action(fn (array $data) => $this->activateForAll($data['feature'])),

            Action::make('deactivate_for_all')
                ->label(__('app.actions.deactivate_for_all'))
                ->modalHeading(__('app.actions.deactivate_for_all'))
                ->modalDescription(__('app.messages.feature_flags.deactivate_for_all'))
                ->schema([
                    Select::make('feature')
                        ->label(__('app.labels.feature'))
                        ->required()
                        ->options(FeatureFlagSegment::featureOptionsList())
                        ->columnSpanFull(),
                ])
                ->modalSubmitActionLabel(__('app.actions.deactivate'))
                ->color('danger')
                ->action(fn (array $data) => $this->deactivateForAll($data['feature'])),

            Action::make('purge_features')
                ->label(__('app.actions.purge_feature_flags'))
                ->modalHeading(__('app.actions.purge_feature_flags'))
                ->modalDescription(__('app.messages.feature_flags.purge'))
                ->schema([
                    Select::make('feature')
                        ->label(__('app.labels.feature'))
                        ->selectablePlaceholder(false)
                        ->options(
                            [null => __('app.labels.all_features')]
                            + FeatureFlagSegment::featureOptionsList()
                        )
                        ->columnSpanFull(),
                ])
                ->modalSubmitActionLabel(__('app.actions.purge'))
                ->color('danger')
                ->action(fn (array $data) => $this->purgeFeatures($data['feature'] ?? null)),
        ];
    }

    private function activateForAll(string $feature): void
    {
        Feature::activateForEveryone($feature);

        Notification::make()
            ->success()
            ->title(__('app.messages.feature_flags.completed'))
            ->body(__('app.messages.feature_flags.activated_for_all', [
                'feature' => $this->featureTitle($feature),
            ]))
            ->send();

        event(new \Stephenjude\FilamentFeatureFlag\Events\FeatureActivatedForAll($feature, Filament::auth()->user()));
    }

    private function deactivateForAll(string $feature): void
    {
        Feature::deactivateForEveryone($feature);

        Notification::make()
            ->success()
            ->title(__('app.messages.feature_flags.completed'))
            ->body(__('app.messages.feature_flags.deactivated_for_all', [
                'feature' => $this->featureTitle($feature),
            ]))
            ->send();

        event(new \Stephenjude\FilamentFeatureFlag\Events\FeatureDeactivatedForAll($feature, Filament::auth()->user()));
    }

    private function purgeFeatures(?string $feature): void
    {
        Feature::purge($feature);

        Notification::make()
            ->success()
            ->title(__('app.messages.feature_flags.completed'))
            ->body(__('app.messages.feature_flags.purged'))
            ->send();
    }

    private function afterCreate(FeatureFlagSegment $featureSegment): void
    {
        Feature::purge($featureSegment->feature);

        event(new \Stephenjude\FilamentFeatureFlag\Events\FeatureSegmentCreated($featureSegment, Filament::auth()->user()));
    }

    private function featureTitle(string $feature): string
    {
        return Str::of(class_basename($feature))->headline()->toString();
    }
}
