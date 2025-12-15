@php
    $sessionMessages = collect([
        ['type' => 'success', 'message' => session('success')],
        ['type' => 'error', 'message' => session('error')],
        ['type' => 'warning', 'message' => session('warning')],
        ['type' => 'info', 'message' => session('info') ?: session('status')],
    ])->filter(fn ($item) => filled($item['message']));

    $flashBag = collect(session('flash', []))
        ->map(fn ($message, $type) => ['type' => $type, 'message' => $message]);

    $toasts = $sessionMessages->merge($flashBag)->values();
@endphp

@if ($toasts->isNotEmpty())
    <div class="fixed inset-0 z-50 flex items-start justify-end px-4 py-6 sm:py-8 pointer-events-none">
        <div class="w-full max-w-sm space-y-3">
            @foreach ($toasts as $toast)
                <div
                    x-data="{ open: true }"
                    x-show="open"
                    x-init="setTimeout(() => open = false, 4200)"
                    x-transition
                    class="pointer-events-auto"
                >
                    <x-alert :type="$toast['type']" :message="$toast['message']" />
                </div>
            @endforeach
        </div>
    </div>
@endif
