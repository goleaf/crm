# Helper Functions - Practical Examples

This document provides real-world examples of using helper functions in your Filament resources, services, and views.

## Table Column Formatting

### Using DateHelper in Tables
```php
use App\Support\Helpers\DateHelper;

TextColumn::make('created_at')
    ->label(__('app.labels.created_at'))
    ->formatStateUsing(fn ($state) => DateHelper::ago($state))
    ->sortable(),

TextColumn::make('updated_at')
    ->label(__('app.labels.updated_at'))
    ->formatStateUsing(fn ($state) => DateHelper::humanDate($state, 'M j, Y g:i A'))
    ->toggleable(),
```

### Using NumberHelper in Tables
```php
use App\Support\Helpers\NumberHelper;

TextColumn::make('revenue')
    ->label(__('app.labels.revenue'))
    ->formatStateUsing(fn ($state) => NumberHelper::currency($state, 'USD'))
    ->sortable(),

TextColumn::make('file_size')
    ->label(__('app.labels.file_size'))
    ->formatStateUsing(fn ($state) => NumberHelper::fileSize($state)),

TextColumn::make('completion_rate')
    ->label(__('app.labels.completion'))
    ->formatStateUsing(fn ($state) => NumberHelper::percentage($state)),
```

### Using ArrayHelper in Tables
```php
use App\Support\Helpers\ArrayHelper;

TextColumn::make('tags')
    ->label(__('app.labels.tags'))
    ->formatStateUsing(fn (mixed $state) => ArrayHelper::joinList($state, ', ', ' and ')),

TextColumn::make('categories')
    ->label(__('app.labels.categories'))
    ->formatStateUsing(fn (mixed $state) => ArrayHelper::joinList($state)),
```

### Using StringHelper in Tables
```php
use App\Support\Helpers\StringHelper;

TextColumn::make('description')
    ->label(__('app.labels.description'))
    ->formatStateUsing(fn ($state) => StringHelper::limit($state, 100))
    ->wrap(),

TextColumn::make('content')
    ->label(__('app.labels.content'))
    ->formatStateUsing(fn ($state) => StringHelper::excerpt($state, 150)),
```

### Using FileHelper in Tables
```php
use App\Support\Helpers\FileHelper;

TextColumn::make('attachment')
    ->label(__('app.labels.attachment'))
    ->icon(fn ($state) => FileHelper::iconClass($state))
    ->formatStateUsing(fn ($state) => FileHelper::nameWithoutExtension($state)),
```

## Infolist Entries

### Date Formatting
```php
use App\Support\Helpers\DateHelper;

TextEntry::make('created_at')
    ->label(__('app.labels.created'))
    ->formatStateUsing(fn ($state) => DateHelper::formatRange($state, now())),

TextEntry::make('last_login')
    ->label(__('app.labels.last_login'))
    ->formatStateUsing(fn ($state) => DateHelper::ago($state)),
```

### URL Display
```php
use App\Support\Helpers\UrlHelper;

TextEntry::make('website')
    ->label(__('app.labels.website'))
    ->formatStateUsing(fn ($state) => UrlHelper::shorten($state, 50))
    ->url(fn ($state) => $state)
    ->openUrlInNewTab(),
```

### Color Display
```php
use App\Support\Helpers\ColorHelper;

ColorEntry::make('brand_color')
    ->label(__('app.labels.brand_color'))
    ->formatStateUsing(function ($state) {
        $brightness = ColorHelper::isLight($state) ? 'Light' : 'Dark';
        return "{$state} ({$brightness})";
    }),
```

## Form Field Processing

### Slug Generation
```php
use App\Support\Helpers\StringHelper;

TextInput::make('title')
    ->label(__('app.labels.title'))
    ->required()
    ->live(onBlur: true)
    ->afterStateUpdated(function ($state, Set $set) {
        $set('slug', StringHelper::kebab($state));
    }),

TextInput::make('slug')
    ->label(__('app.labels.slug'))
    ->required()
    ->unique(ignoreRecord: true),
```

### Phone Number Validation
```php
use App\Support\Helpers\ValidationHelper;

TextInput::make('phone')
    ->label(__('app.labels.phone'))
    ->tel()
    ->rules([
        fn () => function (string $attribute, $value, Closure $fail) {
            if (!ValidationHelper::isPhone($value)) {
                $fail(__('validation.phone'));
            }
        },
    ]),
```

