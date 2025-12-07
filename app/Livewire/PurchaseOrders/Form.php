<?php

declare(strict_types=1);

namespace App\Livewire\PurchaseOrders;

use App\Livewire\BaseLivewireComponent;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLineItem;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

final class Form extends BaseLivewireComponent
{
    public ?int $purchaseOrderId = null;

    public ?int $vendor_id = null;

    public ?int $order_id = null;

    public ?string $ordered_at = null;

    public ?string $expected_delivery_date = null;

    public ?string $payment_terms = null;

    public ?string $shipping_terms = null;

    public ?string $ship_method = null;

    public ?string $ship_to_address = null;

    public ?string $bill_to_address = null;

    public string $currency_code;

    public ?string $notes = null;

    public ?string $terms = null;

    /**
     * @var array<int, array{id?: int|null, name: string|null, description: string|null, quantity: float|int|string|null, unit_cost: float|int|string|null, tax_rate: float|int|string|null, expected_receipt_at: string|null, order_line_item_id: int|null}>
     */
    public array $lineItems = [];

    public function mount(?int $purchaseOrderId = null): void
    {
        $this->purchaseOrderId = $purchaseOrderId;
        $this->currency_code = config('company.default_currency', 'USD');
        $this->ordered_at ??= now()->toDateString();
        $this->expected_delivery_date ??= now()->addDays(7)->toDateString();

        if ($purchaseOrderId !== null) {
            $this->loadPurchaseOrder($purchaseOrderId);
        } else {
            $this->lineItems = [
                $this->emptyLineItem(),
            ];
        }
    }

    #[On('edit-purchase-order')]
    public function loadPurchaseOrder(int $purchaseOrderId): void
    {
        $teamId = auth('web')->user()?->currentTeam?->getKey();

        /** @var PurchaseOrder|null $purchaseOrder */
        $purchaseOrder = PurchaseOrder::query()
            ->with('lineItems')
            ->when($teamId !== null, fn (Builder $query): Builder => $query->where('team_id', $teamId))
            ->find($purchaseOrderId);

        if ($purchaseOrder === null) {
            return;
        }

        $this->purchaseOrderId = $purchaseOrder->id;
        $this->vendor_id = $purchaseOrder->vendor_id;
        $this->order_id = $purchaseOrder->order_id;
        $this->ordered_at = optional($purchaseOrder->ordered_at)?->toDateString();
        $this->expected_delivery_date = optional($purchaseOrder->expected_delivery_date)?->toDateString();
        $this->payment_terms = $purchaseOrder->payment_terms;
        $this->shipping_terms = $purchaseOrder->shipping_terms;
        $this->ship_method = $purchaseOrder->ship_method;
        $this->ship_to_address = $purchaseOrder->ship_to_address;
        $this->bill_to_address = $purchaseOrder->bill_to_address;
        $this->currency_code = $purchaseOrder->currency_code;
        $this->notes = $purchaseOrder->notes;
        $this->terms = $purchaseOrder->terms;

        $this->lineItems = $purchaseOrder->lineItems
            ->map(fn (PurchaseOrderLineItem $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_cost' => (float) $item->unit_cost,
                'tax_rate' => (float) $item->tax_rate,
                'expected_receipt_at' => optional($item->expected_receipt_at)?->toDateString(),
                'order_line_item_id' => $item->order_line_item_id,
            ])
            ->toArray();
    }

