@props([
    'items' => [],
])

@php
    use Illuminate\Support\Facades\Route;

    $defaultItems = [
        ['label' => 'Dashboard', 'href' => url('/dashboard'), 'icon' => 'squares-2x2'],
        ['label' => 'Companies', 'href' => url('/companies'), 'icon' => 'building-office'],
        ['label' => 'People', 'href' => url('/people'), 'icon' => 'user-group'],
        ['label' => 'Opportunities', 'href' => url('/opportunities'), 'icon' => 'sparkles'],
        ['label' => 'Support', 'href' => url('/cases'), 'icon' => 'lifebuoy'],
    ];

    $navItems = count($items) > 0 ? $items : $defaultItems;
@endphp

<aside class="w-64 bg-white border-r border-gray-100 min-h-screen sticky top-0 hidden lg:flex flex-col">
    <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-2">
        <img src="{{ asset('relaticle-logomark.svg') }}" alt="Relaticle logo" class="h-8 w-8">
        <div>
            <p class="text-xs text-gray-500">Relaticle</p>
            <p class="text-sm font-semibold text-gray-900">Workspace</p>
        </div>
    </div>

    <nav class="flex-1 px-3 py-6 space-y-1">
        @foreach($navItems as $item)
            @php
                $isActive = request()->fullUrlIs($item['href']) || request()->is(ltrim(parse_url($item['href'], PHP_URL_PATH) ?? '', '/').'*');
            @endphp
            <a href="{{ $item['href'] }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ $isActive ? 'bg-primary/10 text-primary' : 'text-gray-700 hover:bg-gray-50' }}">
                @switch($item['icon'] ?? null)
                    @case('squares-2x2')
                        <x-heroicon-o-squares-2x2 class="w-5 h-5"/>
                        @break
                    @case('building-office')
                        <x-heroicon-o-building-office class="w-5 h-5"/>
                        @break
                    @case('user-group')
                        <x-heroicon-o-user-group class="w-5 h-5"/>
                        @break
                    @case('sparkles')
                        <x-heroicon-o-sparkles class="w-5 h-5"/>
                        @break
                    @case('lifebuoy')
                        <x-heroicon-o-lifebuoy class="w-5 h-5"/>
                        @break
                    @default
                        <x-heroicon-o-circle-stack class="w-5 h-5"/>
                @endswitch
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="px-4 py-4 border-t border-gray-100">
        <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-600">
            <p class="font-semibold text-gray-900">Need help?</p>
            <p class="text-xs text-gray-500">Visit the docs or open a ticket.</p>
            <div class="mt-3 flex gap-2">
                <a href="{{ Route::has('documentation.index') ? route('documentation.index') : url('/docs') }}" class="text-primary text-xs font-medium">Documentation</a>
                <span class="text-gray-300">•</span>
                <a href="{{ url('/support') }}" class="text-primary text-xs font-medium">Support</a>
            </div>
        </div>
    </div>
</aside>
