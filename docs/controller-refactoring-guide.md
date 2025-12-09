# Laravel Controller Refactoring Guide

> **Quick Reference**: This is the comprehensive refactoring guide. For concise rules, see `.kiro/steering/controller-refactoring.md` and `.kiro/steering/laravel-container-services.md`.

## Overview
This guide provides practical patterns for refactoring Laravel controllers to follow modern best practices, focusing on thin controllers with business logic extracted to Action classes and Services.

## Why Refactor Controllers?

### Problems with Fat Controllers
- **Hard to Test**: Business logic mixed with HTTP concerns
- **Poor Reusability**: Logic tied to HTTP context
- **Difficult to Maintain**: Large methods with multiple responsibilities
- **Tight Coupling**: Direct dependencies on framework components

### Benefits of Thin Controllers
- **Easy Testing**: Actions tested independently of HTTP layer
- **Reusability**: Actions used in controllers, jobs, commands, Filament
- **Maintainability**: Single responsibility per class
- **Flexibility**: Easy to swap implementations

## Refactoring Patterns

### Pattern 1: Extract to Action Class

#### Before: Fat Controller
```php
<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function approve(Request $request, Order $order)
    {
        // Validation
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
            'notify_customer' => 'boolean',
        ]);
        
        // Authorization
        if (!$request->user()->can('approve', $order)) {
            abort(403);
        }
        
        // Business Logic
        DB::transaction(function () use ($order, $validated, $request) {
            $order->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $request->user()->id,
                'approval_notes' => $validated['notes'] ?? null,
            ]);
            
            // Log activity
            activity()
                ->performedOn($order)
                ->causedBy($request->user())
                ->log('Order approved');
            
            // Send notifications
            if ($validated['notify_customer'] ?? true) {
                Mail::to($order->customer->email)
                    ->send(new OrderApprovedMail($order));
            }
            
            // Update inventory
            foreach ($order->items as $item) {
                $item->product->decrement('stock', $item->quantity);
            }
        });
        
        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Order approved successfully');
    }
}
```

#### After: Thin Controller + Action

**1. Create Form Request**
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('approve', $this->route('order'));
    }
    
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
            'notify_customer' => ['boolean'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'notes.max' => __('validation.approval_notes_too_long'),
        ];
    }
    
    public function preparedData(): array
    {
        return [
            'notes' => $this->input('notes'),
            'notify_customer' => $this->boolean('notify_customer', true),
            'approved_by' => $this->user()->id,
            'approved_at' => now(),
        ];
    }
}
```

**2. Create Action Class**
```php
<?php

namespace App\Actions\Orders;

use App\Events\OrderApproved;
use App\Mail\OrderApprovedMail;
use App\Models\Order;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ApproveOrder
{
    public function __construct(
        private readonly InventoryService $inventory
    ) {}
    
    public function execute(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            // Update order
            $order->update([
                'status' => 'approved',
                'approved_at' => $data['approved_at'],
                'approved_by' => $data['approved_by'],
                'approval_notes' => $data['notes'] ?? null,
            ]);
            
            // Log activity
            activity()
                ->performedOn($order)
                ->causedBy($data['approved_by'])
                ->log('Order approved');
            
            // Send notification
            if ($data['notify_customer']) {
                Mail::to($order->customer->email)
                    ->send(new OrderApprovedMail($order));
            }
            
            // Update inventory
            $this->inventory->reserveForOrder($order);
            
            // Dispatch event
            event(new OrderApproved($order));
            
            return $order->fresh();
        });
    }
}
```

**3. Create Single Action Controller**
```php
<?php

namespace App\Http\Controllers;

use App\Actions\Orders\ApproveOrder;
use App\Http\Requests\ApproveOrderRequest;
use App\Models\Order;

class ApproveOrderController extends Controller
{
    public function __invoke(
        ApproveOrderRequest $request,
        Order $order,
        ApproveOrder $action
    ) {
        $action->execute($order, $request->preparedData());
        
        return redirect()
            ->route('orders.show', $order)
            ->with('success', __('app.messages.order_approved'));
    }
}
```

**4. Update Route**
```php
// routes/web.php
use App\Http\Controllers\ApproveOrderController;

Route::post('/orders/{order}/approve', ApproveOrderController::class)
    ->name('orders.approve')
    ->middleware(['auth', 'can:approve,order']);
```

### Pattern 2: Service + Action Combination

For complex operations requiring multiple services:

```php
<?php

namespace App\Actions\Customers;

use App\Models\Customer;
use App\Services\CRM\CustomerService;
use App\Services\Email\EmailService;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Support\Facades\DB;

class MergeCustomers
{
    public function __construct(
        private readonly CustomerService $customers,
        private readonly EmailService $email,
        private readonly AnalyticsService $analytics
    ) {}
    
