<?php

declare(strict_types=1);

use App\Enums\ProcessApprovalStatus;
use App\Enums\PurchaseOrderReceiptType;
use App\Enums\PurchaseOrderStatus;
use App\Livewire\PurchaseOrders\Form;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderApproval;
use App\Models\PurchaseOrderLineItem;
use App\Models\PurchaseOrderReceipt;
use App\Models\Team;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('syncs purchase order totals and receipt-driven statuses', function (): void {
    $team = Team::factory()->create();
    $vendor = Vendor::factory()->create(['team_id' => $team->id]);

    $purchaseOrder = PurchaseOrder::factory()->create([
        'team_id' => $team->id,
        'vendor_id' => $vendor->id,
    ]);

    $lineA = PurchaseOrderLineItem::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'team_id' => $team->id,
        'quantity' => 5,
        'unit_cost' => 100,
        'tax_rate' => 0,
    ]);

    $lineB = PurchaseOrderLineItem::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'team_id' => $team->id,
        'quantity' => 2,
        'unit_cost' => 50,
        'tax_rate' => 0,
    ]);

    $purchaseOrder->syncFinancials();
    $purchaseOrder->refresh();

    expect((float) $purchaseOrder->subtotal)->toBe(600.0)
        ->and((float) $purchaseOrder->tax_total)->toBe(0.0)
        ->and((float) $purchaseOrder->total)->toBe(600.0)
        ->and($purchaseOrder->status)->toBe(PurchaseOrderStatus::DRAFT);

    // Partial receipt keeps status at partially_received
    PurchaseOrderReceipt::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'purchase_order_line_item_id' => $lineA->id,
        'team_id' => $team->id,
        'receipt_type' => PurchaseOrderReceiptType::RECEIPT,
        'quantity' => 2,
        'unit_cost' => 100,
    ]);

    $purchaseOrder->syncFinancials();
    $purchaseOrder->refresh();

    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::PARTIALLY_RECEIVED)
        ->and((float) $purchaseOrder->received_cost)->toBe(200.0);

    // Receive remaining quantities for both lines
    PurchaseOrderReceipt::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'purchase_order_line_item_id' => $lineA->id,
        'team_id' => $team->id,
        'receipt_type' => PurchaseOrderReceiptType::RECEIPT,
        'quantity' => 3,
        'unit_cost' => 100,
    ]);

    PurchaseOrderReceipt::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'purchase_order_line_item_id' => $lineB->id,
        'team_id' => $team->id,
        'receipt_type' => PurchaseOrderReceiptType::RECEIPT,
        'quantity' => 2,
        'unit_cost' => 50,
    ]);

    $purchaseOrder->syncFinancials();
    $purchaseOrder->refresh();

    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::RECEIVED)
        ->and((float) $purchaseOrder->received_cost)->toBe(600.0)
        ->and((float) $purchaseOrder->outstanding_commitment)->toBe(0.0);
});

it('syncs approval state transitions', function (): void {
    $purchaseOrder = PurchaseOrder::factory()->create();

    $pending = PurchaseOrderApproval::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'team_id' => $purchaseOrder->team_id,
        'status' => ProcessApprovalStatus::PENDING,
    ]);

    $purchaseOrder->syncApprovalState();
    $purchaseOrder->refresh();

    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::PENDING_APPROVAL);

    $pending->update(['status' => ProcessApprovalStatus::APPROVED]);
    $purchaseOrder->syncApprovalState();
    $purchaseOrder->refresh();

    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::APPROVED)
        ->and($purchaseOrder->approved_at)->not()->toBeNull();

    PurchaseOrderApproval::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'team_id' => $purchaseOrder->team_id,
        'status' => ProcessApprovalStatus::REJECTED,
    ]);

    $purchaseOrder->syncApprovalState();
    $purchaseOrder->refresh();

    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::CANCELLED);
});

it('creates purchase orders via Livewire form without page refresh', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->id]);
    $user->teams()->attach($team);

    $vendor = Vendor::factory()->create(['team_id' => $team->id]);
    $order = Order::factory()->create(['team_id' => $team->id]);

    $this->actingAs($user);

    $today = now()->toDateString();
    $expected = now()->addDays(5)->toDateString();

    Livewire::test(Form::class)
        ->set('vendor_id', $vendor->id)
        ->set('order_id', $order->id)
        ->set('ordered_at', $today)
        ->set('expected_delivery_date', $expected)
        ->set('currency_code', 'USD')
        ->set('payment_terms', 'Net 30')
        ->set('lineItems', [
            [
                'name' => 'Widgets',
                'description' => 'High quality widgets',
                'quantity' => 2,
                'unit_cost' => 50,
                'tax_rate' => 0,
                'expected_receipt_at' => $expected,
                'order_line_item_id' => null,
            ],
            [
                'name' => 'Cables',
                'description' => null,
                'quantity' => 3,
                'unit_cost' => 10,
                'tax_rate' => 0,
                'expected_receipt_at' => null,
                'order_line_item_id' => null,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $purchaseOrder = PurchaseOrder::query()->latest()->first();

    expect($purchaseOrder)->not->toBeNull()
        ->and($purchaseOrder->vendor_id)->toBe($vendor->id)
        ->and($purchaseOrder->order_id)->toBe($order->id)
        ->and((float) $purchaseOrder->subtotal)->toBe(130.0)
        ->and((float) $purchaseOrder->total)->toBe(130.0)
        ->and($purchaseOrder->lineItems()->count())->toBe(2);
});
