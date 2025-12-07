<?php

declare(strict_types=1);

use App\Enums\CustomFields\PeopleField;
use App\Enums\CustomFieldType;
use App\Models\People;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldValue;

return new class extends Migration
{
    public function up(): void
    {
        $fieldIds = $this->phoneNumberFields()->pluck('id');

        if ($fieldIds->isEmpty()) {
            return;
        }

        CustomField::query()
            ->whereKey($fieldIds)
            ->update([
                'name' => PeopleField::PHONE_NUMBER->getDisplayName(),
                'type' => CustomFieldType::TAGS_INPUT->value,
            ]);

        CustomFieldValue::query()
            ->whereIn('custom_field_id', $fieldIds)
            ->chunkById(100, function (Collection $values): void {
                /** @var CustomFieldValue $value */
                foreach ($values as $value) {
                    $numbers = $this->normalizeNumbers($value);
                    $value->json_value = $numbers ?: null;
                    $value->string_value = null;
                    $value->text_value = null;
                    $value->save();
                }
            });
    }

    public function down(): void
    {
        $fieldIds = $this->phoneNumberFields()->pluck('id');

        if ($fieldIds->isEmpty()) {
            return;
        }

        CustomField::query()
            ->whereKey($fieldIds)
            ->update([
                'name' => 'Phone Number',
                'type' => CustomFieldType::TEXT->value,
            ]);

        CustomFieldValue::query()
            ->whereIn('custom_field_id', $fieldIds)
            ->chunkById(100, function (Collection $values): void {
                /** @var CustomFieldValue $value */
                foreach ($values as $value) {
                    $numbers = $value->json_value instanceof Collection
                        ? $value->json_value->toArray()
                        : (array) $value->json_value;

                    $flat = implode(', ', array_filter(array_map(trim(...), $numbers)));

                    $value->text_value = $flat !== '' ? $flat : null;
                    $value->json_value = null;
                    $value->save();
                }
            });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, CustomField>
     */
    private function phoneNumberFields()
    {
        return CustomField::query()
            ->where('entity_type', People::class)
            ->where('code', PeopleField::PHONE_NUMBER->value)
            ->get();
    }

    /**
     * @return array<int, string>
     */
    private function normalizeNumbers(CustomFieldValue $value): array
    {
        if ($value->json_value instanceof Collection) {
            $existing = $value->json_value->toArray();
        } elseif (is_array($value->json_value)) {
            $existing = $value->json_value;
        } else {
            $raw = $value->text_value ?? $value->string_value;

            if (is_string($raw)) {
                $decoded = json_decode($raw, true);

                $existing = is_array($decoded) ? $decoded : preg_split('/[,\\n]+/', $raw);
            } elseif ($raw !== null) {
                $existing = [(string) $raw];
            } else {
                $existing = [];
            }
        }

        return array_values(array_unique(array_filter(array_map(trim(...), $existing))));
    }
};
