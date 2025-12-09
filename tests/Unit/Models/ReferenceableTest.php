<?php

declare(strict_types=1);

use App\Enums\OrderFulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLineItem;
use App\Models\PurchaseOrderReceipt;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

test('orders use referenceable counters and keep sequences in sync', function (): void {
    \Illuminate\Support\Facades\Date::setTestNow(\Illuminate\Support\Facades\Date::create(2026, 1, 15));

    $team = Team::factory()->create();

    Order::query()->create([
        'team_id' => $team->getKey(),
        'ordered_at' => \Illuminate\Support\Facades\Date::now(),
        'number' => 'ORD-2026-00007',
        'sequence' => 7,
        'status' => OrderStatus::DRAFT,
        'fulfillment_status' => OrderFulfillmentStatus::PENDING,
        'currency_code' => config('company.default_currency', 'USD'),
        'subtotal' => 0,
        'discount_total' => 0,
        'tax_total' => 0,
        'total' => 0,
        'balance_due' => 0,
        'paid_total' => 0,
        'invoiced_total' => 0,
    ]);

    $order = Order::factory()->create([
        'team_id' => $team->getKey(),
        'ordered_at' => \Illuminate\Support\Facades\Date::now(),
    ]);

    $counterValue = DB::table('model_reference_counters')
        ->where('key', Order::class . ':2026')
        ->value('value');

    expect($order->number)->toBe('ORD-2026-00008')
        ->and($order->sequence)->toBe(8)
        ->and($counterValue)->toBe(8);

    \Illuminate\Support\Facades\Date::setTestNow();
});

test('purchase order receipts generate reference codes with the template strategy', function (): void {
    \Illuminate\Support\Facades\Date::setTestNow(\Illuminate\Support\Facades\Date::create(2026, 2, 2));

    $purchaseOrder = PurchaseOrder::factory()->create();
    $lineItem = PurchaseOrderLineItem::factory()->create([
        'purchase_order_id' => $purchaseOrder->getKey(),
        'team_id' => $purchaseOrder->team_id,
    ]);

    $receipt = PurchaseOrderReceipt::factory()->create([
        'purchase_order_id' => $purchaseOrder->getKey(),
        'purchase_order_line_item_id' => $lineItem->getKey(),
        'team_id' => $purchaseOrder->team_id,
        'reference' => null,
    ]);

    expect($receipt->reference)->toBe('POR-2026-00001');

    \Illuminate\Support\Facades\Date::setTestNow();
});