    /**
     * @return array<string, array<int|string|Rule>>
     */
    private function rules(): array
    {
        return [
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'ordered_at' => ['nullable', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:ordered_at'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'shipping_terms' => ['nullable', 'string', 'max:255'],
            'ship_method' => ['nullable', 'string', 'max:255'],
            'ship_to_address' => ['nullable', 'string'],
            'bill_to_address' => ['nullable', 'string'],
            'currency_code' => ['required', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'lineItems' => ['required', 'array', 'min:1'],
            'lineItems.*.id' => ['nullable', 'integer'],
            'lineItems.*.name' => ['required', 'string', 'max:255'],
            'lineItems.*.description' => ['nullable', 'string'],
            'lineItems.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lineItems.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'lineItems.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
            'lineItems.*.expected_receipt_at' => ['nullable', 'date'],
            'lineItems.*.order_line_item_id' => ['nullable', 'integer', 'exists:order_line_items,id'],
        ];
    }

    public function addLineItem(): void
    {
        $this->lineItems[] = $this->emptyLineItem();
    }

    public function removeLineItem(int $index): void
    {
        unset($this->lineItems[$index]);
        $this->lineItems = array_values($this->lineItems);
    }

    public function save(): void
    {
        $data = $this->validate();
        $teamId = auth('web')->user()?->currentTeam?->getKey();

        $purchaseOrder = $this->purchaseOrderId !== null
            ? PurchaseOrder::query()
                ->when($teamId !== null, fn (Builder $query): Builder => $query->where('team_id', $teamId))
                ->findOrFail($this->purchaseOrderId)
            : new PurchaseOrder;

        DB::transaction(function () use ($purchaseOrder, $data, $teamId): void {
            $purchaseOrder->fill([
                'team_id' => $teamId ?? $purchaseOrder->team_id,
                'vendor_id' => $data['vendor_id'] ?? null,
                'order_id' => $data['order_id'] ?? null,
                'ordered_at' => $data['ordered_at'] ?? now(),
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? null,
                'shipping_terms' => $data['shipping_terms'] ?? null,
                'ship_method' => $data['ship_method'] ?? null,
                'ship_to_address' => $data['ship_to_address'] ?? null,
                'bill_to_address' => $data['bill_to_address'] ?? null,
                'currency_code' => $data['currency_code'],
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
            ]);

            $purchaseOrder->save();

            $lineIds = [];
            foreach ($data['lineItems'] as $line) {
                $lineItem = isset($line['id'])
                    ? PurchaseOrderLineItem::query()
                        ->where('purchase_order_id', $purchaseOrder->id)
                        ->find($line['id'])
                    : new PurchaseOrderLineItem;

                $lineItem->fill([
                    'purchase_order_id' => $purchaseOrder->id,
                    'team_id' => $purchaseOrder->team_id,
                    'name' => $line['name'],
                    'description' => $line['description'] ?? null,
                    'quantity' => $line['quantity'],
                    'unit_cost' => $line['unit_cost'],
                    'tax_rate' => $line['tax_rate'] ?? 0,
                    'expected_receipt_at' => $line['expected_receipt_at'] ?? null,
                    'order_line_item_id' => $line['order_line_item_id'] ?? null,
                ]);

                $lineItem->save();
                $lineIds[] = $lineItem->id;
            }

            PurchaseOrderLineItem::query()
                ->where('purchase_order_id', $purchaseOrder->id)
                ->whereNotIn('id', $lineIds)
                ->doesntHave('receipts')
                ->delete();

            $purchaseOrder->syncFinancials();
        });

        $this->dispatch('purchase-order-saved', id: $purchaseOrder->id);
        $this->sendNotification('Saved', 'Purchase order updated', 'success');
    }

    #[\Livewire\Attributes\Computed]
    public function subtotal(): float
    {
        return collect($this->lineItems)
            ->sum(fn (array $line): float => round(((float) ($line['quantity'] ?? 0)) * ((float) ($line['unit_cost'] ?? 0)), 2));
    }

    #[\Livewire\Attributes\Computed]
    public function taxTotal(): float
    {
        return collect($this->lineItems)
            ->sum(function (array $line): float {
                $lineTotal = ((float) ($line['quantity'] ?? 0)) * ((float) ($line['unit_cost'] ?? 0));

                return round($lineTotal * ((float) ($line['tax_rate'] ?? 0) / 100), 2);
            });
    }

    #[\Livewire\Attributes\Computed]
    public function total(): float
    {
        return max(round($this->getSubtotalProperty() + $this->getTaxTotalProperty(), 2), 0);
    }

    #[\Livewire\Attributes\Computed]
    public function vendorOptions(): array
    {
        return Vendor::query()
            ->where('team_id', auth('web')->user()?->currentTeam?->getKey())
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    #[\Livewire\Attributes\Computed]
    public function orderOptions(): array
    {
        return Order::query()
            ->select(['id', 'number', 'company_id'])
            ->with('company:id,name')
            ->where('team_id', auth('web')->user()?->currentTeam?->getKey())
            ->latest()
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Order $order): array => [$order->id => trim($order->number.' '.$order->company?->name ?? '')])
            ->toArray();
    }

    private function emptyLineItem(): array
    {
        return [
            'id' => null,
            'name' => null,
            'description' => null,
            'quantity' => 1,
            'unit_cost' => 0,
            'tax_rate' => 0,
            'expected_receipt_at' => null,
            'order_line_item_id' => null,
        ];
    }

    public function render(): View
    {
        return view('livewire.purchase-orders.form');
    }
}
