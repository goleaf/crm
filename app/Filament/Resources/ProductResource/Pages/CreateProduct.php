<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

final class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract custom fields from the form data
        $customFields = [];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'custom_field_')) {
                $fieldId = str_replace('custom_field_', '', $key);
                $customFields[$fieldId] = $value;
                unset($data[$key]);
            }
        }

        // Store custom fields for later processing
        $this->customFieldsData = $customFields;

        return $data;
    }

    protected function afterCreate(): void
    {
        // Save custom fields after the product is created
        if (isset($this->customFieldsData) && $this->customFieldsData !== []) {
            $tenant = Filament::getTenant();
            if ($tenant) {
                foreach ($this->customFieldsData as $fieldId => $value) {
                    $customField = \Relaticle\CustomFields\Models\CustomField::find($fieldId);
                    if ($customField && $value !== null && $value !== '') {
                        $this->record->saveCustomFieldValue($customField, $value);
                    }
                }
            }
        }
    }

    private array $customFieldsData = [];
}
