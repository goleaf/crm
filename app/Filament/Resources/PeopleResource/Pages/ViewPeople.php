<?php

declare(strict_types=1);

namespace App\Filament\Resources\PeopleResource\Pages;

use App\Enums\CustomFields\PeopleField;
use App\Filament\Actions\GenerateRecordSummaryAction;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\PeopleResource;
use App\Models\People;
use App\Models\Tag;
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
            GenerateRecordSummaryAction::make(),
            ActionGroup::make([
                EditAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                Flex::make([
                    ImageEntry::make('avatar')
                        ->label('')
                        ->height(30)
                        ->circular()
                        ->grow(false),
                    TextEntry::make('name')
                        ->label('')
                        ->size(TextSize::Large),
                    TextEntry::make('company.name')
                        ->label(__('app.labels.company'))
                        ->color('primary')
                        ->url(fn (People $record): ?string => $record->company ? CompanyResource::getUrl('view', [$record->company]) : null),
                ]),
                Grid::make()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('job_title')
                            ->label('Job Title'),
                        TextEntry::make('department')
                            ->label('Department'),
                        TextEntry::make('lead_source')
                            ->label('Lead Source'),
                        TextEntry::make('reportsTo.name')
                            ->label('Reports To'),
                        TextEntry::make('birthdate')
                            ->label('Birthday')
                            ->date(),
                        TextEntry::make('segments')
                            ->label('Segments')
                            ->formatStateUsing(fn (?array $state): ?string => $state === null || $state === [] ? null : implode(', ', $state)),
                        TextEntry::make('tags')
                            ->label(__('app.labels.tags'))
                            ->state(fn (People $record) => $record->tags)
                            ->formatStateUsing(fn (Tag $tag): string => $tag->name)
                            ->badge()
                            ->listWithLineBreaks()
                            ->color(fn (Tag $tag): array|string => $tag->color ? Color::hex($tag->color) : 'gray'),
                        TextEntry::make('is_portal_user')
                            ->label('Portal User')
                            ->formatStateUsing(fn (People $record): string => $record->is_portal_user ? 'Yes' : 'No'),
                        TextEntry::make('portal_username')
                            ->label('Portal Username'),
                        TextEntry::make('sync_enabled')
                            ->label('Sync Enabled')
                            ->formatStateUsing(fn (People $record): string => $record->sync_enabled ? 'Yes' : 'No'),
                        TextEntry::make('sync_reference')
                            ->label('Sync Reference'),
                    ]),
                Grid::make()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('emails')
                            ->label('Emails')
                            ->state(fn (People $record) => $record->emails->map(
                                fn ($email): string => $email->email.' '.($email->is_primary ? '(primary)' : '('.
                                    ($email->type?->label() ?? $email->type).')')
                            ))
                            ->listWithLineBreaks()
                            ->copyable(),
                        TextEntry::make('phone_mobile')
                            ->label('Mobile')
                            ->copyable(),
                        TextEntry::make('phone_office')
                            ->label('Office')
                            ->copyable(),
                        TextEntry::make('phone_home')
                            ->label('Home')
                            ->copyable(),
                        TextEntry::make('phone_fax')
                            ->label('Fax')
                            ->copyable(),
                        TextEntry::make('custom_phones')
                            ->label('Other Phones')
                            ->state(fn (People $record): ?string => $this->formatCustomPhoneNumbers($record))
                            ->visible(fn (?string $state): bool => filled($state))
                            ->copyable(),
                        TextEntry::make('social_links.linkedin')
                            ->label('LinkedIn')
                            ->url(fn (?string $state): ?string => $state)
                            ->copyable(),
                        TextEntry::make('social_links.twitter')
                            ->label('Twitter')
                            ->url(fn (?string $state): ?string => $state)
                            ->copyable(),
                        TextEntry::make('social_links.facebook')
                            ->label('Facebook')
                            ->url(fn (?string $state): ?string => $state)
                            ->copyable(),
                        TextEntry::make('social_links.github')
                            ->label('GitHub')
                            ->url(fn (?string $state): ?string => $state)
                            ->copyable(),
                    ]),
                Grid::make()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('address_street')
                            ->label('Street'),
                        TextEntry::make('address_city')
                            ->label('City'),
                        TextEntry::make('address_state')
                            ->label('State/Province'),
                        TextEntry::make('address_postal_code')
                            ->label('Postal Code'),
                        TextEntry::make('address_country')
                            ->label('Country'),
                        TextEntry::make('assistant_name')
                            ->label('Assistant'),
                        TextEntry::make('assistant_email')
                            ->label('Assistant Email')
                            ->copyable(),
                        TextEntry::make('assistant_phone')
                            ->label('Assistant Phone')
                            ->copyable(),
                    ]),
                CustomFields::infolist()->forSchema($schema)->build()->columnSpanFull(),
            ])->columnSpanFull(),
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

        $value = $record->getCustomFieldValue($field);

        if ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->toArray();
        }

        $phones = is_array($value) ? $value : [$value];

        $phones = array_values(array_filter(array_map(trim(...), $phones)));

        return $phones === [] ? null : implode(', ', $phones);
    }
}
