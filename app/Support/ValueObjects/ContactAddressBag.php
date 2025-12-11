<?php

declare(strict_types=1);

namespace App\Support\ValueObjects;

use App\Data\AddressData;
use App\Enums\AddressType;
use App\Support\Addresses\AddressFormatter;
use Bag\Attributes\Validation\Enum;
use Bag\Attributes\Validation\Min;
use Bag\Attributes\Validation\Required;
use Bag\Attributes\Validation\Str;
use Bag\Bag;
use Illuminate\Validation\Rules\Enum as EnumRule;

final readonly class ContactAddressBag extends Bag
{
    public string $country_code;

    public function __construct(
        #[Enum(AddressType::class)]
        public AddressType $type,
        #[Required, Str, Min(2)]
        public string $line1,
        #[Str]
        public ?string $line2,
        #[Required, Str, Min(2)]
        public string $city,
        #[Str]
        public ?string $state = null,
        #[Str]
        string $country_code = 'US',
        #[Str]
        public ?string $postal_code = null,
    ) {
        $this->country_code = strtoupper($country_code);
    }

    public static function rules(): array
    {
        return [
            'type' => ['nullable', new EnumRule(AddressType::class)],
            'line1' => ['required', 'string', 'min:2'],
            'line2' => ['nullable', 'string'],
            'city' => ['required', 'string', 'min:2'],
            'state' => ['nullable', 'string'],
            'country_code' => ['required', 'string', 'size:2'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function normalizedCountryCode(): string
    {
        return $this->country_code;
    }

    public function toAddressData(): AddressData
    {
        return new AddressData(
            type: $this->type,
            line1: $this->line1,
            line2: $this->line2,
            city: $this->city,
            state: $this->state,
            postal_code: $this->postal_code,
            country_code: $this->country_code,
        );
    }

    public function formatted(?AddressFormatter $formatter = null, bool $multiline = false): string
    {
        return ($formatter ?? new AddressFormatter)->format($this->toAddressData(), $multiline);
    }
}
