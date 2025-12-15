<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; margin: 24px; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        .muted { color: #475569; font-size: 12px; }
        .totals { margin-top: 16px; width: 40%; float: right; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 8px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #f8fafc; font-size: 12px; color: #334155; }
    </style>
</head>
<body>
@php
    $currency = $invoice->currency_code ?? 'USD';
    $money = fn (float $value): string => $currency.' '.number_format($value, 2);
@endphp

<div style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1>Invoice {{ $invoice->number }}</h1>
        <div class="muted">Issued {{ optional($invoice->issue_date)->toFormattedDateString() ?? '—' }}</div>
        <div class="muted">Due {{ optional($invoice->due_date)->toFormattedDateString() ?? '—' }}</div>
        @if($invoice->order)
            <div class="muted">Order {{ $invoice->order->number }}</div>
        @endif
    </div>
    <div style="text-align: right;">
        <div class="muted">Status: {{ $invoice->status->label() }}</div>
        <div class="muted">Template: {{ $templateKey }}</div>
    </div>
</div>

<div style="margin-top: 16px;">
    <div class="muted">Bill To</div>
    <div>{{ $invoice->company->name ?? '—' }}</div>
    <div class="muted">{{ $invoice->contact->name ?? '' }}</div>
</div>

<table>
    <thead>
    <tr>
        <th style="width: 50%;">Item</th>
        <th style="width: 10%;">Qty</th>
        <th style="width: 15%;">Unit</th>
        <th style="width: 10%;">Tax %</th>
        <th style="width: 15%;">Total</th>
    </tr>
    </thead>
    <tbody>
    @forelse($invoice->lineItems as $line)
        <tr>
            <td>
                <div>{{ $line->name }}</div>
                @if($line->description)
                    <div class="muted">{{ $line->description }}</div>
                @endif
            </td>
            <td>{{ number_format((float) $line->quantity, 2) }}</td>
            <td>{{ $money((float) $line->unit_price) }}</td>
            <td>{{ number_format((float) $line->tax_rate, 2) }}%</td>
            <td>{{ $money((float) $line->line_total + (float) $line->tax_total) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="muted">No items added.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<div class="totals">
    <table>
        <tr>
            <td class="muted">Subtotal</td>
            <td style="text-align: right;">{{ $money((float) $invoice->subtotal) }}</td>
        </tr>
        <tr>
            <td class="muted">Discounts</td>
            <td style="text-align: right;">- {{ $money((float) $invoice->discount_total) }}</td>
        </tr>
        <tr>
            <td class="muted">Tax</td>
            <td style="text-align: right;">{{ $money((float) $invoice->tax_total) }}</td>
        </tr>
        <tr>
            <td class="muted">Paid</td>
            <td style="text-align: right;">- {{ $money((float) $invoice->payments->where('status', 'completed')->sum('amount')) }}</td>
        </tr>
        <tr>
            <th>Total</th>
            <th style="text-align: right;">{{ $money((float) $invoice->total) }}</th>
        </tr>
        <tr>
            <th>Balance</th>
            <th style="text-align: right;">{{ $money((float) $invoice->balance_due) }}</th>
        </tr>
    </table>
</div>

<div style="clear: both;"></div>

<div style="margin-top: 20px;">
    <div class="muted">Terms</div>
    <div>{{ $invoice->payment_terms ?? 'Payment terms not specified.' }}</div>
</div>
</body>
</html>
