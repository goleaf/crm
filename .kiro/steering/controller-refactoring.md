# Laravel Controller Refactoring Patterns

> **📚 Comprehensive Guide**: See `docs/controller-refactoring-guide.md` for detailed examples, testing patterns, and migration strategies.

## Core Principles
- Controllers should be thin and focused on HTTP concerns (request/response handling)
- Business logic belongs in Action classes, Services, or Jobs
- Use Single Action Controllers for complex operations
- Form Requests handle validation, authorization, and data preparation
- Follow the "fat model, skinny controller" principle with modern service layer patterns

## Single Action Controllers

### When to Use
- Complex operations that don't fit standard CRUD patterns
- Operations requiring multiple steps or external service calls
- Actions that need extensive testing in isolation
- When a controller method grows beyond 20-30 lines

### Pattern
```php
// app/Http/Controllers/ApproveOrderController.php
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
        $action->execute($order, $request->validated());
        
        return redirect()
            ->route('orders.show', $order)
            ->with('success', __('app.messages.order_approved'));
    }
}
```

### Benefits
- ✅ Clear, descriptive class names (ApproveOrderController vs OrderController@approve)
- ✅ Easy to test in isolation
- ✅ Simple routing: `Route::post('/orders/{order}/approve', ApproveOrderController::class)`
- ✅ No method name conflicts or confusion

## Action Classes

### Structure
```php
// app/Actions/Orders/ApproveOrder.php
namespace App\Actions\Orders;

use App\Events\OrderApproved;
use App\Models\Order;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\DB;

class ApproveOrder
{
    public function __construct(
        private readonly NotificationService $notifications
    ) {}
    
    public function execute(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $order->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'approval_notes' => $data['notes'] ?? null,
            ]);
            
            event(new OrderApproved($order));
            
            $this->notifications->notifyOrderApproved($order);
            
            return $order->fresh();
        });
    }
}
```

### Organization
```
app/Actions/
├── Orders/
│   ├── ApproveOrder.php
│   ├── CancelOrder.php
│   ├── CreateOrder.php
│   └── RefundOrder.php
├── Customers/
│   ├── MergeCustomers.php
│   ├── ExportCustomers.php
│   └── ImportCustomers.php
└── Invoices/
    ├── GenerateInvoice.php
    ├── SendInvoice.php
    └── MarkInvoicePaid.php
```

## Form Request Pattern

### Enhanced Form Requests
```php
// app/Http/Requests/ApproveOrderRequest.php
namespace App\Http\Requests;

use App\Models\Order;
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
            'notes' => 'nullable|string|max:1000',
            'notify_customer' => 'boolean',
        ];
    }
    
    public function messages(): array
    {
        return [
            'notes.max' => __('validation.custom.notes.max'),
        ];
    }
    
    // Prepare data for action
    public function preparedData(): array
    {
        return [
            'notes' => $this->input('notes'),
            'notify_customer' => $this->boolean('notify_customer'),
            'approved_by' => $this->user()->id,
            'approved_at' => now(),
        ];
    }
}
```

## Service Layer Integration

### When to Use Services vs Actions
- **Actions**: Single, focused operations (ApproveOrder, SendInvoice)
- **Services**: Multiple related operations or complex business logic (OrderService, PaymentService)

### Action with Service Dependencies
```php
// app/Actions/Orders/ProcessPayment.php
namespace App\Actions\Orders;

use App\Models\Order;
use App\Services\Payment\PaymentService;
use App\Services\Inventory\InventoryService;

class ProcessPayment
{
    public function __construct(
        private readonly PaymentService $payment,
        private readonly InventoryService $inventory
    ) {}
    
    public function execute(Order $order, array $paymentData): bool
    {
        // Charge payment
        $charge = $this->payment->charge(
            $order->total,
            $paymentData['payment_method']
        );
        
        if (!$charge->successful()) {
            return false;
        }
        
        // Reserve inventory
        $this->inventory->reserve($order->items);
        
        // Update order
        $order->update([
            'status' => 'paid',
            'payment_id' => $charge->id,
        ]);
        
        return true;
    }
}
```

## Filament Integration

### Resource Actions with Action Classes
```php
// app/Filament/Resources/OrderResource.php
use App\Actions\Orders\ApproveOrder;
use Filament\Actions\Action;

protected function getHeaderActions(): array
{
    return [
        Action::make('approve')
            ->label(__('app.actions.approve_order'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->form([
                Textarea::make('notes')
                    ->label(__('app.labels.approval_notes')),
                Toggle::make('notify_customer')
                    ->label(__('app.labels.notify_customer'))
                    ->default(true),
            ])
            ->action(function (Order $record, array $data, ApproveOrder $action) {
                try {
                    $action->execute($record, $data);
                    
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
    ];
}
```

## Testing Patterns

### Testing Actions
```php
// tests/Unit/Actions/Orders/ApproveOrderTest.php
use App\Actions\Orders\ApproveOrder;
use App\Models\Order;
use App\Services\Notifications\NotificationService;

it('approves order and sends notification', function () {
    $notifications = Mockery::mock(NotificationService::class);
    $notifications->shouldReceive('notifyOrderApproved')->once();
    
    $action = new ApproveOrder($notifications);
    
    $order = Order::factory()->create(['status' => 'pending']);
    
    $result = $action->execute($order, [
        'notes' => 'Approved by manager',
    ]);
    
    expect($result->status)->toBe('approved');
    expect($result->approved_at)->not->toBeNull();
});
```

