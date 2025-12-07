<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Data\AddressData;
use App\Enums\AccountType;
use App\Enums\AddressType;
use App\Enums\CustomFields\CompanyField;
use App\Enums\Industry;
use App\Filament\Actions\GenerateRecordSummaryAction;
use App\Filament\Components\Infolists\AvatarName;
use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\CompanyResource\RelationManagers\AnnualRevenuesRelationManager;
use App\Filament\Resources\CompanyResource\RelationManagers\CasesRelationManager;
use App\Filament\Resources\CompanyResource\RelationManagers\NotesRelationManager;
use App\Filament\Resources\CompanyResource\RelationManagers\PeopleRelationManager;
use App\Filament\Resources\CompanyResource\RelationManagers\TasksRelationManager;
use App\Jobs\FetchFaviconForCompany;
use App\Models\AccountTeamMember;
use App\Models\Company;
use App\Support\Addresses\AddressFormatter;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Relaticle\CustomFields\Facades\CustomFields;

/**
 * ViewCompany page displays comprehensive company information in a read-only infolist format.
 *
 * This page uses Filament v4's unified Schema system to display company details including:
 * - Basic company information (name, type, industry, contact details)
 * - Address information (billing, shipping, additional addresses)
 * - Account team members with role and access level badges
 * - Child companies hierarchy
 * - File attachments with uploader information
 * - Activity timeline (notes, tasks, opportunities)
 * - Custom fields integration
 * - Annual revenue tracking
 *
 * Performance Optimizations:
 * - Eager loading of relationships (creator, accountOwner, parentCompany)
 * - Batch loading of attachment uploaders to prevent N+1 queries
 * - Pre-computed enum colors in state mapping
 * - Efficient RepeatableEntry state mapping
 *
 * Badge Color Implementation:
 * The account team member badges use a nested array structure where colors are pre-computed
 * during state mapping. The color callbacks receive the entire nested array as $state:
 * - State structure: ['label' => ..., 'color' => ...]
 * - Color callback: fn (?array $state): string => $state['color'] ?? 'gray'
 *
 * @see \App\Filament\Resources\CompanyResource
 * @see \App\Models\Company
 * @see docs/ui-ux/viewcompany-badge-colors.md
 * @see docs/performance-viewcompany.md
 *
 * @package App\Filament\Resources\CompanyResource\Pages
 */
