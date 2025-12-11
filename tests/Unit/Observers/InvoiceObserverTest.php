<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
});

test('registers invoice number on creating', function (): void {
    $this->actingAs($this->user);

    $invoice = Invoice::factory()->make([
        'invoice_number' => null,
        'team_id' => $this->team->id,
    ]);

    $invoice->save();

    expect($invoice->invoice_number)->not()->toBeNull();
});

test('records status change on created', function (): void {
    $this->actingAs($this->user);

    $invoice = Invoice::factory()->create([
        'team_id' => $this->team->id,
        'status' => InvoiceStatus::DRAFT,
    ]);

    expect($invoice->statusHistories()->count())->toBe(1)
        ->and($invoice->statusHistories()->first()->to_status)->toBe(InvoiceStatus::DRAFT);
});

test('records status change on update', function (): void {
    $this->actingAs($this->user);

    $invoice = Invoice::factory()->create([
        'team_id' => $this->team->id,
        'status' => InvoiceStatus::DRAFT,
    ]);

    $invoice->update(['status' => InvoiceStatus::SENT]);

    expect($invoice->statusHistories()->count())->toBe(2)
        ->and($invoice->statusHistories()->latest()->first()->to_status)->toBe(InvoiceStatus::SENT)
        ->and($invoice->statusHistories()->latest()->first()->from_status)->toBe(InvoiceStatus::DRAFT);
});

test('syncs financials on created', function (): void {
    $this->actingAs($this->user);

    $invoice = Invoice::factory()->create([
        'team_id' => $this->team->id,
        'subtotal' => 1000.00,
        'tax_amount' => 100.00,
        'total' => 1100.00,
    ]);

    expect($invoice->fresh()->subtotal)->toBe(1000.00)
        ->and($invoice->fresh()->total)->toBe(1100.00);
});

test('syncs financials on saved', function (): void {
    $this->actingAs($this->user);

    $invoice = Invoice::factory()->create([
        'team_id' => $this->team->id,
        'subtotal' => 1000.00,
        'total' => 1100.00,
    ]);

    $invoice->update(['subtotal' => 2000.00]);

    expect($invoice->fresh()->subtotal)->toBe(2000.00);
});
