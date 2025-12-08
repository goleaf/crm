<x-guest-layout 
    :title="brand_name() . ' - ' . __('The Next-Generation Open-Source CRM Platform')"
    :description="brand_name() . ' is an open-source CRM platform designed for modern businesses. Manage your customers, leads, and opportunities with ease.'"
    :ogTitle="brand_name() . ' - Open-Source CRM Platform'"
    :ogDescription="'Discover ' . brand_name() . ', the next-generation open-source CRM platform. Powerful, flexible, and built for modern businesses.'"
    :ogImage="url('/images/og-image.jpg')">
    @include('home.partials.hero')
    @include('home.partials.kanban')
    @include('home.partials.features')
    @include('home.partials.calendar')
    @include('home.partials.community')
    @include('home.partials.start-building')
</x-guest-layout>
