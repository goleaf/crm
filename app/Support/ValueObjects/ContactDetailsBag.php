<?php

declare(strict_types=1);

namespace App\Support\ValueObjects;

use App\Support\Helpers\StringHelper;
use App\Support\PersonNameFormatter;
use Bag\Attributes\Validation\Email;
use Bag\Attributes\Validation\Min;
use Bag\Attributes\Validation\Required;
use Bag\Attributes\Validation\Str;
use Bag\Bag;

readonly class ContactDetailsBag extends Bag
{
    public function __construct(
        #[Required, Str, Min(2)]
        public string $first_name,
        #[Required, Str, Min(2)]
        public string $last_name,
        #[Required, Email]
        public string $email,
        #[Str]
        public ?string $phone = null,
        #[Str]
        public ?string $job_title = null,
        public ?ContactAddressBag $address = null,
    ) {}

    public static function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2'],
            'last_name' => ['required', 'string', 'min:2'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:25'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable'],
        ];
    }

    public function fullName(): string
    {
        return PersonNameFormatter::full($this->first_name . ' ' . $this->last_name);
    }

    public function initials(int $length = 2): string
    {
        return StringHelper::initials($this->fullName(), $length);
    }

    public function hasPhone(): bool
    {
        return filled($this->phone);
    }
}
