<?php

declare(strict_types=1);

namespace App\Services\Validation;

use App\Enums\AccountType;
use App\Enums\CaseStatus;
use App\Enums\Industry;
use App\Enums\LeadStatus;
use App\Models\Account;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Rules\CleanContent;
use App\Rules\ValidEmail;
use App\Rules\ValidPhone;
use App\Rules\ValidUrl;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Centralized validation service for CRM modules.
 * Provides consistent validation rules and error handling.
 */
final class CrmValidationService
{
    /**
     * Validate account data with comprehensive rules.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validateAccountData(array $data, ?Account $account = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', new CleanContent],
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
        ];

        // Add parent validation with cycle detection
        if ($account instanceof \App\Models\Account) {
            $rules['parent_id'] = [
                'nullable',
                'integer',
                'exists:accounts,id',
                function ($attribute, $value, $fail) use ($account): void {
                    if ($value !== null && $account->wouldCreateCycle($value)) {
                        $fail(__('validation.custom.parent_id.no_cycle'));
                    }
                },
            ];
        } else {
            $rules['parent_id'] = ['nullable', 'integer', 'exists:accounts,id'];
        }

        return $this->validateWithCustomMessages($data, $rules, [
            'name.required' => __('validation.custom.account.name_required'),
            'type.required' => __('validation.custom.account.type_required'),
            'annual_revenue.min' => __('validation.custom.account.revenue_positive'),
            'employee_count.min' => __('validation.custom.account.employee_count_positive'),
            'currency.size' => __('validation.custom.account.currency_code'),
        ]);
    }

    /**
     * Validate lead data with comprehensive rules.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validateLeadData(array $data): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', new CleanContent],
            'job_title' => ['nullable', 'string', 'max:255', new CleanContent],
            'company_name' => ['nullable', 'string', 'max:255', new CleanContent],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'email' => ['nullable', new ValidEmail],
            'phone' => ['nullable', new ValidPhone],
            'mobile' => ['nullable', new ValidPhone],
            'website' => ['nullable', new ValidUrl],
            'status' => ['required', Rule::enum(LeadStatus::class)],
            'score' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'assigned_to_id' => ['nullable', 'integer', 'exists:users,id'],
            'territory' => ['nullable', 'string', 'max:255', new CleanContent],
            'duplicate_of_id' => ['nullable', 'integer', 'exists:leads,id'],
            'duplicate_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];

        return $this->validateWithCustomMessages($data, $rules, [
            'name.required' => __('validation.custom.lead.name_required'),
            'email.email' => __('validation.custom.lead.email_invalid'),
            'score.min' => __('validation.custom.lead.score_min'),
            'score.max' => __('validation.custom.lead.score_max'),
            'duplicate_score.min' => __('validation.custom.lead.duplicate_score_min'),
            'duplicate_score.max' => __('validation.custom.lead.duplicate_score_max'),
        ]);
    }

    /**
     * Validate opportunity data with comprehensive rules.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validateOpportunityData(array $data): array
    {
        $rules = [
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
        ];

        return $this->validateWithCustomMessages($data, $rules, [
            'name.required' => __('validation.custom.opportunity.name_required'),
            'probability.min' => __('validation.custom.opportunity.probability_min'),
            'probability.max' => __('validation.custom.opportunity.probability_max'),
            'amount.min' => __('validation.custom.opportunity.amount_positive'),
        ]);
    }

    /**
     * Validate case data with comprehensive rules.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validateCaseData(array $data): array
    {
        $rules = [
            'case_number' => ['nullable', 'string', 'max:50', new CleanContent],
            'subject' => ['required', 'string', 'max:255', new CleanContent],
            'description' => ['nullable', 'string', new CleanContent],
            'status' => ['required', Rule::enum(CaseStatus::class)],
            'priority' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', 'string', 'max:100', new CleanContent],
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'contact_id' => ['nullable', 'integer', 'exists:people,id'],
            'assigned_to_id' => ['nullable', 'integer', 'exists:users,id'],
            'escalation_level' => ['nullable', 'integer', 'min:0', 'max:10'],
            'sla_breach_at' => ['nullable', 'date'],
            'resolution_notes' => ['nullable', 'string', new CleanContent],
            'portal_visible' => ['nullable', 'boolean'],
            'email_thread_id' => ['nullable', 'string', 'max:255'],
        ];

        return $this->validateWithCustomMessages($data, $rules, [
            'subject.required' => __('validation.custom.case.subject_required'),
            'status.required' => __('validation.custom.case.status_required'),
            'escalation_level.min' => __('validation.custom.case.escalation_level_min'),
            'escalation_level.max' => __('validation.custom.case.escalation_level_max'),
        ]);
    }

    /**
     * Validate contact data with comprehensive rules.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validateContactData(array $data): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', new CleanContent],
            'primary_email' => ['nullable', new ValidEmail],
            'alternate_email' => ['nullable', new ValidEmail],
            'phone_mobile' => ['nullable', new ValidPhone],
            'phone_office' => ['nullable', new ValidPhone],
            'phone_home' => ['nullable', new ValidPhone],
            'phone_fax' => ['nullable', new ValidPhone],
            'job_title' => ['nullable', 'string', 'max:255', new CleanContent],
            'department' => ['nullable', 'string', 'max:255', new CleanContent],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'reports_to_id' => ['nullable', 'integer', 'exists:people,id'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'assistant_name' => ['nullable', 'string', 'max:255', new CleanContent],
            'assistant_phone' => ['nullable', new ValidPhone],
            'assistant_email' => ['nullable', new ValidEmail],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'string', new ValidUrl],
            'is_portal_user' => ['nullable', 'boolean'],
            'portal_username' => ['nullable', 'string', 'max:255'],
        ];

        return $this->validateWithCustomMessages($data, $rules, [
            'name.required' => __('validation.custom.contact.name_required'),
            'birthdate.before' => __('validation.custom.contact.birthdate_past'),
        ]);
    }

    /**
     * Validate enum values with proper error messages.
     *
     * @param class-string $enumClass
     */
    public function validateEnum(mixed $value, string $enumClass): bool
    {
        if ($value === null) {
            return true;
        }

        if (! enum_exists($enumClass)) {
            return false;
        }

        if (method_exists($enumClass, 'tryFrom')) {
            return $enumClass::tryFrom($value) !== null;
        }

        $validNames = array_column($enumClass::cases(), 'name');

        return in_array($value, $validNames, true);
    }

    /**
     * Validate with custom messages and return validated data.
     *
     * @param array<string, mixed>  $data
     * @param array<string, mixed>  $rules
     * @param array<string, string> $messages
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function validateWithCustomMessages(array $data, array $rules, array $messages = []): array
    {
        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
