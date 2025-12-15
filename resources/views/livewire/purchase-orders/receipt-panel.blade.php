<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Receipt Tracking</h3>
            <p class="text-sm text-gray-500">Log receipts and returns in real time.</p>
        </div>
        @if ($outstanding !== null)
            <div class="text-right">
                <div class="text-xs uppercase text-gray-500">Outstanding</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">${{ number_format($outstanding, 2) }}</div>
            </div>
        @endif
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Line Item</label>
            <select wire:model.live="line_item_id"
                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                @foreach ($lineOptions as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('line_item_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Receipt Type</label>
            <select wire:model.live="receipt_type"
                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                <option value="receipt">Receipt</option>
                <option value="return">Return</option>
            </select>
            @error('receipt_type') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Quantity</label>
            <input type="number" step="0.01" min="0" wire:model.live="quantity"
                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
            @error('quantity') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Unit Cost</label>
            <input type="number" step="0.01" min="0" wire:model.live="unit_cost"
                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
            @error('unit_cost') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Received At</label>
            <input type="datetime-local" wire:model.live="received_at"
                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
            @error('received_at') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Reference</label>
            <input type="text" wire:model.live="reference" placeholder="Packing slip, BOL #"
                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
            @error('reference') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="space-y-2">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Notes</label>
        <textarea wire:model.live="notes" rows="3"
            class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"></textarea>
        @error('notes') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center justify-between">
        <div class="text-sm text-gray-500">Ajax-powered; saves without page refresh.</div>
        <button type="button" wire:click="saveReceipt"
            class="inline-flex items-center rounded-md border border-transparent bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
            Save Receipt
        </button>
    </div>
</div>
