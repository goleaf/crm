<?php

declare(strict_types=1);

namespace App\Filament\Resources\PeopleResource\Pages;

use App\Enums\CustomFields\PeopleField;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\PeopleResource;
use App\Models\People;
use App\Models\Tag;
use App\Support\Helpers\ArrayHelper;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\TextSize;
use Relaticle\CustomFields\Facades\CustomFields;
use Relaticle\CustomFields\Models\CustomField;

final class ViewPeople extends ViewRecord
{
    protected static string $resource = PeopleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                EditAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            // Header Section with Avatar and Basic Info
            Section::make()
                ->schema([
                    Flex::make([
                        ImageEntry::make('avatar')
                            ->label('')
                            ->height(80)
                            ->circular()
                            ->grow(false),
                        Grid::make(1)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('')
                                    ->size(TextSize::ExtraLarge)
                                    ->weight('bold'),
                                TextEntry::make('job_title')
                                    ->label('')
                                    ->size(TextSize::Medium)
                                    ->color('gray')
                                    ->icon('heroicon-o-briefcase'),
                                TextEntry::make('company.name')
                                    ->label('')
                                    ->color('primary')
                                    ->icon('heroicon-o-building-office-2')
                                    ->url(fn (People $record): ?string => $record->company ? CompanyResource::getUrl('view', [$record->company]) : null),
                            ])
                            ->grow(true),
                    ]),
                ])
                ->columnSpanFull(),

            // Professional Information
            Section::make('Professional Information')
                ->icon('heroicon-o-briefcase')
                ->description('Job details and organizational structure')
                ->columns(2)
                ->schema([
                    TextEntry::make('job_title')
                        ->label('Job Title')
                        ->icon('heroicon-o-identification'),
                    TextEntry::make('department')
                        ->label('Department')
                        ->icon('heroicon-o-building-office'),
                    TextEntry::make('role')
                        ->label('Role')
                        ->icon('heroicon-o-user-circle')
                        ->badge(),
                    TextEntry::make('reportsTo.name')
                        ->label('Reports To')
                        ->icon('heroicon-o-arrow-up-circle'),
                    TextEntry::make('lead_source')
                        ->label('Lead Source')
                        ->icon('heroicon-o-funnel')
                        ->badge()
                        ->color('info'),
                    TextEntry::make('birthdate')
                        ->label('Birthday')
                        ->icon('heroicon-o-cake')
                        ->date('F j, Y'),
                ])
                ->collapsible(),

            // Contact Information
            Section::make('Contact Information')
                ->icon('heroicon-o-phone')
                ->description('Email addresses and phone numbers')
                ->columns(2)
                ->schema([
                    TextEntry::make('emails')
                        ->label('Email Addresses')
                        ->icon('heroicon-o-envelope')
                        ->state(fn (People $record) => $record->emails->map(
                            fn ($email): string => $email->email.' '.($email->is_primary ? '★' : '('.($email->type?->label() ?? $email->type).')')
                        ))
                        ->listWithLineBreaks()
                        ->copyable()
                        ->columnSpanFull(),
                    TextEntry::make('phone_mobile')
                        ->label('Mobile Phone')
                        ->icon('heroicon-o-device-phone-mobile')
                        ->copyable(),
                    TextEntry::make('phone_office')
                        ->label('Office Phone')
                        ->icon('heroicon-o-phone')
                        ->copyable(),
                    TextEntry::make('phone_home')
                        ->label('Home Phone')
                        ->icon('heroicon-o-home')
                        ->copyable(),
                    TextEntry::make('phone_fax')
                        ->label('Fax')
                        ->icon('heroicon-o-printer')
                        ->copyable(),
                    TextEntry::make('custom_phones')
                        ->label('Other Phones')
                        ->icon('heroicon-o-phone-arrow-down-left')
                        ->state(fn (People $record): ?string => $this->formatCustomPhoneNumbers($record))
                        ->visible(fn (?string $state): bool => filled($state))
                        ->copyable()
                        ->columnSpanFull(),
                ])
                ->collapsible(),

            // Social Media
            Section::make('Social Media')
                ->icon('heroicon-o-globe-alt')
                ->description('Social media profiles and links')
                ->columns(2)
                ->schema([
                    TextEntry::make('social_links.linkedin')
                        ->label('LinkedIn')
                        ->icon('heroicon-o-link')
                        ->url(fn (?string $state): ?string => $state)
                        ->openUrlInNewTab()
                        ->copyable(),
                    TextEntry::make('social_links.twitter')
                        ->label('Twitter')
                        ->icon('heroicon-o-link')
                        ->url(fn (?string $state): ?string => $state)
                        ->openUrlInNewTab()
                        ->copyable(),
                    TextEntry::make('social_links.facebook')
                        ->label('Facebook')
                        ->icon('heroicon-o-link')
                        ->url(fn (?string $state): ?string => $state)
                        ->openUrlInNewTab()
                        ->copyable(),
                    TextEntry::make('social_links.github')
                        ->label('GitHub')
                        ->icon('heroicon-o-link')
                        ->url(fn (?string $state): ?string => $state)
                        ->openUrlInNewTab()
                        ->copyable(),
                ])
                ->collapsible()
                ->collapsed(),

            // Address Information
            Section::make('Address')
                ->icon('heroicon-o-map-pin')
                ->description('Physical address details')
                ->columns(2)
                ->schema([
                    TextEntry::make('address_street')
                        ->label('Street Address')
                        ->icon('heroicon-o-map')
                        ->columnSpanFull(),
                    TextEntry::make('address_city')
                        ->label('City')
                        ->icon('heroicon-o-building-office-2'),
                    TextEntry::make('address_state')
                        ->label('State/Province')
                        ->icon('heroicon-o-map'),
                    TextEntry::make('address_postal_code')
                        ->label('Postal Code')
                        ->icon('heroicon-o-hashtag'),
                    TextEntry::make('address_country')
                        ->label('Country')
                        ->icon('heroicon-o-globe-americas'),
                ])
                ->collapsible()
                ->collapsed(),

            // Assistant Information
            Section::make('Assistant Information')
                ->icon('heroicon-o-user-plus')
                ->description('Assistant contact details')
                ->columns(3)
                ->schema([
                    TextEntry::make('assistant_name')
                        ->label('Assistant Name')
                        ->icon('heroicon-o-user'),
                    TextEntry::make('assistant_email')
                        ->label('Assistant Email')
                        ->icon('heroicon-o-envelope')
                        ->copyable(),
                    TextEntry::make('assistant_phone')
                        ->label('Assistant Phone')
                        ->icon('heroicon-o-phone')
                        ->copyable(),
                ])
                ->collapsible()
                ->collapsed()
                ->visible(fn (People $record): bool => filled($record->assistant_name) || filled($record->assistant_email) || filled($record->assistant_phone)),

            // Segmentation & Tags
            Section::make('Segmentation & Tags')
                ->icon('heroicon-o-tag')
                ->description('Categories and organizational tags')
                ->columns(1)
                ->schema([
                    TextEntry::make('segments')
                        ->label('Segments')
                        ->icon('heroicon-o-squares-2x2')
                        ->formatStateUsing(fn (mixed $state): ?string => ArrayHelper::joinList($state, ', ', emptyPlaceholder: null))
                        ->badge()
                        ->separator(','),
                    TextEntry::make('tags')
                        ->label('Tags')
                        ->icon('heroicon-o-tag')
                        ->state(fn (People $record) => $record->tags)
                        ->formatStateUsing(fn (Tag $tag): string => $tag->name)
                        ->badge()
                        ->separator(',')
                        ->color(fn (Tag $tag): array|string => $tag->color ? Color::hex($tag->color) : 'gray'),
                ])
                ->collapsible(),

            // Portal & Sync Settings
            Section::make('Portal & Sync Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->description('Portal access and synchronization configuration')
                ->columns(2)
                ->schema([
                    TextEntry::make('is_portal_user')
                        ->label('Portal User')
                        ->icon('heroicon-o-user-circle')
                        ->formatStateUsing(fn (People $record): string => $record->is_portal_user ? 'Yes' : 'No')
                        ->badge()
                        ->color(fn (People $record): string => $record->is_portal_user ? 'success' : 'gray'),
                    TextEntry::make('portal_username')
                        ->label('Portal Username')
                        ->icon('heroicon-o-at-symbol')
                        ->copyable(),
                    TextEntry::make('sync_enabled')
                        ->label('Sync Enabled')
                        ->icon('heroicon-o-arrow-path')
                        ->formatStateUsing(fn (People $record): string => $record->sync_enabled ? 'Yes' : 'No')
                        ->badge()
                        ->color(fn (People $record): string => $record->sync_enabled ? 'success' : 'gray'),
                    TextEntry::make('sync_reference')
                        ->label('Sync Reference')
                        ->icon('heroicon-o-key')
                        ->copyable(),
                ])
                ->collapsible()
                ->collapsed(),

            // Custom Fields
            CustomFields::infolist()->forSchema($schema)->build()->columnSpanFull(),
        ]);
    }

    private function formatCustomPhoneNumbers(People $record): ?string
    {
        $field = CustomField::query()
            ->forEntity(People::class)
            ->where('code', PeopleField::PHONE_NUMBER->value)
            ->first();

        if (! $field instanceof CustomField) {
            return null;
        }

        return ArrayHelper::joinList($record->getCustomFieldValue($field), ', ', emptyPlaceholder: null);
    }
}
