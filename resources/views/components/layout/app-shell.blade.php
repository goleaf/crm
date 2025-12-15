@props([
    'title' => brand_name(),
    'description' => '',
    'breadcrumbs' => [],
    'nav' => [],
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900">
<div class="min-h-screen flex">
    <x-layout.sidebar :items="$nav"/>

    <div class="flex-1 flex flex-col min-h-screen">
        <x-layout.app-header :title="$title"/>

        <main class="flex-1 w-full">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <x-layout.breadcrumbs :items="$breadcrumbs"/>

                <div class="bg-white shadow-sm rounded-xl border border-gray-100 mt-4">
                    <div class="p-6">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </main>

        <x-layout.footer/>
    </div>
</div>

@stack('modals')
@livewireScripts
</body>
</html>
