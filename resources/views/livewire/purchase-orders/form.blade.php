<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ $purchaseOrderId ? 'Edit Purchase Order' : 'Create Purchase Order' }}
            </h3>
            <p class="text-sm text-gray-500">Dynamic, Ajax-powered form with instant totals.</p>
        </div>
        <div class="text-right">
            <div class="text-xs uppercase text-gray-500">Totals</div>
            <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">${{ number_format($this->total, 2) }}</div>
            <div class="text-xs text-gray-500">Subtotal ${{ number_format($this->subtotal, 2) }} • Tax ${{ number_format($this->taxTotal, 2) }}</div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Vendor</label>
            <select wire:model.live="vendor_id"
                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                <option value="">Select a vendor</option>
                @foreach ($this->vendorOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            @error('vendor_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Linked Sales Order</label>
            <select wire:model.live="order_id"
                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                <option value="">No link</option>
                @foreach ($this->orderOptions as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('order_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Ordered At</label>
            <input type="date" wire:model.live="ordered_at"
                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
            @error('ordered_at') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Expected Delivery</label>
            <input type="date" wire:model.live="expected_delivery_date"
                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
            @error('expected_delivery_date') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Currency</label>
            <input type="text" wire:model.live="currency_code" maxlength="3"
                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
            @error('currency_code') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Payment Terms</label>
            <input type="text" wire:model.live="payment_terms" placeholder="Net 30"
                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
            @error('payment_terms') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Shipping Terms</label>
            <input type="text" wire:model.live="shipping_terms" placeholder="Standard"
                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
            @error('shipping_terms') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Ship Method</label>
            <input type="text" wire:model.live="ship_method" placeholder="Ground"
                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
            @error('ship_method') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Ship To</label>
            <textarea wire:model.live="ship_to_address" rows="2"
                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"></textarea>
            @error('ship_to_address') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Bill To</label>
            <textarea wire:model.live="bill_to_address" rows="2"
                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"></textarea>
            @error('bill_to_address') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100">Line Items</h4>
                <p class="text-xs text-gray-500">Dynamic rows update totals in real time.</p>
            </div>
            <button wire:click="addLineItem" type="button"
                class="inline-flex items-center rounded-md border border-transparent bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 shadow-sm hover:bg-indigo-100 dark:bg-indigo-900/40 dark:text-indigo-100">
                + Add line
            </button>
        </div>

        <div class="space-y-3">
            @foreach ($lineItems as $index => $lineItem)
                <div class="rounded-lg border border-gray-200 p-3 shadow-sm dark:border-gray-800" wire:key="line-{{ $index }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="w-full space-y-2">
                            <div class="grid gap-3 md:grid-cols-2">
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Name</label>
                                    <input type="text" wire:model.live="lineItems.{{ $index }}.name" placeholder="Item name"
                                        class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                                    @error('lineItems.'.$index.'.name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Linked Order Line</label>
                                    <input type="number" wire:model.live="lineItems.{{ $index }}.order_line_item_id" placeholder="Order line ID"
                                        class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                                    @error('lineItems.'.$index.'.order_line_item_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Description</label>
                                <textarea wire:model.live="lineItems.{{ $index }}.description" rows="2"
                                    class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"></textarea>
                                @error('lineItems.'.$index.'.description') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="grid gap-3 md:grid-cols-4">
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Quantity</label>
                                    <input type="number" step="0.01" min="0" wire:model.live="lineItems.{{ $index }}.quantity"
                                        class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                                    @error('lineItems.'.$index.'.quantity') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Unit Cost</label>
                                    <input type="number" step="0.01" min="0" wire:model.live="lineItems.{{ $index }}.unit_cost"
                                        class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                                    @error('lineItems.'.$index.'.unit_cost') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Tax %</label>
                                    <input type="number" step="0.01" min="0" wire:model.live="lineItems.{{ $index }}.tax_rate"
                                        class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                                    @error('lineItems.'.$index.'.tax_rate') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Expected Receipt</label>
                                    <input type="date" wire:model.live="lineItems.{{ $index }}.expected_receipt_at"
                                        class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                                    @error('lineItems.'.$index.'.expected_receipt_at') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <button type="button" wire:click="removeLineItem({{ $index }})"
                                class="text-xs text-red-600 hover:text-red-700">Remove</button>
                            <div class="text-right text-sm font-semibold text-gray-900 dark:text-gray-100">
                                ${{ number_format(((float) ($lineItem['quantity'] ?? 0)) * ((float) ($lineItem['unit_cost'] ?? 0)), 2) }}
                            </div>
                        </div>
                    </div>
                @endforeach
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Notes</label>
            <textarea wire:model.live="notes" rows="3"
                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"></textarea>
            @error('notes') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Terms</label>
            <textarea wire:model.live="terms" rows="3"
                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"></textarea>
            @error('terms') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex items-center justify-between">
        <div class="text-sm text-gray-500">
            Changes save instantly without page refresh.
        </div>
        <div class="flex gap-2">
            <button type="button" wire:click="save"
                class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Save Purchase Order
            </button>
        </div>
    </div>
</div>
