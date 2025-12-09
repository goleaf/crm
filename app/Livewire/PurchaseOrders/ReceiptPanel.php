<?php

declare(strict_types=1);

namespace App\Livewire\PurchaseOrders;

use App\Enums\PurchaseOrderReceiptType;
use App\Livewire\BaseLivewireComponent;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLineItem;
use App\Models\PurchaseOrderReceipt;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

final class ReceiptPanel extends BaseLivewireComponent
{
    public ?int $purchaseOrderId = null;

    public ?int $line_item_id = null;

    public string $receipt_type = PurchaseOrderReceiptType::RECEIPT->value;

    public ?string $received_at = null;

    public float $quantity = 1;

    public float $unit_cost = 0;

    public ?string $reference = null;

    public ?string $notes = null;

    public array $lineOptions = [];

    public ?float $outstanding = null;

    public function mount(?int $purchaseOrderId = null): void
    {
        $this->purchaseOrderId = $purchaseOrderId;
        $this->received_at = now()->toDateTimeString();

        if ($purchaseOrderId !== null) {
            $this->loadPurchaseOrder($purchaseOrderId);
        }
    }

    #[On('open-receipts')]
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
        $this->lineOptions = $purchaseOrder->lineItems
            ->mapWithKeys(function (PurchaseOrderLineItem $line): array {
                $openQty = max((float) $line->quantity - (float) $line->received_quantity, 0);
                $label = $line->name . ' (' . number_format($line->quantity, 2) . ' ordered, ' . number_format($line->received_quantity, 2) . ' received)';

                return [$line->id => $label];
            })
            ->toArray();

        $this->line_item_id = array_key_first($this->lineOptions);
        $this->applyLineDefaults();
        $this->outstanding = (float) $purchaseOrder->outstanding_commitment;
    }

    public function updatedLineItemId(): void
    {
        $this->applyLineDefaults();
    }

    public function saveReceipt(): void
    {
        $this->validate([
            'purchaseOrderId' => ['required', 'integer', 'exists:purchase_orders,id'],
            'line_item_id' => ['required', 'integer', 'exists:purchase_order_line_items,id'],
            'receipt_type' => ['required', 'string', 'in:receipt,return'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'received_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        auth('web')->user()?->currentTeam?->getKey();

        DB::transaction(function (): void {
            $lineItem = PurchaseOrderLineItem::query()
                ->where('id', $this->line_item_id)
                ->whereHas('purchaseOrder', fn (Builder $builder): Builder => $builder->where('id', $this->purchaseOrderId))
                ->firstOrFail();

            $purchaseOrder = $lineItem->purchaseOrder;

            $receipt = new PurchaseOrderReceipt;
            $receipt->fill([
                'purchase_order_id' => $purchaseOrder->id,
                'purchase_order_line_item_id' => $lineItem->id,
                'team_id' => $purchaseOrder->team_id,
                'receipt_type' => $this->receipt_type,
                'quantity' => $this->quantity,
                'unit_cost' => $this->unit_cost,
                'received_at' => $this->received_at,
                'reference' => $this->reference,
                'notes' => $this->notes,
            ]);

            $receipt->save();
            $purchaseOrder->refresh();
            $purchaseOrder->syncFinancials();
            $purchaseOrder->syncApprovalState();
            $this->outstanding = (float) $purchaseOrder->outstanding_commitment;
        });

        $this->dispatch('purchase-order-received', id: $this->purchaseOrderId);
        $this->sendNotification('Receipt recorded', 'Receipt saved without a page refresh', 'success');

        $this->resetReceiptFields();
    }

    private function applyLineDefaults(): void
    {
        if ($this->line_item_id === null) {
            return;
        }

        $lineItem = PurchaseOrderLineItem::query()
            ->where('id', $this->line_item_id)
            ->whereHas('purchaseOrder', fn (Builder $builder): Builder => $builder->where('id', $this->purchaseOrderId))
            ->first();

        if ($lineItem === null) {
            return;
        }

        $remaining = max((float) $lineItem->quantity - (float) $lineItem->received_quantity, 0.0);
        $this->quantity = $remaining > 0 ? $remaining : 1;
        $this->unit_cost = (float) $lineItem->unit_cost;
    }

    private function resetReceiptFields(): void
    {
        $this->receipt_type = PurchaseOrderReceiptType::RECEIPT->value;
        $this->quantity = 1;
        $this->unit_cost = 0;
        $this->reference = null;
        $this->notes = null;
        $this->received_at = now()->toDateTimeString();
        $this->applyLineDefaults();
    }

    public function render(): View
    {
        return view('livewire.purchase-orders.receipt-panel');
    }
}