### Email Validation with Helper
```php
use App\Support\Helpers\ValidationHelper;

TextInput::make('email')
    ->label(__('app.labels.email'))
    ->email()
    ->suffixAction(
        Action::make('verify')
            ->icon('heroicon-o-check-badge')
            ->action(function ($state, $set) {
                if (ValidationHelper::isEmail($state)) {
                    Notification::make()
                        ->title(__('app.notifications.valid_email'))
                        ->success()
                        ->send();
                }
            })
    ),
```

## Service Layer Examples

### Activity Feed Service
```php
use App\Support\Helpers\DateHelper;
use App\Support\Helpers\StringHelper;

class ActivityFeedService
{
    public function formatActivity(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'description' => StringHelper::limit($activity->description, 100),
            'time_ago' => DateHelper::ago($activity->created_at),
            'is_recent' => DateHelper::isToday($activity->created_at),
        ];
    }
}
```

### Report Generation Service
```php
use App\Support\Helpers\NumberHelper;
use App\Support\Helpers\DateHelper;

class ReportService
{
    public function generateSummary(array $data): string
    {
        $revenue = NumberHelper::currency($data['total_revenue'], 'USD');
        $period = DateHelper::formatRange($data['start_date'], $data['end_date']);
        $growth = NumberHelper::percentage($data['growth_rate']);
        
        return "Revenue: {$revenue} for {$period} (Growth: {$growth})";
    }
}
```

### File Upload Service
```php
use App\Support\Helpers\FileHelper;
use Blaspsoft\Onym\Facades\Onym;

class FileUploadService
{
    public function processUpload(UploadedFile $file): array
    {
        // Validate
        if (!FileHelper::validateUpload($file, ['pdf', 'doc', 'docx'], 5 * 1024 * 1024)) {
            throw new \Exception('Invalid file');
        }
        
        // Generate safe filename
        $filename = Onym::make(
            defaultFilename: FileHelper::sanitizeFilename($file->getClientOriginalName()),
            extension: FileHelper::extension($file->getClientOriginalName()),
            strategy: 'uuid'
        );
        
        return [
            'filename' => $filename,
            'size' => NumberHelper::fileSize($file->getSize()),
            'type' => FileHelper::mimeType($file->getClientOriginalName()),
            'icon' => FileHelper::iconClass($filename),
        ];
    }
}
```

## Widget Examples

### Stats Widget with Helpers
```php
use App\Support\Helpers\NumberHelper;
use App\Support\Helpers\DateHelper;

class SalesStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $revenue = Order::sum('total');
        $orders = Order::count();
        $avgOrder = $orders > 0 ? $revenue / $orders : 0;
        
        return [
            Stat::make(__('app.stats.total_revenue'), NumberHelper::currency($revenue, 'USD'))
                ->description(NumberHelper::abbreviate($revenue))
                ->color('success'),
                
            Stat::make(__('app.stats.total_orders'), NumberHelper::format($orders))
                ->description(__('app.stats.this_month'))
                ->color('primary'),
                
            Stat::make(__('app.stats.avg_order'), NumberHelper::currency($avgOrder, 'USD'))
                ->description(NumberHelper::percentage(15.5).' '.__('app.stats.increase'))
                ->color('warning'),
        ];
    }
}
```

### Activity Widget with Helpers
```php
use App\Support\Helpers\DateHelper;
use App\Support\Helpers\StringHelper;

class RecentActivityWidget extends TableWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->query(Activity::query()->latest()->limit(10))
            ->columns([
                TextColumn::make('description')
                    ->formatStateUsing(fn ($state) => StringHelper::limit($state, 50)),
                    
                TextColumn::make('created_at')
                    ->formatStateUsing(fn ($state) => DateHelper::ago($state))
                    ->badge()
                    ->color(fn ($state) => DateHelper::isToday($state) ? 'success' : 'gray'),
            ]);
    }
}
```

## Export Column Formatting

### Using Helpers in Exporters
```php
use App\Support\Helpers\ArrayHelper;
use App\Support\Helpers\DateHelper;
use App\Support\Helpers\NumberHelper;

class CompanyExporter extends Exporter
{
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name'),
            
            ExportColumn::make('revenue')
                ->formatStateUsing(fn ($state) => NumberHelper::currency($state, 'USD')),
                
            ExportColumn::make('tags')
                ->formatStateUsing(fn ($state) => ArrayHelper::joinList($state)),
                
            ExportColumn::make('created_at')
                ->formatStateUsing(fn ($state) => DateHelper::humanDate($state, 'Y-m-d H:i:s')),
        ];
    }
}
```

