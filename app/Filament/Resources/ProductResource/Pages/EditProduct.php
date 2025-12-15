<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

final class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load custom field values into the form
        $tenant = Filament::getTenant();
        if ($tenant && $this->record) {
            $customFieldValues = $this->record->customFieldValues()
                ->with('customField')
                ->get();

            foreach ($customFieldValues as $customFieldValue) {
                $data["custom_field_{$customFieldValue->custom_field_id}"] = $customFieldValue->value;
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

    protected function afterSave(): void
    {
        // Save custom fields after the product is saved
        if (isset($this->customFieldsData)) {
            $tenant = Filament::getTenant();
            if ($tenant) {
                foreach ($this->customFieldsData as $fieldId => $value) {
                    $customField = \Relaticle\CustomFields\Models\CustomField::find($fieldId);
                    if ($customField) {
                        if ($value !== null && $value !== '') {
                            $this->record->saveCustomFieldValue($customField, $value);
                        } else {
                            // Remove the custom field value if it's empty
                            $this->record->customFieldValues()
                                ->where('custom_field_id', $fieldId)
                                ->delete();
                        }
                    }
                }
            }
        }
    }

    private array $customFieldsData = [];
}