### Testing Controllers
```php
// tests/Feature/Controllers/ApproveOrderControllerTest.php
use App\Models\Order;

it('approves order via controller', function () {
    $user = User::factory()->create();
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
```

## Migration Strategy

### Step 1: Identify Candidates
Look for controllers with:
- Methods longer than 30 lines
- Complex business logic
- Multiple service dependencies
- Difficult to test methods

### Step 2: Extract Actions
```php
// Before: Fat Controller
class OrderController extends Controller
{
    public function approve(Request $request, Order $order)
    {
        $request->validate(['notes' => 'nullable|string']);
        
        DB::transaction(function () use ($order, $request) {
            $order->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);
            
            event(new OrderApproved($order));
            
            Mail::to($order->customer)->send(new OrderApprovedMail($order));
        });
        
        return redirect()->route('orders.show', $order);
    }
}

// After: Thin Controller + Action
class ApproveOrderController extends Controller
{
    public function __invoke(
        ApproveOrderRequest $request,
        Order $order,
        ApproveOrder $action
    ) {
        $action->execute($order, $request->validated());
        
        return redirect()
            ->route('orders.show', $order)
            ->with('success', __('app.messages.order_approved'));
    }
}
```

### Step 3: Update Routes
```php
// Before
Route::post('/orders/{order}/approve', [OrderController::class, 'approve']);

// After
Route::post('/orders/{order}/approve', ApproveOrderController::class);
```

### Step 4: Update Tests
- Test actions in isolation (unit tests)
- Test controllers for HTTP concerns (feature tests)
- Mock action dependencies in controller tests

## Best Practices

### DO:
- ✅ Use single action controllers for complex operations
- ✅ Extract business logic to Action classes
- ✅ Inject dependencies via constructor
- ✅ Use Form Requests for validation and authorization
- ✅ Keep controllers focused on HTTP concerns
- ✅ Test actions and controllers separately
- ✅ Use descriptive action names (ApproveOrder, not ProcessOrder)
- ✅ Return models or DTOs from actions, not responses

### DON'T:
- ❌ Put business logic in controllers
- ❌ Use static methods in actions (breaks testability)
- ❌ Skip Form Requests for validation
- ❌ Return responses from actions
- ❌ Mix HTTP concerns with business logic
- ❌ Create god actions that do too much
- ❌ Forget to use transactions for multi-step operations

## Directory Structure

```
app/
├── Actions/
│   ├── Orders/
│   ├── Customers/
│   ├── Invoices/
│   └── Payments/
├── Http/
│   ├── Controllers/
│   │   ├── ApproveOrderController.php
│   │   ├── CancelOrderController.php
│   │   └── ...
│   └── Requests/
│       ├── ApproveOrderRequest.php
│       └── ...
└── Services/
    ├── Payment/
    ├── Notifications/
    └── ...
```

## Integration with Existing Patterns

### Works With
- ✅ Service Container Pattern (inject actions/services)
- ✅ Filament Actions (use action classes in Filament)
- ✅ Queue Jobs (dispatch actions from jobs)
- ✅ Event Listeners (call actions in listeners)
- ✅ Form Requests (validation before action execution)

### Example: Complete Flow
```php
// 1. Route
Route::post('/orders/{order}/approve', ApproveOrderController::class)
    ->middleware(['auth', 'can:approve,order']);

// 2. Controller
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

// 3. Form Request
class ApproveOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('approve', $this->route('order'));
    }
    
    public function rules(): array
    {
        return ['notes' => 'nullable|string|max:1000'];
    }
    
    public function preparedData(): array
    {
        return [
            'notes' => $this->input('notes'),
            'approved_by' => $this->user()->id,
        ];
    }
}

// 4. Action
class ApproveOrder
{
    public function __construct(
        private readonly NotificationService $notifications
    ) {}
    
    public function execute(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $order->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $data['approved_by'],
                'approval_notes' => $data['notes'] ?? null,
            ]);
            
            event(new OrderApproved($order));
            
            $this->notifications->notifyOrderApproved($order);
            
            return $order->fresh();
        });
    }
}
```

## Related Documentation
- `docs/controller-refactoring-guide.md` - Complete refactoring guide
- `docs/laravel-container-services.md` - Service pattern guidelines
- `.kiro/steering/laravel-conventions.md` - Laravel conventions
- `.kiro/steering/filament-conventions.md` - Filament integration
- `.kiro/steering/testing-standards.md` - Testing patterns

## Quick Reference

### When to Refactor
- Controller method > 30 lines
- Complex business logic in controller
- Difficult to test controller methods
- Multiple service dependencies
- Repeated logic across controllers

### Refactoring Checklist
1. ✅ Create Action class in `app/Actions/{Domain}/`
2. ✅ Extract business logic to action
3. ✅ Create Form Request for validation
4. ✅ Create Single Action Controller
5. ✅ Update route to use new controller
6. ✅ Write unit tests for action
7. ✅ Write feature tests for controller
8. ✅ Update documentation
9. ✅ Remove old controller method
10. ✅ Run full test suite

### Common Patterns
- **CRUD Operations**: Keep in resource controllers
- **Complex Operations**: Extract to single action controllers
- **Multi-Step Processes**: Use action classes with service dependencies
- **External API Calls**: Wrap in service, call from action
- **Background Jobs**: Dispatch from action, not controller