## Notification Examples

### Rich Notifications with Helpers
```php
use App\Support\Helpers\NumberHelper;
use App\Support\Helpers\DateHelper;

Notification::make()
    ->title(__('app.notifications.order_completed'))
    ->body(function () use ($order) {
        $total = NumberHelper::currency($order->total, 'USD');
        $time = DateHelper::ago($order->completed_at);
        
        return "Order #{$order->number} totaling {$total} was completed {$time}";
    })
    ->success()
    ->send();
```

## Blade View Examples

### Using Helpers in Blade
```blade
@php
use App\Support\Helpers\DateHelper;
use App\Support\Helpers\NumberHelper;
use App\Support\Helpers\HtmlHelper;
@endphp

<div class="stats">
    <div class="stat">
        <span class="label">Revenue:</span>
        <span class="value">{{ NumberHelper::currency($revenue, 'USD') }}</span>
    </div>
    
    <div class="stat">
        <span class="label">Last Updated:</span>
        <span class="value">{{ DateHelper::ago($updated_at) }}</span>
    </div>
    
    <div class="contact">
        {!! HtmlHelper::mailto($email, 'Contact Us') !!}
    </div>
    
    <div class="avatar">
        {!! HtmlHelper::avatar($user->name, 48) !!}
    </div>
</div>
```

## API Response Formatting

### Using Helpers in API Controllers
```php
use App\Support\Helpers\DateHelper;
use App\Support\Helpers\NumberHelper;
use App\Support\Helpers\ArrayHelper;

class ContactController extends Controller
{
    public function show(Contact $contact): JsonResponse
    {
        return response()->json([
            'id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email,
            'tags' => ArrayHelper::joinList($contact->tags),
            'created_at' => DateHelper::humanDate($contact->created_at),
            'created_ago' => DateHelper::ago($contact->created_at),
            'lifetime_value' => NumberHelper::currency($contact->lifetime_value, 'USD'),
        ]);
    }
}
```

## Testing with Helpers

### Using Helpers in Tests
```php
use App\Support\Helpers\ValidationHelper;
use App\Support\Helpers\StringHelper;

it('validates email addresses correctly', function () {
    expect(ValidationHelper::isEmail('test@example.com'))->toBeTrue();
    expect(ValidationHelper::isEmail('invalid-email'))->toBeFalse();
});

it('generates proper slugs', function () {
    $slug = StringHelper::kebab('Hello World Example');
    expect($slug)->toBe('hello-world-example');
    expect(ValidationHelper::isSlug($slug))->toBeTrue();
});
```

## Best Practices

### DO:
- ✅ Use helpers consistently across the application
- ✅ Combine multiple helpers for complex formatting
- ✅ Cache expensive helper operations when appropriate
- ✅ Use helpers in closures for reactive Filament fields
- ✅ Leverage helpers in exporters for consistent formatting
- ✅ Use validation helpers in custom validation rules

### DON'T:
- ❌ Mix helper usage with manual implementations
- ❌ Forget to handle null values (helpers do this for you)
- ❌ Skip using helpers for "simple" operations
- ❌ Ignore helper return types in strict mode
- ❌ Reinvent functionality that helpers provide

## Performance Tips

1. **Cache Helper Results in Loops**
```php
// Bad
foreach ($items as $item) {
    $formatted = NumberHelper::currency($item->price, 'USD');
}

// Good
$currency = 'USD';
foreach ($items as $item) {
    $formatted = NumberHelper::currency($item->price, $currency);
}
```

2. **Use Helpers in Query Scopes**
```php
public function scopeRecent(Builder $query): Builder
{
    return $query->where('created_at', '>=', DateHelper::startOfDay(now()->subDays(7)));
}
```

3. **Combine Helpers for Complex Operations**
```php
$summary = StringHelper::limit(
    StringHelper::plainText($htmlContent),
    200
);
```

## Related Documentation
- `docs/helper-functions-guide.md` - Complete helper reference
- `.kiro/steering/filament-conventions.md` - Filament patterns
- `.kiro/steering/laravel-conventions.md` - Laravel conventions
