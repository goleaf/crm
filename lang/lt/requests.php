<?php

declare(strict_types=1);

return [
    'lead' => [
        'name_required' => 'Lead name is required.',
        'email_email' => 'Please provide a valid email address.',
        'website_url' => 'Please provide a valid website URL.',
        'phone_regex' => 'Phone numbers may only contain digits, spaces, parentheses, dashes, plus signs, or dots.',
        'score_min' => 'Score must be at least 0.',
        'score_max' => 'Score cannot exceed 1000.',
        'duplicate_score_min' => 'Duplicate confidence must be at least 0.',
        'duplicate_score_max' => 'Duplicate confidence cannot exceed 100.',
    ],
    'contact' => [
        'name_required' => 'Contact name is required.',
        'email_email' => 'Please provide a valid email address.',
        'phone_regex' => 'Phone numbers may only contain digits, spaces, parentheses, dashes, plus signs, or dots.',
    ],
];
