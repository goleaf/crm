<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\CleanContent;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('opportunity'));
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', new CleanContent],
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'contact_id' => ['nullable', 'integer', 'exists:people,id'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'stage' => ['nullable', 'string', 'max:255', new CleanContent],
            'probability' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'expected_close_date' => ['nullable', 'date'],
            'competitors' => ['nullable', 'array'],
            'competitors.*' => ['string', 'max:255', new CleanContent],
            'next_steps' => ['nullable', 'string', 'max:1000', new CleanContent],
            'win_loss_reason' => ['nullable', 'string', 'max:500', new CleanContent],
            'forecast_category' => ['nullable', 'string', 'max:255', new CleanContent],
            'custom_fields' => ['nullable', 'array'],

            // Collaborators
            'collaborators' => ['nullable', 'array'],
            'collaborators.*' => ['integer', 'exists:users,id'],

            // Closing fields
            'closed_at' => ['nullable', 'date'],
            'closed_by_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('validation.custom.opportunity.name_required'),
            'probability.min' => __('validation.custom.opportunity.probability_min'),
            'probability.max' => __('validation.custom.opportunity.probability_max'),
            'amount.min' => __('validation.custom.opportunity.amount_positive'),
            'account_id.exists' => __('validation.custom.opportunity.account_not_found'),
            'company_id.exists' => __('validation.custom.opportunity.company_not_found'),
            'contact_id.exists' => __('validation.custom.opportunity.contact_not_found'),
            'owner_id.exists' => __('validation.custom.opportunity.owner_not_found'),
            'closed_by_id.exists' => __('validation.custom.opportunity.closed_by_not_found'),
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('app.labels.name'),
            'account_id' => __('app.labels.account'),
            'company_id' => __('app.labels.company'),
            'contact_id' => __('app.labels.contact'),
            'owner_id' => __('app.labels.owner'),
            'stage' => __('app.labels.stage'),
            'probability' => __('app.labels.probability'),
            'amount' => __('app.labels.amount'),
            'expected_close_date' => __('app.labels.expected_close_date'),
            'competitors' => __('app.labels.competitors'),
            'next_steps' => __('app.labels.next_steps'),
            'win_loss_reason' => __('app.labels.win_loss_reason'),
            'forecast_category' => __('app.labels.forecast_category'),
            'closed_at' => __('app.labels.closed_at'),
            'closed_by_id' => __('app.labels.closed_by'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure account_id and company_id are consistent
        if ($this->has('company_id') && ! $this->has('account_id')) {
            $this->merge(['account_id' => $this->input('company_id')]);
        }
    }
}
