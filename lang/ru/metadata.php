<?php

declare(strict_types=1);

return [
    'labels' => [
        'metadata' => 'Metadata',
        'custom_metadata' => 'Custom Metadata',
        'key' => 'Key',
        'value' => 'Value',
        'metadata_count' => 'Metadata Count',
        'type' => 'Type',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ],
    'actions' => [
        'add_metadata' => 'Add Metadata',
        'edit_metadata' => 'Edit Metadata',
        'remove_metadata' => 'Remove Metadata',
        'sync_metadata' => 'Sync Metadata',
        'clear_metadata' => 'Clear All Metadata',
    ],
    'notifications' => [
        'metadata_added' => 'Metadata added successfully',
        'metadata_updated' => 'Metadata updated successfully',
        'metadata_removed' => 'Metadata removed successfully',
        'metadata_synced' => 'Metadata synchronized successfully',
        'metadata_cleared' => 'All metadata cleared successfully',
    ],
    'messages' => [
        'no_metadata' => 'No metadata available',
        'confirm_clear' => 'Are you sure you want to clear all metadata? This action cannot be undone.',
        'confirm_remove' => 'Are you sure you want to remove this metadata entry?',
    ],
    'validation' => [
        'key_required' => 'The metadata key is required',
        'key_unique' => 'This metadata key already exists',
        'value_required' => 'The metadata value is required',
    ],
];
