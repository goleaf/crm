@php
    $modalId = $attributes->get('id') ?? 'modal-'.\Illuminate\Support\Str::uuid();
    $maxWidth = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
    ][$maxWidth ?? '2xl'] ?? 'max-w-2xl';
@endphp

@props([
    'id' => null,
    'title' => '',
    'icon' => null,
    'maxWidth' => '2xl',
    'show' => false,
    'persistent' => false,
])

<div
    x-data="{ open: @js($show) }"
    x-on:keyup.escape.window="if (!{{ $persistent ? 'true' : 'false' }}) open = false"
    x-on:open-modal.window="if ($event.detail === '{{ $modalId }}') open = true"
    x-on:close-modal.window="if ($event.detail === '{{ $modalId }}') open = false"
    x-cloak
>
    <div
        x-show="open"
        class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm"
        aria-hidden="true"
        x-transition.opacity
        @click="if (!{{ $persistent ? 'true' : 'false' }}) open = false"
    ></div>

    <div
        x-show="open"
        x-trap.inert.noscroll="open"
        x-transition
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $modalId }}-title"
    >
        <div class="w-full {{ $maxWidth }} origin-top rounded-2xl bg-white/90 shadow-2xl ring-1 ring-slate-200 backdrop-blur">
            <div class="flex items-start gap-3 border-b border-slate-100 px-5 py-4">
                @if ($icon)
                    <x-dynamic-component :component=\"$icon\" class=\"h-5 w-5 text-primary-500\" />
                @endif
                <div class=\"flex-1\">
                    <h2 id=\"{{ $modalId }}-title\" class=\"text-base font-semibold text-slate-900\">{{ $title }}</h2>
                    @isset($subtitle)
                        <p class=\"text-sm text-slate-500\">{{ $subtitle }}</p>
                    @endisset
                </div>
                <button
                    type=\"button\"
                    class=\"inline-flex rounded-full p-2 text-slate-500 hover:bg-slate-100\"
                    @click=\"if (!{{ $persistent ? 'true' : 'false' }}) open = false\"
                    aria-label=\"Close dialog\"
                >
                    <svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" class=\"h-5 w-5\">
                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M6 18 18 6M6 6l12 12\" />
                    </svg>
                </button>
            </div>

            <div class=\"px-5 py-4\">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class=\"flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 px-5 py-4\">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