    public function execute(Customer $primary, Customer $duplicate): Customer
    {
        return DB::transaction(function () use ($primary, $duplicate) {
            // Transfer relationships
            $this->customers->transferRelationships($duplicate, $primary);
            
            // Merge metadata
            $primary->metadata = array_merge(
                $primary->metadata ?? [],
                $duplicate->metadata ?? []
            );
            $primary->save();
            
            // Track merge in analytics
            $this->analytics->trackCustomerMerge($primary, $duplicate);
            
            // Notify stakeholders
            $this->email->notifyCustomerMerge($primary, $duplicate);
            
            // Soft delete duplicate
            $duplicate->delete();
            
            return $primary->fresh();
        });
    }
}
```

### Pattern 3: Pipeline Pattern for Multi-Step Operations

```php
<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Pipelines\Orders\ValidateOrder;
use App\Pipelines\Orders\CalculateTotals;
use App\Pipelines\Orders\ApplyDiscounts;
use App\Pipelines\Orders\ReserveInventory;
use App\Pipelines\Orders\SendConfirmation;
use Illuminate\Pipeline\Pipeline;

class ProcessOrder
{
    public function execute(Order $order): Order
    {
        return app(Pipeline::class)
            ->send($order)
            ->through([
                ValidateOrder::class,
                CalculateTotals::class,
                ApplyDiscounts::class,
                ReserveInventory::class,
                SendConfirmation::class,
            ])
            ->thenReturn();
    }
}
```

## Testing Refactored Code

### Testing Actions (Unit Tests)

```php
<?php

use App\Actions\Orders\ApproveOrder;
use App\Models\Order;
use App\Services\Inventory\InventoryService;

it('approves order with correct data', function () {
    $inventory = Mockery::mock(InventoryService::class);
    $inventory->shouldReceive('reserveForOrder')->once();
    
    $action = new ApproveOrder($inventory);
    
    $order = Order::factory()->create(['status' => 'pending']);
    
    $result = $action->execute($order, [
        'notes' => 'Approved by manager',
        'notify_customer' => true,
        'approved_by' => 1,
        'approved_at' => now(),
    ]);
    
    expect($result->status)->toBe('approved');
    expect($result->approved_at)->not->toBeNull();
    expect($result->approval_notes)->toBe('Approved by manager');
});

it('sends notification when requested', function () {
    Mail::fake();
    
    $inventory = Mockery::mock(InventoryService::class);
    $inventory->shouldReceive('reserveForOrder')->once();
    
    $action = new ApproveOrder($inventory);
    
    $order = Order::factory()->create();
    
    $action->execute($order, [
        'notify_customer' => true,
        'approved_by' => 1,
        'approved_at' => now(),
    ]);
    
    Mail::assertSent(OrderApprovedMail::class);
});

it('does not send notification when not requested', function () {
    Mail::fake();
    
    $inventory = Mockery::mock(InventoryService::class);
    $inventory->shouldReceive('reserveForOrder')->once();
    
    $action = new ApproveOrder($inventory);
    
    $order = Order::factory()->create();
    
    $action->execute($order, [
        'notify_customer' => false,
        'approved_by' => 1,
        'approved_at' => now(),
    ]);
    
    Mail::assertNothingSent();
});
```

### Testing Controllers (Feature Tests)

```php
<?php

use App\Models\Order;
use App\Models\User;

it('approves order via HTTP request', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('approve_orders');
    
    $order = Order::factory()->create(['status' => 'pending']);
    
    $this->actingAs($user)
        ->post(route('orders.approve', $order), [
            'notes' => 'Looks good',
            'notify_customer' => true,
        ])
        ->assertRedirect(route('orders.show', $order))
        ->assertSessionHas('success');
    
    expect($order->fresh()->status)->toBe('approved');
});

it('requires authorization to approve order', function () {
    $user = User::factory()->create(); // No permission
    $order = Order::factory()->create();
    
    $this->actingAs($user)
        ->post(route('orders.approve', $order), [
            'notes' => 'Test',
        ])
        ->assertForbidden();
});

it('validates approval notes length', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('approve_orders');
    
    $order = Order::factory()->create();
    
    $this->actingAs($user)
        ->post(route('orders.approve', $order), [
            'notes' => str_repeat('a', 1001), // Too long
        ])
        ->assertSessionHasErrors('notes');
});
```

## Filament Integration

### Using Actions in Filament Resources

```php
<?php

namespace App\Filament\Resources;

