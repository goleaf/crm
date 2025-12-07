<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\InvoicePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('calculates totals, balance, and status from line items and payments', function (): void {
    $invoice = Invoice::factory()->create([
        'due_date' => Carbon::now()->addDays(10),
    ]);

    InvoiceLineItem::factory()->create([
        'invoice_id' => $invoice->id,
        'team_id' => $invoice->team_id,
        'quantity' => 2,
        'unit_price' => 100,
        'tax_rate' => 10,
    ]);

    InvoiceLineItem::factory()->create([
        'invoice_id' => $invoice->id,
        'team_id' => $invoice->team_id,
        'quantity' => 1,
        'unit_price' => 50,
        'tax_rate' => 0,
    ]);

    $invoice->syncFinancials();
    $invoice->refresh();

    expect((float) $invoice->subtotal)->toBe(250.00)
        ->and((float) $invoice->tax_total)->toBe(20.00)
        ->and((float) $invoice->total)->toBe(270.00)
        ->and((float) $invoice->balance_due)->toBe(270.00)
        ->and($invoice->status)->toBe(InvoiceStatus::DRAFT);

    InvoicePayment::factory()->create([
        'invoice_id' => $invoice->id,
        'team_id' => $invoice->team_id,
        'amount' => 100,
    ]);

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::PARTIAL)
        ->and((float) $invoice->balance_due)->toBe(170.00);

    InvoicePayment::factory()->create([
        'invoice_id' => $invoice->id,
        'team_id' => $invoice->team_id,
        'amount' => 170,
    ]);

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::PAID)
        ->and((float) $invoice->balance_due)->toBe(0.00);
});