final class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GenerateRecordSummaryAction::make(),
            ActionGroup::make([
                EditAction::make()
                    ->after(function (Company $record, array $data): void {
                        $this->dispatchFaviconFetchIfNeeded($record, $data);
                    }),
                DeleteAction::make(),
            ]),
        ];
    }

    /**
     * Dispatch favicon fetch job if domain_name custom field has changed.
     *
     * Compares the new domain value from form data with the existing value in the database.
     * Only dispatches the job if the domain has actually changed and the new value is not empty.
     *
     * @param  Company  $company  The company record being updated
     * @param  array<string, mixed>  $data  The form data containing custom_fields
     *
     * @return void
     */
    private function dispatchFaviconFetchIfNeeded(Company $company, array $data): void
    {
        $customFieldsData = $data['custom_fields'] ?? [];
        $newDomain = $customFieldsData['domain_name'] ?? null;

        // Get the old domain value from the database
        $domainField = $company->customFields()
            ->whereBelongsTo($company->team)
            ->where('code', CompanyField::DOMAIN_NAME->value)
            ->first();

        $oldDomain = $domainField !== null ? $company->getCustomFieldValue($domainField) : null;

        // Only dispatch if domain changed and new value is not empty
        if (! in_array($newDomain, [$oldDomain, null, '', '0'], true)) {
            FetchFaviconForCompany::dispatch($company)->afterCommit();
        }
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Flex::make([
                    Section::make([
                        Flex::make([
                            AvatarName::make('logo')
                                ->avatar('logo')
                                ->name('name')
                                ->avatarSize('lg')
                                ->textSize('xl')
                                ->square()
                                ->label(''),
                            AvatarName::make('creator')
                                ->avatar('creator.avatar')
                                ->name('creator.name')
                                ->avatarSize('sm')
                                ->textSize('sm')  // Default text size for creator
                                ->circular()
                                ->label(__('app.labels.created_by')),
                            AvatarName::make('accountOwner')
                                ->avatar('accountOwner.avatar')
                                ->name('accountOwner.name')
                                ->avatarSize('sm')
                                ->textSize('sm')  // Default text size for account owner
                                ->circular()
                                ->label(__('app.labels.account_owner')),
                        ]),
                        Grid::make()
                            ->columns(12)
                            ->schema([
                                TextEntry::make('account_type')
                                    ->label(__('app.labels.account_type'))
                                    ->badge()
                                    ->columnSpan(3)
                                    ->formatStateUsing(fn (?AccountType $state): string => $state?->label() ?? '—')
                                    ->color(fn (?AccountType $state): string => $state?->color() ?? 'gray'),
                                TextEntry::make('ownership')
                                    ->label(__('app.labels.ownership'))
                                    ->columnSpan(3)
                                    ->formatStateUsing(fn (?string $state): string => $state ?? '—'),
                                TextEntry::make('accountOwner.name')
                                    ->label(__('app.labels.account_owner'))
                                    ->columnSpan(3)
                                    ->formatStateUsing(fn (?string $state): string => $state ?? '—'),
                                TextEntry::make('parentCompany.name')
                                    ->label(__('app.labels.parent_company'))
                                    ->columnSpan(3)
                                    ->formatStateUsing(fn (?string $state): string => $state ?? '—'),
                                TextEntry::make('currency_code')
                                    ->label(__('app.labels.currency'))
                                    ->columnSpan(3)
                                    ->formatStateUsing(fn (?string $state): string => $state ?? '—'),
                                TextEntry::make('website')
                                    ->label(__('app.labels.website'))
                                    ->icon('heroicon-o-globe-alt')
                                    ->url(fn (Company $record): ?string => $record->website ?: null)
                                    ->columnSpan(3)
                                    ->formatStateUsing(fn (?string $state): string => $state ?: '—'),
                                TextEntry::make('industry')
                                    ->label(__('app.labels.industry'))
                                    ->icon('heroicon-o-briefcase')
                                    ->columnSpan(3)
                                    ->formatStateUsing(fn (?Industry $state): string => $state?->label() ?? '—'),
                                TextEntry::make('phone')
                                    ->label(__('app.labels.phone'))
                                    ->icon('heroicon-o-phone')
                                    ->columnSpan(3)
                                    ->formatStateUsing(fn (?string $state): string => $state ?? '—'),
                                TextEntry::make('primary_email')
                                    ->label(__('app.labels.email'))
                                    ->icon('heroicon-o-envelope')
                                    ->url(fn (?string $state): ?string => $state !== null ? 'mailto:'.$state : null)
                                    ->columnSpan(3)
                                    ->formatStateUsing(fn (?string $state): string => $state ?? '—'),
                                TextEntry::make('social_links.linkedin')
                                    ->label(__('app.labels.linkedin'))
                                    ->icon('heroicon-o-link')
                                    ->url(fn (?string $state): ?string => $state ?: null)
                                    ->columnSpan(3)
                                    ->formatStateUsing(fn (?string $state): string => $state ?: '—'),
                                TextEntry::make('social_links.twitter')
                                    ->label(__('app.labels.twitter'))
                                    ->icon('heroicon-o-link')
                                    ->url(fn (?string $state): ?string => $state ?: null)
                                    ->columnSpan(3)
                                    ->formatStateUsing(fn (?string $state): string => $state ?: '—'),
                                TextEntry::make('employee_count')
                                    ->label(__('app.labels.employees'))
                                    ->icon('heroicon-o-users')
                                    ->columnSpan(3)
                                    ->formatStateUsing(fn (mixed $state): string => $state !== null ? number_format((int) $state) : '—'),
                                TextEntry::make('revenue')
                                    ->label(__('app.labels.annual_revenue'))
                                    ->icon('heroicon-o-banknotes')
                                    ->columnSpan(3)
                                    ->state(function (Company $record): string {
                                        $latest = $record->latestAnnualRevenue;

                                        if ($latest !== null) {
                                            return ($latest->currency_code ?? $record->currency_code ?? 'USD').' '.number_format((float) $latest->amount, 2).' ('.$latest->year.')';
                                        }

                                        if ($record->revenue !== null) {
                                            return ($record->currency_code ?? 'USD').' '.number_format((float) $record->revenue, 2);
                                        }

                                        return '—';
                                    }),
                                TextEntry::make('billing_address')
                                    ->label(__('app.labels.billing_address'))
                                    ->columnSpan(6)
                                    ->state(fn (Company $record): string => collect([
                                        $record->billing_street,
                                        $record->billing_city,
                                        $record->billing_state,
                                        $record->billing_postal_code,
                                        $record->billing_country,
                                    ])->filter(fn (mixed $value): bool => filled($value))->implode(', '))
                                    ->formatStateUsing(fn (?string $state): string => $state !== null && trim($state) !== '' ? $state : '—'),
                                TextEntry::make('shipping_address')
                                    ->label(__('app.labels.shipping_address'))
                                    ->columnSpan(6)
                                    ->state(fn (Company $record): string => collect([
                                        $record->shipping_street,
                                        $record->shipping_city,
                                        $record->shipping_state,
                                        $record->shipping_postal_code,
                                        $record->shipping_country,
                                    ])->filter(fn (mixed $value): bool => filled($value))->implode(', '))
                                    ->formatStateUsing(fn (?string $state): string => $state !== null && trim($state) !== '' ? $state : '—'),
                                RepeatableEntry::make('addresses')
                                    ->label(__('app.labels.additional_addresses'))
                                    ->columnSpan(12)
                                    ->state(fn (Company $record): array => $record->addressCollection()
                                        ->filter(fn (AddressData $address): bool => ! in_array($address->type, [AddressType::BILLING, AddressType::SHIPPING], true))
                                        ->map(fn (AddressData $address): array => [
                                            'label' => $address->label ?? $address->type->label(),
                                            'formatted' => new AddressFormatter()->format($address, multiline: true),
                                        ])
                                        ->all())
                                    ->visible(fn (?array $state): bool => count($state ?? []) > 0)
                                    ->schema([
                                        TextEntry::make('label')
                                            ->label(__('app.labels.label'))
                                            ->columnSpan(4),
                                        TextEntry::make('formatted')
                                            ->label(__('app.labels.address'))
                                            ->columnSpan(8),
                                    ]),
                                TextEntry::make('description')
                                    ->label(__('app.labels.description'))
                                    ->columnSpan(12)
                                    ->formatStateUsing(fn (?string $state): string => $state !== null && trim($state) !== '' ? $state : '—'),
                            ]),
                        CustomFields::infolist()->forSchema($schema)->build(),
                        RepeatableEntry::make('account_team_members')
                            ->label(__('app.labels.account_team'))
                            ->columnSpan('full')
                            ->state(fn (Company $record): array => $record->accountTeamMembers()
                                ->with('user')
                                ->orderBy('created_at')
                                ->get()
                                ->map(fn (AccountTeamMember $member): array => [
                                    'name' => $member->user?->name ?? '—',
                                    'email' => $member->user?->email,
                                    'role' => [
                                        'label' => $member->role?->label() ?? '—',
                                        'color' => $member->role?->color() ?? 'gray',
                                    ],
                                    'access' => [
                                        'label' => $member->access_level?->label() ?? '—',
                                        'color' => $member->access_level?->color() ?? 'gray',
                                    ],
                                ])
                                ->all())
                            ->visible(fn (?array $state): bool => count($state ?? []) > 0)
                            ->emptyStateHeading(__('app.messages.no_team_members'))
                            ->emptyStateDescription(__('app.messages.add_team_members_to_collaborate'))
                            ->schema([
                                TextEntry::make('name')
                                    ->label(__('app.labels.member'))
                                    ->columnSpan(4),
                                TextEntry::make('email')
                                    ->label(__('app.labels.email'))
                                    ->url(fn (?string $state): ?string => $state !== null ? 'mailto:'.$state : null)
                                    ->columnSpan(4)
                                    ->formatStateUsing(fn (?string $state): string => $state ?? '—'),
                                TextEntry::make('role')
                                    ->label(__('app.labels.role'))
                                    ->badge()
                                    ->formatStateUsing(fn (?array $state): string => $state['label'] ?? '—')
                                    ->color(fn (?array $state): string => $state['color'] ?? 'gray')
                                    ->columnSpan(2),
                                TextEntry::make('access')
                                    ->label(__('app.labels.access'))
                                    ->badge()
                                    ->formatStateUsing(fn (?array $state): string => $state['label'] ?? '—')
                                    ->color(fn (?array $state): string => $state['color'] ?? 'gray')
                                    ->columnSpan(2),
                            ]),
                        RepeatableEntry::make('child_companies')
                            ->label(__('app.labels.child_companies'))
                            ->columnSpan('full')
                            ->state(fn (Company $record): array => $record->childCompanies()
                                ->select(['id', 'name', 'account_type', 'industry', 'billing_city', 'created_at'])
                                ->get()
                                ->map(fn (Company $child): array => [
                                    'id' => $child->getKey(),
                                    'name' => $child->name,
                                    'account_type' => $child->account_type,
                                    'industry' => $child->industry,
                                    'billing_city' => $child->billing_city,
                                    'url' => self::getResource()::getUrl('view', ['record' => $child]),
                                    'created_at' => $child->created_at,
                                ])
                                ->all())
                            ->visible(fn (?array $state): bool => count($state ?? []) > 0)
                            ->schema([
                                TextEntry::make('name')
                                    ->label(__('app.labels.company'))
                                    ->url(fn (array $state): ?string => $state['url'] ?? null)
                                    ->openUrlInNewTab()
                                    ->columnSpan(4),
                                TextEntry::make('account_type')
                                    ->label(__('app.labels.type'))
                                    ->badge()
                                    ->formatStateUsing(fn (?AccountType $state): string => $state?->label() ?? '—')
                                    ->color(fn (?AccountType $state): string => $state?->color() ?? 'gray')
                                    ->columnSpan(2),
                                TextEntry::make('industry')
                                    ->label(__('app.labels.industry'))
                                    ->columnSpan(2)
                                    ->formatStateUsing(fn (?Industry $state): string => $state?->label() ?? '—'),
                                TextEntry::make('billing_city')
                                    ->label(__('app.labels.city'))
                                    ->columnSpan(2)
                                    ->formatStateUsing(fn (?string $state): string => $state ?? '—'),
                                TextEntry::make('created_at')
                                    ->label(__('app.labels.added'))
                                    ->since()
                                    ->columnSpan(2),
                            ]),
                        RepeatableEntry::make('attachments')
                            ->label(__('app.labels.attachments'))
                            ->columnSpan('full')
                            ->state(function (Company $record): array {
                                // Eager load users for uploaded_by to prevent N+1
                                $uploaderIds = $record->attachments
                                    ->map(fn ($media) => $media->getCustomProperty('uploaded_by'))
                                    ->filter()
                                    ->unique()
                                    ->values();

                                $uploaders = $uploaderIds->isNotEmpty()
                                    ? \App\Models\User::whereIn('id', $uploaderIds)->pluck('name', 'id')
                                    : collect();

                                return $record->attachments
                                    ->map(fn (\Spatie\MediaLibrary\MediaCollections\Models\Media $media): array => [
                                        'file_name' => $media->file_name,
                                        'mime_type' => $media->mime_type,
                                        'uploaded_by_id' => $media->getCustomProperty('uploaded_by'),
                                        'uploaded_by_name' => $uploaders->get($media->getCustomProperty('uploaded_by')) ?? '—',
                                        'uploaded_at' => $media->created_at,
                                        'size' => method_exists($media, 'humanReadableSize') ? $media->humanReadableSize : null,
                                        'url' => $media->getUrl(),
                                    ])
                                    ->all();
                            })
                            ->visible(fn (?array $state): bool => count($state ?? []) > 0)
                            ->schema([
                                TextEntry::make('file_name')
                                    ->label(__('app.labels.file'))
                                    ->url(fn (array $state): ?string => $state['url'] ?? null)
                                    ->openUrlInNewTab()
                                    ->columnSpan(4),
                                TextEntry::make('mime_type')
                                    ->label(__('app.labels.type'))
                                    ->columnSpan(3)
                                    ->formatStateUsing(fn (?string $state): string => $state ?? '—'),
                                TextEntry::make('size')
                                    ->label(__('app.labels.size'))
                                    ->columnSpan(2)
                                    ->formatStateUsing(fn (?string $state): string => $state ?? '—'),
                                TextEntry::make('uploaded_by_name')
                                    ->label(__('app.labels.uploaded_by'))
                                    ->columnSpan(3),
                                TextEntry::make('uploaded_at')
                                    ->label(__('app.labels.uploaded'))
                                    ->dateTime()
                                    ->since()
                                    ->columnSpan(3),
                            ]),
                        RepeatableEntry::make('activity_timeline')
                            ->label(__('app.labels.activity'))
                            ->columnSpan('full')
                            ->state(fn (Company $record): array => $record->getActivityTimeline()
                                ->map(fn (array $item): array => [
                                    'title' => $item['title'],
                                    'summary' => $item['summary'],
                                    'type' => ucfirst((string) $item['type']),
                                    'created_at' => $item['created_at'],
                                ])
                                ->all())
                            ->visible(fn (?array $state): bool => count($state ?? []) > 0)
                            ->schema([
                                TextEntry::make('title')
                                    ->label(__('app.labels.entry'))
                                    ->columnSpan(4),
                                TextEntry::make('type')
                                    ->label(__('app.labels.type'))
                                    ->badge()
                                    ->columnSpan(2),
                                TextEntry::make('summary')
                                    ->label(__('app.labels.summary'))
                                    ->columnSpan(4),
                                TextEntry::make('created_at')
                                    ->label(__('app.labels.when'))
                                    ->since()
                                    ->columnSpan(2),
                            ]),
                    ]),
                    Section::make([
                        TextEntry::make('created_at')
                            ->label(__('app.labels.created_date'))
                            ->icon('heroicon-o-clock')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label(__('app.labels.last_updated'))
                            ->icon('heroicon-o-clock')
                            ->dateTime(),
                    ])->grow(false),
                ])->columnSpan('full'),
            ]);
    }

    public function getRelationManagers(): array
    {
        return [
            AnnualRevenuesRelationManager::class,
            CasesRelationManager::class,
            PeopleRelationManager::class,
            TasksRelationManager::class,
            NotesRelationManager::class,
            ActivitiesRelationManager::class,
        ];
    }
}
