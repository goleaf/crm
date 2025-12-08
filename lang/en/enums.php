<?php

declare(strict_types=1);

return [
    // ... existing enum translations ...

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
