<?php

declare(strict_types=1);

namespace App\Enums\CustomFields;

use App\Enums\CustomFieldType;

enum ProductField: string
{
    use CustomFieldTrait;

    /**
     * Brand: The brand or manufacturer of the product
     */
    case BRAND = 'brand';

    /**
     * Model Number: The specific model number or identifier
     */
    case MODEL_NUMBER = 'model_number';

    /**
     * Weight: The weight of the product
     */
    case WEIGHT = 'weight';

    /**
     * Dimensions: The physical dimensions of the product
     */
    case DIMENSIONS = 'dimensions';

    /**
     * Material: The primary material the product is made from
     */
    case MATERIAL = 'material';

    /**
     * Color: The primary color of the product
     */
    case COLOR = 'color';

    /**
     * Warranty Period: The warranty period for the product
     */
    case WARRANTY_PERIOD = 'warranty_period';

    /**
     * Country of Origin: Where the product was manufactured
     */
    case COUNTRY_OF_ORIGIN = 'country_of_origin';

    /**
     * Certification: Product certifications (CE, FCC, etc.)
     */
    case CERTIFICATION = 'certification';

    /**
     * Eco Friendly: Whether the product is environmentally friendly
     */
    case ECO_FRIENDLY = 'eco_friendly';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::BRAND => __('enums.product_field.brand'),
            self::MODEL_NUMBER => __('enums.product_field.model_number'),
            self::WEIGHT => __('enums.product_field.weight'),
            self::DIMENSIONS => __('enums.product_field.dimensions'),
            self::MATERIAL => __('enums.product_field.material'),
            self::COLOR => __('enums.product_field.color'),
            self::WARRANTY_PERIOD => __('enums.product_field.warranty_period'),
            self::COUNTRY_OF_ORIGIN => __('enums.product_field.country_of_origin'),
            self::CERTIFICATION => __('enums.product_field.certification'),
            self::ECO_FRIENDLY => __('enums.product_field.eco_friendly'),
        };
    }

    public function getFieldType(): string
    {
        return match ($this) {
            self::BRAND, self::MODEL_NUMBER, self::DIMENSIONS, self::MATERIAL, self::WARRANTY_PERIOD, self::CERTIFICATION => CustomFieldType::TEXT->value,
            self::WEIGHT => CustomFieldType::NUMBER->value,
            self::COLOR => CustomFieldType::SELECT->value,
            self::COUNTRY_OF_ORIGIN => CustomFieldType::SELECT->value,
            self::ECO_FRIENDLY => CustomFieldType::TOGGLE->value,
        };
    }

    public function isSystemDefined(): bool
    {
        return match ($this) {
            self::BRAND, self::MODEL_NUMBER, self::WEIGHT, self::DIMENSIONS, self::MATERIAL => true,
            default => false,
        };
    }

    public function isListToggleableHidden(): bool
    {
        return match ($this) {
            self::BRAND, self::MODEL_NUMBER, self::WEIGHT => false,
            default => true,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::BRAND => __('enums.product_field.brand_description'),
            self::MODEL_NUMBER => __('enums.product_field.model_number_description'),
            self::WEIGHT => __('enums.product_field.weight_description'),
            self::DIMENSIONS => __('enums.product_field.dimensions_description'),
            self::MATERIAL => __('enums.product_field.material_description'),
            self::COLOR => __('enums.product_field.color_description'),
            self::WARRANTY_PERIOD => __('enums.product_field.warranty_period_description'),
            self::COUNTRY_OF_ORIGIN => __('enums.product_field.country_of_origin_description'),
            self::CERTIFICATION => __('enums.product_field.certification_description'),
            self::ECO_FRIENDLY => __('enums.product_field.eco_friendly_description'),
        };
    }

    public function getOptions(): ?array
    {
        return match ($this) {
            self::COLOR => [
                'red' => __('enums.product_field.colors.red'),
                'blue' => __('enums.product_field.colors.blue'),
                'green' => __('enums.product_field.colors.green'),
                'yellow' => __('enums.product_field.colors.yellow'),
                'black' => __('enums.product_field.colors.black'),
                'white' => __('enums.product_field.colors.white'),
                'gray' => __('enums.product_field.colors.gray'),
                'brown' => __('enums.product_field.colors.brown'),
                'orange' => __('enums.product_field.colors.orange'),
                'purple' => __('enums.product_field.colors.purple'),
            ],
            self::COUNTRY_OF_ORIGIN => [
                'US' => __('enums.product_field.countries.us'),
                'CN' => __('enums.product_field.countries.cn'),
                'DE' => __('enums.product_field.countries.de'),
                'JP' => __('enums.product_field.countries.jp'),
                'KR' => __('enums.product_field.countries.kr'),
                'TW' => __('enums.product_field.countries.tw'),
                'IT' => __('enums.product_field.countries.it'),
                'FR' => __('enums.product_field.countries.fr'),
                'GB' => __('enums.product_field.countries.gb'),
                'CA' => __('enums.product_field.countries.ca'),
            ],
            default => null,
        };
    }

    public function getOptionColors(): ?array
    {
        return match ($this) {
            self::COLOR => [
                'red' => 'danger',
                'blue' => 'info',
                'green' => 'success',
                'yellow' => 'warning',
                'black' => 'gray',
                'white' => 'gray',
                'gray' => 'gray',
                'brown' => 'warning',
                'orange' => 'warning',
                'purple' => 'primary',
            ],
            default => null,
        };
    }
}
