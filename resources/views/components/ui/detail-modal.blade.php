@props([
    'title' => 'Details',
    'fields' => [],
])

<x-ui.modal {{ $attributes }} :title="$title" max-width="lg">
    <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
        @foreach ($fields as $label => $value)
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>
</x-ui.modal>
