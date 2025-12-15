<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            margin: 24px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .meta {
            font-size: 12px;
            color: #475569;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th, td {
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f8fafc;
            font-size: 12px;
            color: #334155;
        }
        .totals {
            width: 40%;
            float: right;
            margin-top: 16px;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 12px;
            color: #0f172a;
            background: #e2e8f0;
        }
        .section-title {
            margin-top: 24px;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: bold;
        }
        .text-muted { color: #475569; }
    </style>
</head>
<body>
@php
    $currency = $invoice->currency_code ?? 'USD';
    $money = fn (float $value): string => $currency.' '.number_format($value, 2);
@endphp
<div class="header">
    <div>
        <h1>Invoice {{ $invoice->number }}</h1>
        <div class="meta">Issued {{ optional($invoice->issue_date)->toFormattedDateString() ?? '—' }}</div>
        <div class="meta">Due {{ optional($invoice->due_date)->toFormattedDateString() ?? '—' }}</div>
        <div class="meta">Payment Terms: {{ $invoice->payment_terms ?? '—' }}</div>
    </div>
    <div>
        <span class="badge">{{ $invoice->status->label() ?? 'Draft' }}</span>
    </div>
</div>

<div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
    <div>
        <div class="section-title">Bill To</div>
        <div>{{ $invoice->company->name ?? '—' }}</div>
        <div class="text-muted">{{ $invoice->contact->name ?? '' }}</div>
        <div class="text-muted">{{ $invoice->company->primary_email ?? '' }}</div>
    </div>
    <div>
        <div class="section-title">Details</div>
        <div class="text-muted">Currency: {{ $currency }}</div>
        @if($invoice->opportunity)
            <div class="text-muted">Opportunity: {{ $invoice->opportunity->name }}</div>
        @endif
        @if($invoice->template_key)
            <div class="text-muted">Template: {{ $invoice->template_key }}</div>
        @endif
    </div>
</div>

<table>
    <thead>
    <tr>
        <th style="width: 45%;">Item</th>
        <th style="width: 12%;">Qty</th>
        <th style="width: 15%;">Unit Price</th>
        <th style="width: 12%;">Tax %</th>
        <th style="width: 16%;">Line Total</th>
    </tr>
    </thead>
    <tbody>
    @forelse($invoice->lineItems as $line)
        <tr>
            <td>
                <div>{{ $line->name }}</div>
                @if($line->description)
                    <div class="text-muted" style="font-size: 12px;">{{ $line->description }}</div>
                @endif
            </td>
            <td>{{ number_format((float) $line->quantity, 2) }}</td>
            <td>{{ $money((float) $line->unit_price) }}</td>
            <td>{{ number_format((float) $line->tax_rate, 2) }}%</td>
            <td>{{ $money((float) $line->line_total + (float) $line->tax_total) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="text-muted">No line items added yet.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<div class="totals">
    <table>
        <tr>
            <td class="text-muted">Subtotal</td>
            <td style="text-align: right;">{{ $money((float) $invoice->subtotal) }}</td>
        </tr>
        <tr>
            <td class="text-muted">Discounts</td>
            <td style="text-align: right;">- {{ $money((float) $invoice->discount_total) }}</td>
        </tr>
        <tr>
            <td class="text-muted">Tax</td>
            <td style="text-align: right;">{{ $money((float) $invoice->tax_total) }}</td>
        </tr>
        <tr>
            <td class="text-muted">Late Fees</td>
            <td style="text-align: right;">{{ $money((float) $invoice->late_fee_amount) }}</td>
        </tr>
        <tr>
            <td class="text-muted">Payments</td>
            <td style="text-align: right;">- {{ $money((float) $invoice->payments->where('status', 'completed')->sum('amount')) }}</td>
        </tr>
        <tr>
            <th>Total</th>
            <th style="text-align: right;">{{ $money((float) $invoice->total) }}</th>
        </tr>
        <tr>
            <th>Balance Due</th>
            <th style="text-align: right;">{{ $money((float) $invoice->balance_due) }}</th>
        </tr>
    </table>
</div>

<div style="clear: both;"></div>

<div class="section-title">Payment Terms</div>
<div class="text-muted">{{ $invoice->payment_terms ?? 'Payment terms not specified.' }}</div>

<div class="section-title">Notes</div>
<div class="text-muted">{{ $invoice->notes ?? 'No additional notes.' }}</div>

<div class="section-title">History</div>
<div class="text-muted">
    @forelse($invoice->statusHistories as $history)
        <div>
            {{ optional($history->created_at)->toDayDateTimeString() ?? '' }}:
            {{ $history->from_status?->label() ?? 'N/A' }} → {{ $history->to_status?->label() }}
            @if($history->note)
                — {{ $history->note }}
            @endif
        </div>
    @empty
        <div>No status changes recorded.</div>
    @endforelse
</div>
</body>
</html>
