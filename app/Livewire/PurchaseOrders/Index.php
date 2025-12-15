<?php

declare(strict_types=1);

namespace App\Livewire\PurchaseOrders;

use App\Enums\PurchaseOrderStatus;
use App\Livewire\BaseLivewireComponent;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\WithPagination;

final class Index extends BaseLivewireComponent
{
    use WithPagination;

    #[\Livewire\Attributes\Url(except: '')]
    public string $search = '';

    public ?string $status = null;

    public ?int $vendorId = null;

    public bool $showForm = false;

    public bool $showReceipts = false;

    public ?int $editingPurchaseOrderId = null;

    public ?int $receiptsPurchaseOrderId = null;

    private array $queryString = [
        'status' => ['except' => null],
        'vendorId' => ['except' => null],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingVendorId(): void
    {
        $this->resetPage();
    }

    public function updatedVendorId(int|string|null $value): void
    {
        $this->vendorId = $value !== null && $value !== '' ? (int) $value : null;
    }

    public function updatedStatus(?string $value): void
    {
        $this->status = $value !== '' ? $value : null;
    }

    public function openCreate(): void
    {
        $this->editingPurchaseOrderId = null;
        $this->showForm = true;
    }

    public function openEdit(int $purchaseOrderId): void
    {
        $this->editingPurchaseOrderId = $purchaseOrderId;
        $this->showForm = true;
    }

    public function openReceipts(int $purchaseOrderId): void
    {
        $this->receiptsPurchaseOrderId = $purchaseOrderId;
        $this->showReceipts = true;
    }

    #[On('purchase-order-saved')]
    public function handlePurchaseOrderSaved(): void
    {
        $this->showForm = false;
        $this->editingPurchaseOrderId = null;
        $this->resetPage();
    }

    #[On('purchase-order-received')]
    public function handlePurchaseOrderReceived(): void
    {
        $this->showReceipts = false;
        $this->receiptsPurchaseOrderId = null;
        $this->resetPage();
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

    public function render(): View
    {
        $teamId = auth('web')->user()?->currentTeam?->getKey();

        $purchaseOrders = PurchaseOrder::query()
            ->with(['vendor'])
            ->when($teamId !== null, fn (Builder $query): Builder => $query->where('team_id', $teamId))
            ->when($this->search !== '', fn (Builder $query): Builder => $query->where(function (Builder $builder): void {
                $builder
                    ->where('number', 'like', '%' . $this->search . '%')
                    ->orWhereHas('vendor', fn (Builder $vendorQuery): Builder => $vendorQuery->where('name', 'like', '%' . $this->search . '%'));
            }))
            ->when($this->status !== null && $this->status !== '', fn (Builder $query): Builder => $query->where('status', $this->status))
            ->when($this->vendorId !== null, fn (Builder $query): Builder => $query->where('vendor_id', $this->vendorId))
            ->latest('ordered_at')
            ->paginate(10);

        return view('livewire.purchase-orders.index', [
            'purchaseOrders' => $purchaseOrders,
            'statusOptions' => collect(PurchaseOrderStatus::cases())
                ->mapWithKeys(fn (PurchaseOrderStatus $status): array => [$status->value => $status->getLabel()])
                ->all(),
        ]);
    }
}
