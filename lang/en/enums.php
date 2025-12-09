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
];
