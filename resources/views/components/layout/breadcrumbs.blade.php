@props([
    'items' => [],
])

@php
    $trail = collect($items)->map(function ($item) {
        return is_array($item) ? $item : ['label' => (string) $item, 'href' => null];
    })->prepend(['label' => 'Home', 'href' => url('/dashboard')]);
@endphp

<nav class="flex items-center text-sm text-gray-500" aria-label="Breadcrumb">
    @foreach($trail as $index => $crumb)
        <div class="flex items-center">
            @if($index > 0)
                <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-300 mx-2"/>
            @endif

            @if(!empty($crumb['href']) && $index !== $trail->count() - 1)
                <a href="{{ $crumb['href'] }}"
                   class="hover:text-primary transition">{{ $crumb['label'] }}</a>
            @else
                <span class="text-gray-900 font-medium">{{ $crumb['label'] }}</span>
            @endif
        </div>
    @endforeach
</nav>
