<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AccountType;
use App\Enums\Industry;
use App\Rules\CleanContent;
use App\Rules\ValidEmail;
use App\Rules\ValidPhone;
use App\Rules\ValidUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Account::class);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', new CleanContent],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:accounts,id',
                function ($attribute, $value, $fail) {
                    if ($value !== null && $this->wouldCreateCycle($value)) {
                        $fail(__('validation.custom.parent_id.no_cycle'));
                    }
                },
            ],
            'type' => ['required', Rule::enum(AccountType::class)],
            'industry' => ['nullable', Rule::enum(Industry::class)],
            'annual_revenue' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'employee_count' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'currency' => ['nullable', 'string', 'size:3'],
            'website' => ['nullable', new ValidUrl(requireHttps: false, allowLocalhost: false)],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'string', new ValidUrl],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_to_id' => ['nullable', 'integer', 'exists:users,id'],
            
            // Address validation
            'billing_address' => ['nullable', 'array'],
            'billing_address.street' => ['nullable', 'string', 'max:255', new CleanContent],
            'billing_address.city' => ['nullable', 'string', 'max:255', new CleanContent],
            'billing_address.state' => ['nullable', 'string', 'max:255', new CleanContent],
            'billing_address.postal_code' => ['nullable', 'string', 'max:20'],
            'billing_address.country' => ['nullable', 'string', 'size:2'],
            
            'shipping_address' => ['nullable', 'array'],
            'shipping_address.street' => ['nullable', 'string', 'max:255', new CleanContent],
            'shipping_address.city' => ['nullable', 'string', 'max:255', new CleanContent],
            'shipping_address.state' => ['nullable', 'string', 'max:255', new CleanContent],
            'shipping_address.postal_code' => ['nullable', 'string', 'max:20'],
            'shipping_address.country' => ['nullable', 'string', 'size:2'],
            
            // Structured addresses
            'addresses' => ['nullable', 'array'],
            'addresses.*.type' => ['required_with:addresses.*', 'string'],
            'addresses.*.line1' => ['required_with:addresses.*', 'string', 'max:255', new CleanContent],
            'addresses.*.line2' => ['nullable', 'string', 'max:255', new CleanContent],
            'addresses.*.city' => ['nullable', 'string', 'max:255', new CleanContent],
            'addresses.*.state' => ['nullable', 'string', 'max:255', new CleanContent],
            'addresses.*.postal_code' => ['nullable', 'string', 'max:20'],
            'addresses.*.country_code' => ['nullable', 'string', 'size:2'],
            
            'custom_fields' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('validation.custom.account.name_required'),
            'type.required' => __('validation.custom.account.type_required'),
            'annual_revenue.numeric' => __('validation.custom.account.revenue_numeric'),
            'annual_revenue.min' => __('validation.custom.account.revenue_positive'),
            'employee_count.integer' => __('validation.custom.account.employee_count_integer'),
            'employee_count.min' => __('validation.custom.account.employee_count_positive'),
            'currency.size' => __('validation.custom.account.currency_code'),
            'parent_id.exists' => __('validation.custom.account.parent_not_found'),
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('app.labels.name'),
            'parent_id' => __('app.labels.parent_account'),
            'type' => __('app.labels.account_type'),
            'industry' => __('app.labels.industry'),
            'annual_revenue' => __('app.labels.annual_revenue'),
            'employee_count' => __('app.labels.employee_count'),
            'currency' => __('app.labels.currency'),
            'website' => __('app.labels.website'),
            'owner_id' => __('app.labels.owner'),
            'assigned_to_id' => __('app.labels.assigned_to'),
        ];
    }

    private function wouldCreateCycle(?int $parentId): bool
    {
        if ($parentId === null) {
            return false;
        }

        // For new accounts, we can't create a cycle
        // This will be handled by the model's wouldCreateCycle method during updates
        return false;
    }
}