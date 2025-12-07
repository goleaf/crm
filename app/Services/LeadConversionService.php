<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CustomFields\OpportunityField;
use App\Enums\LeadStatus;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;
use Relaticle\Flowforge\Services\Rank;

final class LeadConversionService
{
    /**
     * Convert a lead into downstream records inside a transaction.
     *
     * @param  array<string, mixed>  $payload
     */
    public function convert(Lead $lead, array $payload): LeadConversionResult
    {
        // Prevent double conversion
        if ($lead->isConverted()) {
            throw new \RuntimeException("Lead {$lead->id} has already been converted");
        }

        /** @var User|null $user */
        $user = Auth::guard('web')->user();

        return DB::transaction(function () use ($lead, $payload, $user): LeadConversionResult {
            $company = $this->resolveCompany($lead, $payload);
            $contact = $this->maybeCreateContact($lead, $company, $payload);
            $opportunity = $this->maybeCreateOpportunity($lead, $company, $contact, $payload);

            $lead->forceFill([
                'status' => LeadStatus::CONVERTED,
                'converted_at' => now(),
                'converted_by_id' => $user?->getKey(),
                'converted_company_id' => $company?->getKey(),
                'converted_contact_id' => $contact?->getKey(),
                'converted_opportunity_id' => $opportunity?->getKey(),
            ])->save();

            $this->logConversion($lead, $company, $contact, $opportunity, $user);

            return new LeadConversionResult(
                company: $company,
                contact: $contact,
                opportunity: $opportunity
            );
        });
    }

    /**
     * Log conversion for audit trail
     */
    private function logConversion(
        Lead $lead,
        ?Company $company,
        ?People $contact,
        ?Opportunity $opportunity,
        ?User $user
    ): void {
        Log::info('Lead converted', [
            'lead_id' => $lead->id,
            'lead_name' => $lead->name,
            'company_id' => $company?->id,
            'company_name' => $company?->name,
            'contact_id' => $contact?->id,
            'contact_name' => $contact?->name,
            'opportunity_id' => $opportunity?->id,
            'opportunity_name' => $opportunity?->name,
            'converted_by' => $user?->name,
            'converted_by_id' => $user?->id,
        ]);
    }

    /**
     * @return array<int|string, string>
     */
    public function stageOptions(): array
    {
        return $this->stageField()?->options?->pluck('name', 'id')->all() ?? [];
    }

    private function resolveCompany(Lead $lead, array $payload): ?Company
    {
        $companyId = $payload['company_id'] ?? null;
        $companyName = $payload['new_company_name'] ?? $lead->company_name ?? $lead->name;

        if ($companyId !== null) {
            return Company::query()->find($companyId);
        }

        if (blank($companyName)) {
            return null;
        }

        /** @var Company $company */
        $company = Company::query()->create([
            'name' => $companyName,
            'team_id' => $lead->team_id,
        ]);

        return $company;
    }

    private function maybeCreateContact(Lead $lead, ?Company $company, array $payload): ?People
    {
        if (! ($payload['create_contact'] ?? false)) {
            return null;
        }

        $contactName = $payload['contact_name'] ?? $lead->name;
        if (blank($contactName)) {
            return null;
        }

        /** @var People $contact */
        $contact = People::query()->create([
            'name' => $contactName,
            'company_id' => $company?->getKey(),
            'primary_email' => $payload['contact_email'] ?? $lead->email,
            'phone_mobile' => $payload['contact_phone'] ?? $lead->mobile ?? $lead->phone,
            'job_title' => $lead->job_title,
            'team_id' => $lead->team_id,
        ]);

        return $contact;
    }

    private function maybeCreateOpportunity(
        Lead $lead,
        ?Company $company,
        ?People $contact,
        array $payload,
    ): ?Opportunity {
        if (! ($payload['create_opportunity'] ?? true)) {
            return null;
        }

        $opportunityName = $payload['opportunity_name'] ?? $lead->name;
        if (blank($opportunityName)) {
            return null;
        }

        /** @var Opportunity $opportunity */
        $opportunity = Opportunity::query()->create([
            'name' => $opportunityName,
            'company_id' => $company?->getKey(),
            'contact_id' => $contact?->getKey(),
            'team_id' => $lead->team_id,
            'order_column' => Rank::forEmptySequence()->get(),
        ]);

        $stageValue = $payload['stage_option_id'] ?? null;
        $amountValue = $payload['amount'] ?? null;
        $probabilityValue = $payload['probability'] ?? null;
        $closeDateValue = $payload['close_date'] ?? null;

        if ($stageValue !== null) {
            $this->saveCustomFieldValue($opportunity, OpportunityField::STAGE, $stageValue);
        }

        if ($amountValue !== null) {
            $this->saveCustomFieldValue($opportunity, OpportunityField::AMOUNT, $amountValue);
        }

        if ($probabilityValue !== null) {
            $this->saveCustomFieldValue($opportunity, OpportunityField::PROBABILITY, $probabilityValue);
        }

        if ($closeDateValue !== null) {
            $this->saveCustomFieldValue($opportunity, OpportunityField::CLOSE_DATE, $closeDateValue);
        }

        return $opportunity;
    }

    private function saveCustomFieldValue(Opportunity $opportunity, OpportunityField $field, mixed $value): void
    {
        $customField = $this->field($field);
        if (! $customField instanceof CustomField) {
            return;
        }

        // For select options we expect the option identifier
        if ($customField->type === 'select' && $value !== null) {
            $option = $this->findOption($customField, $value);
            $value = $option?->getKey();
        }

        $opportunity->saveCustomFieldValue($customField, $value);
    }

    private function findOption(CustomField $field, int|string $value): ?CustomFieldOption
    {
        return $field->options()
            ->whereKey($value)
            ->orWhere('name', $value)
            ->first();
    }

    private function field(OpportunityField $field): ?CustomField
    {
        /** @var CustomField|null */
        return CustomField::query()
            ->forEntity(Opportunity::class)
            ->where('code', $field->value)
            ->first();
    }

    private function stageField(): ?CustomField
    {
        return $this->field(OpportunityField::STAGE);
    }
}

final readonly class LeadConversionResult
{
    public function __construct(
        public ?Company $company,
        public ?People $contact,
        public ?Opportunity $opportunity,
    ) {}
}
