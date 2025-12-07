@props([
    'title' => 'Are you sure?',
    'message' => 'This action cannot be undone.',
    'confirmText' => 'Confirm',
    'cancelText' => 'Cancel',
    'confirmMethod' => null,
    'method' => 'post',
    'action' => '#',
    'wireClick' => null,
])

<x-ui.modal {{ $attributes }} :title="$title" max-width="sm">
    <p class="text-sm text-slate-600">{{ $message }}</p>

    <x-slot name="footer">
        <button
            type="button"
            class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            @click="$dispatch('close-modal', '{{ $attributes->get('id') }}')"
        >
            {{ $cancelText }}
        </button>

        <form
            @submit.prevent="{{ $wireClick ? "$wire.$wireClick" : '' }}"
            action="{{ $wireClick ? '#' : $action }}"
            method="{{ $method }}"
            class="inline-flex"
        >
            @csrf
            <button
                type="submit"
                @click="{{ $wireClick ? '' : '$dispatch(\"close-modal\", \"'.$attributes->get('id').'\")' }}"
                class="inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-500"
            >
                {{ $confirmText }}
            </button>
        </form>
    </x-slot>
</x-ui.modal>
