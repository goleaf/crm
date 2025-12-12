<?php

declare(strict_types=1);

return [
    'calendar_event_type' => [
        'meeting' => 'Meeting',
        'call' => 'Call',
        'lunch' => 'Lunch',
        'demo' => 'Demo',
        'follow_up' => 'Follow-up',
        'other' => 'Other',
    ],

    'calendar_event_status' => [
        'scheduled' => 'Scheduled',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'lead_type' => [
        'new_business' => 'New Business',
        'existing_business' => 'Existing Business',
    ],

    'quote_status' => [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
    ],

    'quote_discount_type' => [
        'percent' => 'Percent',
        'fixed' => 'Fixed Amount',
    ],

    'address_type' => [
        'billing' => 'Billing',
        'shipping' => 'Shipping',
        'headquarters' => 'Headquarters',
        'mailing' => 'Mailing',
        'office' => 'Office',
        'other' => 'Other',
    ],

    'ocr_document_status' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ],

    'ocr_document_type' => [
        'invoice' => 'Invoice',
        'receipt' => 'Receipt',
        'business_card' => 'Business Card',
        'contract' => 'Contract',
        'shipping_label' => 'Shipping Label',
        'id_card' => 'ID Card',
        'passport' => 'Passport',
        'custom' => 'Custom',
    ],

    'product_status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'discontinued' => 'Discontinued',
        'draft' => 'Draft',
    ],

    'product_lifecycle_stage' => [
        'concept' => 'Concept',
        'development' => 'Development',
        'testing' => 'Testing',
        'released' => 'Released',
        'active' => 'Active',
        'mature' => 'Mature',
        'declining' => 'Declining',
        'discontinued' => 'Discontinued',
        'end_of_life' => 'End of Life',
    ],

    'product_relationship_type' => [
        'bundle' => 'Bundle',
        'cross_sell' => 'Cross-sell',
        'upsell' => 'Upsell',
        'dependency' => 'Dependency',
        'alternative' => 'Alternative',
        'accessory' => 'Accessory',
        'bundle_description' => 'Products sold together as a package',
        'cross_sell_description' => 'Related products that complement this item',
        'upsell_description' => 'Higher-value alternatives to this product',
        'dependency_description' => 'Products required for this item to function',
        'alternative_description' => 'Similar products that can substitute this item',
        'accessory_description' => 'Optional add-ons that enhance this product',
    ],

    'product_attribute_data_type' => [
        'text' => 'Text',
        'number' => 'Number',
        'select' => 'Select',
        'multi_select' => 'Multi-Select',
        'boolean' => 'Boolean',
    ],
];
