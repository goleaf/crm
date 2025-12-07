<?php

declare(strict_types=1);

use App\Enums\OrderFulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\InvoicePayment;
use App\Models\Order;
use App\Models\OrderLineItem;
use App\Services\OrderInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('calculates totals and fulfillment status from line items', function (): void {
    $order = Order::factory()->create([
        'ordered_at' => Carbon::now(),
        'currency_code' => 'EUR',
    ]);

    $line1 = OrderLineItem::factory()->create([
        'order_id' => $order->id,
        'team_id' => $order->team_id,
        'quantity' => 2,
        'unit_price' => 100,
        'tax_rate' => 10,
    ]);

    $line2 = OrderLineItem::factory()->create([
        'order_id' => $order->id,
        'team_id' => $order->team_id,
        'quantity' => 1,
        'unit_price' => 50,
        'tax_rate' => 0,
    ]);

    $order->refresh();

    expect((float) $order->subtotal)->toBe(250.00)
        ->and((float) $order->tax_total)->toBe(20.00)
        ->and((float) $order->total)->toBe(270.00)
        ->and($order->fulfillment_status)->toBe(OrderFulfillmentStatus::PENDING)
        ->and($order->status)->toBe(OrderStatus::DRAFT)
        ->and($order->currency_code)->toBe('EUR');

    $line1->update(['fulfilled_quantity' => 1]);
    $order->refresh();

    expect($order->fulfillment_status)->toBe(OrderFulfillmentStatus::PARTIAL)
        ->and($order->status)->toBe(OrderStatus::DRAFT);

    $line1->update(['fulfilled_quantity' => 2]);
    $line2->update(['fulfilled_quantity' => 1]);
    $order->refresh();

    expect($order->fulfillment_status)->toBe(OrderFulfillmentStatus::FULFILLED)
        ->and($order->status)->toBe(OrderStatus::FULFILLED);
});

it('generates an invoice from an order and syncs payments to balance', function (): void {
    $order = Order::factory()->create([
        'payment_terms' => 'Net 15',
    ]);

    OrderLineItem::factory()->create([
        'order_id' => $order->id,
        'team_id' => $order->team_id,
        'quantity' => 1,
        'unit_price' => 200,
        'tax_rate' => 5,
    ]);

    $service = app(OrderInvoiceService::class);
    $invoice = $service->createFromOrder($order, 'minimal');

    $order->refresh();
    $invoice->refresh();

    $order->refresh()->load('invoices');

    expect($invoice->order_id)->toBe($order->id)
        ->and($order->invoices)->toHaveCount(1)
        ->and($order->status)->toBe(OrderStatus::INVOICED)
        ->and($invoice->template_key)->toBe('minimal')
        ->and((float) $invoice->total)->toBe(210.00)
        ->and((float) $order->balance_due)->toBe(210.00);

    InvoicePayment::factory()->create([
        'invoice_id' => $invoice->id,
        'team_id' => $invoice->team_id,
        'amount' => 110,
    ]);

    $order->refresh();
    $invoice->refresh();

    expect((float) $order->balance_due)->toBe(100.00)
        ->and((float) $invoice->balance_due)->toBe(100.00);

    InvoicePayment::factory()->create([
        'invoice_id' => $invoice->id,
        'team_id' => $invoice->team_id,
        'amount' => 100,
    ]);

    $order->refresh();
    $invoice->refresh();

    expect((float) $order->balance_due)->toBe(0.00)
        ->and((float) $invoice->balance_due)->toBe(0.00);
});
