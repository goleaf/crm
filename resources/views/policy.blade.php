<x-guest-layout 
    :title="'Privacy Policy - ' . brand_name()"
    :description="brand_name() . ' Privacy Policy - How we collect, use, and protect your personal information.'"
    :ogTitle="'Privacy Policy - ' . brand_name()"
    :ogDescription="'Read the Privacy Policy for ' . brand_name() . '. Learn about how we handle your personal information and protect your privacy.'">
    <x-legal-document
        title="Privacy Policy"
        subtitle="How we collect, use, and protect your personal information."
        :content="$policy"
    />
</x-guest-layout>
