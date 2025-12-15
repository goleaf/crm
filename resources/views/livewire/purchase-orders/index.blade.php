<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Purchase Orders</h2>
            <p class="text-sm text-gray-500">Interactive, no-refresh management with Livewire.</p>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="openCreate"
                class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                New Purchase Order
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 rounded-lg bg-white p-4 shadow dark:bg-gray-900">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="PO number or vendor"
                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" />
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Status</label>
                <select wire:model.live="status"
                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                    <option value="">All</option>
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Vendor</label>
                <select wire:model.live="vendorId"
                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                    <option value="">All vendors</option>
                    @foreach ($this->vendorOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">PO</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Vendor</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Totals</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Expected</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-900">
                @forelse ($purchaseOrders as $purchaseOrder)
                    <tr wire:key="po-row-{{ $purchaseOrder->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                            <div class="font-semibold">{{ $purchaseOrder->number }}</div>
                            <div class="text-xs text-gray-500">Ordered {{ optional($purchaseOrder->ordered_at)->format('Y-m-d') }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                            {{ $purchaseOrder->vendor?->name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                            <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-100">
                                {{ $purchaseOrder->status->getLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                            <div class="font-semibold">${{ number_format((float) $purchaseOrder->total, 2) }}</div>
                            <div class="text-xs text-gray-500">Outstanding ${{ number_format((float) $purchaseOrder->outstanding_commitment, 2) }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                            {{ optional($purchaseOrder->expected_delivery_date)->format('Y-m-d') ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-900 dark:text-gray-100 space-x-2">
                            <button wire:click="openEdit({{ $purchaseOrder->id }})"
                                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                Edit
                            </button>
                            <button wire:click="openReceipts({{ $purchaseOrder->id }})"
                                class="inline-flex items-center rounded-md border border-transparent bg-emerald-600 px-3 py-1 text-xs font-medium text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                Receipts
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            No purchase orders found. Start by creating one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
            {{ $purchaseOrders->links() }}
        </div>
    </div>

    @if ($showForm)
        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-900">
            @livewire('purchase-orders.form', ['purchaseOrderId' => $editingPurchaseOrderId], key('po-form-'.($editingPurchaseOrderId ?? 'new')))
        </div>
    @endif

    @if ($showReceipts && $receiptsPurchaseOrderId)
        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-900">
            @livewire('purchase-orders.receipt-panel', ['purchaseOrderId' => $receiptsPurchaseOrderId], key('po-receipts-'.$receiptsPurchaseOrderId))
        </div>
    @endif
</div>
