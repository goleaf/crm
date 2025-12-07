<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CreationSource;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderLineItem;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final class OrderInvoiceService
{
    public function createFromOrder(Order $order, ?string $templateKey = null): Invoice
    {
        return DB::transaction(function () use ($order, $templateKey): Invoice {
            $invoice = Invoice::create([
                'team_id' => $order->team_id,
                'creator_id' => $order->creator_id,
                'company_id' => $order->company_id,
                'contact_id' => $order->contact_id,
                'opportunity_id' => $order->opportunity_id,
                'order_id' => $order->getKey(),
                'issue_date' => $order->ordered_at ?? Date::now(),
                'due_date' => $order->fulfillment_due_at ?? ($order->ordered_at ?? Date::now())->copy()->addDays(30),
                'payment_terms' => $order->payment_terms,
                'currency_code' => $order->currency_code,
                'fx_rate' => $order->fx_rate,
                'discount_total' => $order->discount_total,
                'status' => InvoiceStatus::DRAFT,
                'template_key' => $templateKey ?? $order->invoice_template_key ?? config('invoices.default_template', 'standard'),
                'notes' => $order->notes,
                'terms' => $order->terms,
                'creation_source' => CreationSource::SYSTEM,
            ]);

            $items = $order->lineItems()->orderBy('sort_order')->get();

            if ($items->isEmpty() && is_array($order->line_items)) {
                $items = collect($order->line_items)->map(fn (array $item): OrderLineItem => tap(new OrderLineItem, function (OrderLineItem $line) use ($item): void {
                    $line->name = $item['name'] ?? 'Line item';
                    $line->description = $item['description'] ?? null;
                    $line->quantity = (float) ($item['quantity'] ?? 1);
                    $line->unit_price = (float) ($item['unit_price'] ?? 0);
                    $line->tax_rate = (float) ($item['tax_rate'] ?? 0);
                    $line->sort_order = (int) ($item['sort_order'] ?? 0);
                }));
            }

            $items->each(function (OrderLineItem $item) use ($invoice): void {
                $invoice->lineItems()->create([
                    'team_id' => $invoice->team_id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'tax_rate' => $item->tax_rate,
                    'sort_order' => $item->sort_order,
                ]);
            });

            $invoice->syncFinancials('Invoice generated from order '.$order->number);
            $order->markInvoiced($invoice);

            return $invoice;
        });
    }
}
