<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use App\Services\Import\ImportMappingService;
use App\Services\Import\ImportValidationService;

// Test ImportValidationService
echo "Testing ImportValidationService...\n";

$validationService = new ImportValidationService;

// Test validation rules
$testRow = [
    'company_name' => 'Test Company',
    'email_address' => 'test@example.com',
    'phone_number' => '123-456-7890',
];

$mapping = [
    'name' => 'company_name',
    'email' => 'email_address',
    'phone' => 'phone_number',
];

$errors = $validationService->validateRow($testRow, $mapping, 'Company');
echo 'Validation errors for valid row: ' . (empty($errors) ? 'None (Good!)' : implode(', ', $errors)) . "\n";

// Test invalid row
$invalidRow = [
    'company_name' => '', // Missing required field
    'email_address' => 'invalid-email', // Invalid email
    'phone_number' => '123-456-7890',
];

$errors = $validationService->validateRow($invalidRow, $mapping, 'Company');
echo 'Validation errors for invalid row: ' . (empty($errors) ? 'None (Bad!)' : implode(', ', $errors)) . "\n";

// Test ImportMappingService
echo "\nTesting ImportMappingService...\n";

$mappingService = new ImportMappingService;

// Test field suggestions
$headers = ['company_name', 'email_address', 'phone_number', 'website_url'];
$mockImportJob = (object) [
    'model_type' => 'Company',
    'preview_data' => ['headers' => $headers],
];

$suggestions = $mappingService->suggestMapping($mockImportJob);
echo 'Mapping suggestions: ' . json_encode($suggestions, JSON_PRETTY_PRINT) . "\n";

$availableFields = $mappingService->getAvailableFields('Company');
echo 'Available fields for Company: ' . json_encode(array_column($availableFields, 'label'), JSON_PRETTY_PRINT) . "\n";

echo "\nImport engine core functionality test completed successfully!\n";
