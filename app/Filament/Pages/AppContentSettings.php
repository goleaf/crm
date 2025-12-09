<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Clusters\Settings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Inerba\DbConfig\AbstractPageSettings;

final class AppContentSettings extends AbstractPageSettings
{
    protected static ?string $title = 'Content Settings';

    protected static ?string $navigationLabel = 'Content Settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?int $navigationSort = 985;

    protected static ?string $slug = 'content-settings';

    protected static ?string $cluster = Settings::class;

    protected ?string $subheading = 'DB-backed content and messaging managed from Filament.';

    protected string $view = 'filament.pages.app-content-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.settings');
    }

    protected function settingName(): string
    {
        return 'app-content';
    }

    /**
     * Provide sensible defaults for first-load UX.
     *
     * @return array<string, mixed>
     */
    public function getDefaultData(): array
    {
        $supportEmail = config('mail.from.address') ?? 'support@example.com';

        return [
            'brand' => [
                'name' => brand_name(),
                'tagline' => 'Run your customer operations from a single place.',
                'value_prop' => 'Keep teams aligned with shared pipelines, automation, and reporting.',
            ],
            'cta' => [
                'enabled' => true,
                'label' => 'Start a workspace',
                'url' => rtrim((string) config('app.url'), '/') . '/register',
                'helper' => 'Invite your team after onboarding is finished.',
            ],
            'support' => [
                'email' => $supportEmail,
                'url' => rtrim((string) config('app.url'), '/') . '/help',
            ],
            'announcement' => [
                'enabled' => false,
                'message' => 'Use announcements for seasonal notices or maintenance windows.',
            ],
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Brand voice')
                    ->schema([
                        Forms\Components\TextInput::make('brand.name')
                            ->label('Brand name')
                            ->required()
                            ->maxLength(120),
                        Forms\Components\TextInput::make('brand.tagline')
                            ->label('Tagline')
                            ->helperText('Single-line promise shown on marketing surfaces.')
                            ->maxLength(180),
                        Forms\Components\Textarea::make('brand.value_prop')
                            ->label('Value proposition')
                            ->rows(3)
                            ->helperText('Longer blurb for landing sections or email footers.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Primary call to action')
                    ->schema([
                        Forms\Components\Toggle::make('cta.enabled')
                            ->label('Show CTA')
                            ->helperText('Controls whether the CTA renders on public surfaces.')
                            ->default(true),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('cta.label')
                                    ->label('CTA label')
                                    ->maxLength(80)
                                    ->required(),
                                Forms\Components\TextInput::make('cta.url')
                                    ->label('CTA URL')
                                    ->url()
                                    ->maxLength(255)
                                    ->required(),
                            ]),
                        Forms\Components\Textarea::make('cta.helper')
                            ->label('CTA helper text')
                            ->rows(2)
                            ->helperText('Optional supporting copy beneath the primary call to action.')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Support & trust')
                    ->schema([
                        Forms\Components\TextInput::make('support.email')
                            ->label('Support email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('support.url')
                            ->label('Support URL')
                            ->url()
                            ->maxLength(255)
                            ->helperText('Link to status page, help center, or contact form.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Announcement banner')
                    ->schema([
                        Forms\Components\Toggle::make('announcement.enabled')
                            ->label('Show announcement')
                            ->live(),
                        Forms\Components\Textarea::make('announcement.message')
                            ->label('Announcement message')
                            ->rows(2)
                            ->helperText('Shown when the banner is enabled.')
                            ->visible(fn (Get $get): bool => (bool) $get('announcement.enabled'))
                            ->requiredIf('announcement.enabled', true),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }
}
