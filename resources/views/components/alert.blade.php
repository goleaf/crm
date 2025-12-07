@props([
    'type' => 'info', // success, error, warning, info
    'title' => null,
    'message' => null,
    'closable' => true,
])

@php
    $variants = [
        'success' => ['bg' => 'bg-emerald-50 dark:bg-emerald-900/40', 'border' => 'border-emerald-200 dark:border-emerald-800', 'text' => 'text-emerald-900 dark:text-emerald-100'],
        'error' => ['bg' => 'bg-rose-50 dark:bg-rose-900/40', 'border' => 'border-rose-200 dark:border-rose-800', 'text' => 'text-rose-900 dark:text-rose-100'],
        'warning' => ['bg' => 'bg-amber-50 dark:bg-amber-900/40', 'border' => 'border-amber-200 dark:border-amber-800', 'text' => 'text-amber-900 dark:text-amber-100'],
        'info' => ['bg' => 'bg-sky-50 dark:bg-sky-900/40', 'border' => 'border-sky-200 dark:border-sky-800', 'text' => 'text-sky-900 dark:text-sky-100'],
    ];

    $variant = $variants[$type] ?? $variants['info'];
@endphp

<div
    x-data="{ open: true }"
    x-show="open"
    x-transition
    class="rounded-lg border {{ $variant['border'] }} {{ $variant['bg'] }} px-4 py-3 shadow-sm"
>
    <div class="flex items-start gap-3">
        <div class="pt-0.5 text-sm font-semibold {{ $variant['text'] }}">
            {{ $title ?? ucfirst($type) }}
        </div>

        <div class="flex-1 text-sm {{ $variant['text'] }}">
            {{ $message ?? $slot }}
        </div>

        @if ($closable)
            <button
                type="button"
                class="text-sm font-medium {{ $variant['text'] }}"
                x-on:click="open = false"
            >
                ×
            </button>
        @endif
    </div>
</div>
