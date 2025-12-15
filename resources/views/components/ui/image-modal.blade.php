@props([
    'title' => 'Preview',
    'src',
    'alt' => '',
])

<x-ui.modal {{ $attributes }} :title="$title" max-width="3xl" persistent>
    <div class="flex flex-col items-center gap-3">
        <div class="overflow-hidden rounded-2xl border border-slate-100 shadow-inner">
            <img
                src="{{ $src }}"
                alt="{{ $alt }}"
                class="max-h-[70vh] w-full object-contain"
                loading="lazy"
            />
        </div>
        <div class="flex w-full items-center justify-between text-sm text-slate-500">
            <span>{{ $alt }}</span>
            <a href="{{ $src }}" download class="inline-flex items-center gap-1 text-primary-600 hover:underline">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0 4-4m-4 4-4-4m12 4v2.5c0 .933-.567 1.5-1.5 1.5h-11c-.933 0-1.5-.567-1.5-1.5V16" />
                </svg>
                Download
            </a>
        </div>
    </div>
</x-ui.modal>
