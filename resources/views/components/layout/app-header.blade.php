@php
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();
    $initials = $user && filled($user->name ?? null)
        ? strtoupper(mb_substr((string) $user->name, 0, 1))
        : 'U';
@endphp

<header class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-wide text-gray-400">Workspace</p>
            <h1 class="text-lg font-semibold text-gray-900">{{ $title ?? config('app.name', 'Relaticle') }}</h1>
        </div>

        <div class="flex items-center gap-4">
            <button type="button"
                    class="hidden sm:inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                <x-heroicon-o-bell class="w-5 h-5"/>
                <span>Notifications</span>
            </button>

            <div class="relative" x-data="{ open: false }">
                <button type="button"
                        class="flex items-center gap-3 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition"
                        x-on:click="open = !open" aria-haspopup="true" aria-expanded="false">
                    <div class="flex items-center justify-center w-9 h-9 rounded-full bg-primary/10 text-primary font-semibold">
                        {{ $initials }}
                    </div>
                    <div class="hidden sm:block text-left">
                        <p class="text-sm font-semibold text-gray-900">{{ $user?->name ?? 'Guest' }}</p>
                        <p class="text-xs text-gray-500">{{ $user?->email ?? 'guest@relaticle.com' }}</p>
                    </div>
                    <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-500"/>
                </button>

                <div x-cloak x-show="open" x-transition
                     x-on:click.away="open = false"
                     class="absolute right-0 mt-2 w-56 bg-white border border-gray-100 rounded-xl shadow-lg py-2 z-50">
                    <a href="{{ Route::has('profile.show') ? route('profile.show', [], false) : url('/profile') }}"
                       class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        <x-heroicon-o-user class="w-4 h-4"/> Profile
                    </a>
                    <a href="{{ url('/settings') }}"
                       class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        <x-heroicon-o-cog-6-tooth class="w-4 h-4"/> Settings
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <x-heroicon-o-arrow-right-end-on-rectangle class="w-4 h-4"/> Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