use App\Actions\Orders\ApproveOrder;
use App\Actions\Orders\CancelOrder;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
```

```php
<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Actions\Orders\ApproveOrder;
use App\Actions\Orders\CancelOrder;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label(__('app.actions.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => $record->status === 'pending')
                ->form([
                    Textarea::make('notes')
                        ->label(__('app.labels.approval_notes'))
                        ->maxLength(1000),
                    Toggle::make('notify_customer')
                        ->label(__('app.labels.notify_customer'))
                        ->default(true),
                ])
                ->action(function ($record, array $data, ApproveOrder $action) {
                    try {
                        $action->execute($record, [
                            ...$data,
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                        
                        Notification::make()
                            ->title(__('app.notifications.order_approved'))
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('app.notifications.approval_failed'))
                            ->danger()
                            ->body($e->getMessage())
                            ->send();
                    }
                })
                ->requiresConfirmation(),
                
            Action::make('cancel')
                ->label(__('app.actions.cancel'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn ($record) => in_array($record->status, ['pending', 'approved']))
                ->form([
                    Textarea::make('reason')
                        ->label(__('app.labels.cancellation_reason'))
                        ->required()
                        ->maxLength(1000),
                ])
                ->action(function ($record, array $data, CancelOrder $action) {
                    $action->execute($record, $data);
                    
                    Notification::make()
                        ->title(__('app.notifications.order_cancelled'))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation(),
        ];
    }
}
```

## Migration Checklist

### Step 1: Identify Candidates
- [ ] Controllers with methods > 30 lines
- [ ] Complex business logic in controllers
- [ ] Multiple service dependencies
- [ ] Difficult to test methods
- [ ] Repeated logic across controllers

### Step 2: Plan Refactoring
- [ ] Identify business logic to extract
- [ ] Determine service dependencies
- [ ] Plan action class structure
- [ ] Design Form Request validation
- [ ] Plan test coverage

### Step 3: Create New Classes
- [ ] Create Action class in `app/Actions/{Domain}/`
- [ ] Create Form Request in `app/Http/Requests/`
- [ ] Create Single Action Controller
- [ ] Update route definition
- [ ] Register services if needed

### Step 4: Write Tests
- [ ] Unit tests for action class
- [ ] Feature tests for controller
- [ ] Test authorization
- [ ] Test validation
- [ ] Test error handling

### Step 5: Deploy
- [ ] Run full test suite
- [ ] Update documentation
- [ ] Deploy to staging
- [ ] Verify functionality
- [ ] Deploy to production

### Step 6: Cleanup
- [ ] Remove old controller method
- [ ] Remove unused routes
- [ ] Update API documentation
- [ ] Archive old tests

## Common Pitfalls

### ❌ Returning Responses from Actions
```php
// DON'T
public function execute(Order $order): RedirectResponse
{
    // ...
    return redirect()->route('orders.show', $order);
}

// DO
public function execute(Order $order): Order
{
    // ...
    return $order->fresh();
}
```

### ❌ Using Static Methods
```php
// DON'T
class ApproveOrder
{
    public static function execute(Order $order): void
    {
        // Hard to test, can't inject dependencies
    }
}

// DO
class ApproveOrder
{
    public function __construct(
        private readonly InventoryService $inventory
    ) {}
    
    public function execute(Order $order): Order
    {
        // Testable, injectable dependencies
    }
}
```

### ❌ Mixing HTTP Concerns in Actions
```php
// DON'T
public function execute(Request $request, Order $order): Order
{
    $validated = $request->validate([...]);
    // ...
}

// DO
public function execute(Order $order, array $data): Order
{
    // Pure business logic, no HTTP concerns
}
```

## Best Practices Summary

### Controllers Should:
- ✅ Handle HTTP request/response
- ✅ Delegate to Form Requests for validation
- ✅ Delegate to Actions for business logic
- ✅ Return appropriate HTTP responses
- ✅ Handle exceptions gracefully

### Actions Should:
- ✅ Contain business logic
- ✅ Accept plain PHP types (arrays, models)
- ✅ Return models or DTOs
- ✅ Use dependency injection
- ✅ Be framework-agnostic

### Form Requests Should:
- ✅ Handle validation rules
- ✅ Handle authorization
- ✅ Prepare data for actions
- ✅ Provide custom error messages
- ✅ Define attribute names

## Related Documentation
- `.kiro/steering/controller-refactoring.md` - Steering rules
- `.kiro/steering/laravel-conventions.md` - Laravel conventions
- `.kiro/steering/laravel-container-services.md` - Service patterns
- `.kiro/steering/filament-conventions.md` - Filament integration
- `.kiro/steering/testing-standards.md` - Testing patterns
- `docs/laravel-validation-enhancements.md` - Validation patterns
- `docs/laravel-pipeline-integration.md` - Pipeline patterns

## Quick Reference

### Directory Structure
```
app/
├── Actions/
│   ├── Orders/
│   │   ├── ApproveOrder.php
│   │   ├── CancelOrder.php
│   │   └── ProcessPayment.php
│   ├── Customers/
│   │   ├── MergeCustomers.php
│   │   └── ExportCustomers.php
│   └── Invoices/
│       ├── GenerateInvoice.php
│       └── SendInvoice.php
├── Http/
│   ├── Controllers/
│   │   ├── ApproveOrderController.php
│   │   ├── CancelOrderController.php
│   │   └── ...
│   └── Requests/
│       ├── ApproveOrderRequest.php
│       ├── CancelOrderRequest.php
│       └── ...
└── Services/
    ├── Inventory/
    ├── Email/
    └── Analytics/
```

### Command Templates

```bash
# Create Action
php artisan make:class Actions/Orders/ApproveOrder

# Create Form Request
php artisan make:request ApproveOrderRequest

# Create Controller
php artisan make:controller ApproveOrderController --invokable

# Create Test
php artisan make:test Actions/Orders/ApproveOrderTest --unit
php artisan make:test Controllers/ApproveOrderControllerTest
```
