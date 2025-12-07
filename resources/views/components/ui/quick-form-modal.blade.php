@props([
    'title' => 'Quick Create',
    'action' => '#',
    'method' => 'post',
    'submitText' => 'Save',
    'subheading' => null,
])

<x-ui.modal {{ $attributes }} :title="$title" :subtitle="$subheading" max-width="lg">
    <form method="{{ $method }}" action="{{ $action }}" class="space-y-4">
        @csrf
        {{ $slot }}

        <div class="flex justify-end gap-3">
            <button
                type="button"
                class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="$dispatch('close-modal', '{{ $attributes->get('id') }}')"
            >
                Cancel
            </button>
            <button
                type="submit"
                class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
            >
                {{ $submitText }}
            </button>
        </div>
    </form>
</x-ui.modal>
