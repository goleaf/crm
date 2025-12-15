<x-guest-layout 
    :title="'Terms of Service - ' . brand_name()"
    :description="brand_name() . ' Terms of Service - Use of the ' . brand_name() . ' website and all related services is subject to the following terms of service.'"
    :ogTitle="'Terms of Service - ' . brand_name()"
    :ogDescription="'Read the Terms of Service for ' . brand_name() . '. Learn about the rules and guidelines for using our open-source CRM platform.'">
    <x-legal-document
        title="Terms of Service"
        :subtitle="'Use of the ' . brand_name() . ' website and all related services is subject to the following terms of service.'"
        :content="$terms"
    />
</x-guest-layout>
