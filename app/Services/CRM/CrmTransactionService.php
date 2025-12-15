<?php

declare(strict_types=1);

namespace App\Services\CRM;

use App\Models\Account;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\SupportCase;
use App\Services\Validation\CrmValidationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Service for handling CRM operations with transactional safety.
 * Ensures data integrity across complex operations.
 */
final readonly class CrmTransactionService
{
    public function __construct(
        private CrmValidationService $validator,
    ) {}

    /**
     * Create or update an account with full validation and transaction safety.
     *
     * @param array<string, mixed> $data
     *
     * @throws ValidationException
     */
    public function saveAccount(array $data, ?Account $account = null): Account
    {
        return DB::transaction(function () use ($data, $account): Account {
            try {
                // Validate data
                $validated = $this->validator->validateAccountData($data, $account);

                // Create or update
                if (! $account instanceof \App\Models\Account) {
                    $account = Account::create($validated);

                    Log::info('Account created', [
                        'account_id' => $account->id,
                        'name' => $account->name,
                        'user_id' => auth()->id(),
                    ]);
                } else {
                    $account->update($validated);

                    Log::info('Account updated', [
                        'account_id' => $account->id,
                        'name' => $account->name,
                        'user_id' => auth()->id(),
                    ]);
                }

                return $account->fresh();

            } catch (\Exception $e) {
                Log::error('Account save failed', [
                    'data' => $data,
                    'account_id' => $account?->id,
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Create or update an opportunity with validation and weighted amount calculation.
     *
     * @param array<string, mixed> $data
     *
     * @throws ValidationException
     */
    public function saveOpportunity(array $data, ?Opportunity $opportunity = null): Opportunity
    {
        return DB::transaction(function () use ($data, $opportunity): Opportunity {
            try {
                // Validate data
                $validated = $this->validator->validateOpportunityData($data);

                // Create or update
                if (! $opportunity instanceof \App\Models\Opportunity) {
                    $opportunity = Opportunity::create($validated);

                    Log::info('Opportunity created', [
                        'opportunity_id' => $opportunity->id,
                        'name' => $opportunity->name,
                        'amount' => $opportunity->amount,
                        'user_id' => auth()->id(),
                    ]);
                } else {
                    $opportunity->update($validated);

                    Log::info('Opportunity updated', [
                        'opportunity_id' => $opportunity->id,
                        'name' => $opportunity->name,
                        'amount' => $opportunity->amount,
                        'user_id' => auth()->id(),
                    ]);
                }

                // Weighted amount is calculated automatically in the model
                return $opportunity->fresh();

            } catch (\Exception $e) {
                Log::error('Opportunity save failed', [
                    'data' => $data,
                    'opportunity_id' => $opportunity?->id,
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Create or update a case with SLA validation.
     *
     * @param array<string, mixed> $data
     *
     * @throws ValidationException
     */
    public function saveCase(array $data, ?SupportCase $case = null): SupportCase
    {
        return DB::transaction(function () use ($data, $case): SupportCase {
            try {
                // Validate data
                $validated = $this->validator->validateCaseData($data);

                // Create or update
                if (! $case instanceof \App\Models\SupportCase) {
                    $case = SupportCase::create($validated);

                    Log::info('Case created', [
                        'case_id' => $case->id,
                        'case_number' => $case->case_number,
                        'subject' => $case->subject,
                        'user_id' => auth()->id(),
                    ]);
                } else {
                    $case->update($validated);

                    Log::info('Case updated', [
                        'case_id' => $case->id,
                        'case_number' => $case->case_number,
                        'subject' => $case->subject,
                        'user_id' => auth()->id(),
                    ]);
                }

                return $case->fresh();

            } catch (\Exception $e) {
                Log::error('Case save failed', [
                    'data' => $data,
                    'case_id' => $case?->id,
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Create or update a contact with validation.
     *
     * @param array<string, mixed> $data
     *
     * @throws ValidationException
     */
    public function saveContact(array $data, ?People $contact = null): People
    {
        return DB::transaction(function () use ($data, $contact): People {
            try {
                // Validate data
                $validated = $this->validator->validateContactData($data);

                // Create or update
                if (! $contact instanceof \App\Models\People) {
                    $contact = People::create($validated);

                    Log::info('Contact created', [
                        'contact_id' => $contact->id,
                        'name' => $contact->name,
                        'email' => $contact->primary_email,
                        'user_id' => auth()->id(),
                    ]);
                } else {
                    $contact->update($validated);

                    Log::info('Contact updated', [
                        'contact_id' => $contact->id,
                        'name' => $contact->name,
                        'email' => $contact->primary_email,
                        'user_id' => auth()->id(),
                    ]);
                }

                // Sync email columns if needed
                if (method_exists($contact, 'syncEmailColumns')) {
                    $contact->syncEmailColumns();
                }

                return $contact->fresh();

            } catch (\Exception $e) {
                Log::error('Contact save failed', [
                    'data' => $data,
                    'contact_id' => $contact?->id,
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Create bidirectional account-contact relationship with validation.
     */
    public function linkAccountContact(Account $account, People $contact, bool $isPrimary = false, ?string $role = null): void
    {
        DB::transaction(function () use ($account, $contact, $isPrimary, $role): void {
            try {
                // Validate that both records exist and belong to the same team
                if ($account->team_id !== $contact->team_id) {
                    throw new \InvalidArgumentException('Account and contact must belong to the same team');
                }

                // Check if relationship already exists
                $exists = $account->contacts()
                    ->wherePivot('people_id', $contact->id)
                    ->exists();

                if (! $exists) {
                    $account->contacts()->attach($contact->id, [
                        'is_primary' => $isPrimary,
                        'role' => $role,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Log::info('Account-contact relationship created', [
                        'account_id' => $account->id,
                        'contact_id' => $contact->id,
                        'is_primary' => $isPrimary,
                        'role' => $role,
                        'user_id' => auth()->id(),
                    ]);
                } else {
                    // Update existing relationship
                    $account->contacts()->updateExistingPivot($contact->id, [
                        'is_primary' => $isPrimary,
                        'role' => $role,
                        'updated_at' => now(),
                    ]);

                    Log::info('Account-contact relationship updated', [
                        'account_id' => $account->id,
                        'contact_id' => $contact->id,
                        'is_primary' => $isPrimary,
                        'role' => $role,
                        'user_id' => auth()->id(),
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('Account-contact linking failed', [
                    'account_id' => $account->id,
                    'contact_id' => $contact->id,
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Remove account-contact relationship safely.
     */
    public function unlinkAccountContact(Account $account, People $contact): void
    {
        DB::transaction(function () use ($account, $contact): void {
            try {
                $account->contacts()->detach($contact->id);

                Log::info('Account-contact relationship removed', [
                    'account_id' => $account->id,
                    'contact_id' => $contact->id,
                    'user_id' => auth()->id(),
                ]);

            } catch (\Exception $e) {
                Log::error('Account-contact unlinking failed', [
                    'account_id' => $account->id,
                    'contact_id' => $contact->id,
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Safely update SLA timers and escalation status.
     */
    public function updateCaseSla(SupportCase $case, ?\Carbon\Carbon $breachAt = null, ?int $escalationLevel = null): SupportCase
    {
        return DB::transaction(function () use ($case, $breachAt, $escalationLevel): SupportCase {
            try {
                $updates = [];

                if ($breachAt instanceof \Carbon\Carbon) {
                    $updates['sla_breach_at'] = $breachAt;
                }

                if ($escalationLevel !== null) {
                    $updates['escalation_level'] = max(0, min(10, $escalationLevel));
                }

                if ($updates !== []) {
                    $case->update($updates);

                    Log::info('Case SLA updated', [
                        'case_id' => $case->id,
                        'case_number' => $case->case_number,
                        'sla_breach_at' => $breachAt?->toISOString(),
                        'escalation_level' => $escalationLevel,
                        'user_id' => auth()->id(),
                    ]);
                }

                return $case->fresh();

            } catch (\Exception $e) {
                Log::error('Case SLA update failed', [
                    'case_id' => $case->id,
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Safely perform lead conversion with full transaction rollback on failure.
     *
     * @param array<string, mixed> $conversionData
     */
    public function performLeadConversion(Lead $lead, array $conversionData): array
    {
        return DB::transaction(function () use ($lead, $conversionData): array {
            try {
                // Prevent double conversion
                if ($lead->isConverted()) {
                    throw new \RuntimeException("Lead {$lead->id} has already been converted");
                }

                $results = [];

                // Create account if needed
                if (isset($conversionData['create_account']) && $conversionData['create_account']) {
                    $accountData = $conversionData['account_data'] ?? [];
                    $results['account'] = $this->saveAccount($accountData);
                }

                // Create contact if needed
                if (isset($conversionData['create_contact']) && $conversionData['create_contact']) {
                    $contactData = $conversionData['contact_data'] ?? [];
                    if (isset($results['account'])) {
                        $contactData['company_id'] = $results['account']->id;
                    }
                    $results['contact'] = $this->saveContact($contactData);
                }

                // Create opportunity if needed
                if (isset($conversionData['create_opportunity']) && $conversionData['create_opportunity']) {
                    $opportunityData = $conversionData['opportunity_data'] ?? [];
                    if (isset($results['account'])) {
                        $opportunityData['account_id'] = $results['account']->id;
                    }
                    if (isset($results['contact'])) {
                        $opportunityData['contact_id'] = $results['contact']->id;
                    }
                    $results['opportunity'] = $this->saveOpportunity($opportunityData);
                }

                // Mark lead as converted
                $lead->update([
                    'status' => \App\Enums\LeadStatus::CONVERTED,
                    'converted_at' => now(),
                    'converted_by_id' => auth()->id(),
                    'converted_company_id' => $results['account']->id ?? null,
                    'converted_contact_id' => $results['contact']->id ?? null,
                    'converted_opportunity_id' => $results['opportunity']->id ?? null,
                ]);

                // Link account and contact if both were created
                if (isset($results['account'], $results['contact'])) {
                    $this->linkAccountContact($results['account'], $results['contact'], true);
                }

                Log::info('Lead conversion completed', [
                    'lead_id' => $lead->id,
                    'account_id' => $results['account']->id ?? null,
                    'contact_id' => $results['contact']->id ?? null,
                    'opportunity_id' => $results['opportunity']->id ?? null,
                    'user_id' => auth()->id(),
                ]);

                return $results;

            } catch (\Exception $e) {
                Log::error('Lead conversion failed', [
                    'lead_id' => $lead->id,
                    'conversion_data' => $conversionData,
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Safely handle malformed data with quarantine for retry.
     *
     * @param array<string, mixed> $data
     */
    public function quarantineInvalidData(string $source, array $data, string $reason): void
    {
        try {
            DB::table('quarantined_data')->insert([
                'source' => $source,
                'data' => json_encode($data),
                'reason' => $reason,
                'team_id' => auth()->user()?->currentTeam?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::warning('Data quarantined for retry', [
                'source' => $source,
                'reason' => $reason,
                'data_size' => strlen(json_encode($data)),
                'user_id' => auth()->id(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to quarantine invalid data', [
                'source' => $source,
                'reason' => $reason,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
        }
    }

    /**
     * Validate and handle web-to-lead form submission.
     *
     * @param array<string, mixed> $formData
     */
    public function processWebToLead(array $formData): Lead
    {
        return DB::transaction(function () use ($formData): Lead {
            try {
                // Basic validation for web form data
                $validated = $this->validator->validateLeadData($formData);

                // Add web form metadata
                $validated['web_form_key'] = $formData['form_key'] ?? null;
                $validated['web_form_payload'] = $formData;
                $validated['creation_source'] = \App\Enums\CreationSource::WEB_FORM;

                $lead = Lead::create($validated);

                Log::info('Web-to-lead processed', [
                    'lead_id' => $lead->id,
                    'form_key' => $formData['form_key'] ?? null,
                    'email' => $lead->email,
                ]);

                return $lead;

            } catch (ValidationException $e) {
                // Quarantine invalid form data for review
                $this->quarantineInvalidData('web-to-lead', $formData, 'Validation failed: ' . $e->getMessage());

                throw $e;
            } catch (\Exception $e) {
                Log::error('Web-to-lead processing failed', [
                    'form_data' => $formData,
                    'error' => $e->getMessage(),
                ]);

                // Quarantine for retry
                $this->quarantineInvalidData('web-to-lead', $formData, 'Processing error: ' . $e->getMessage());

                throw $e;
            }
        });
    }

    /**
     * Validate and handle email-to-case ingestion.
     *
     * @param array<string, mixed> $emailData
     */
    public function processEmailToCase(array $emailData): SupportCase
    {
        return DB::transaction(function () use ($emailData): SupportCase {
            try {
                // Extract case data from email
                $caseData = [
                    'subject' => $emailData['subject'] ?? 'Email Case',
                    'description' => $emailData['body'] ?? '',
                    'status' => \App\Enums\CaseStatus::NEW,
                    'email_thread_id' => $emailData['message_id'] ?? null,
                    'portal_visible' => false,
                ];

                // Try to find existing contact by email
                if (isset($emailData['from_email'])) {
                    $contact = People::where('primary_email', $emailData['from_email'])
                        ->orWhere('alternate_email', $emailData['from_email'])
                        ->first();

                    if ($contact !== null) {
                        $caseData['contact_id'] = $contact->id;
                        $caseData['company_id'] = $contact->company_id;
                    }
                }

                $case = $this->saveCase($caseData);

                Log::info('Email-to-case processed', [
                    'case_id' => $case->id,
                    'from_email' => $emailData['from_email'] ?? null,
                    'subject' => $emailData['subject'] ?? null,
                ]);

                return $case;

            } catch (ValidationException $e) {
                // Quarantine invalid email data for review
                $this->quarantineInvalidData('email-to-case', $emailData, 'Validation failed: ' . $e->getMessage());

                throw $e;
            } catch (\Exception $e) {
                Log::error('Email-to-case processing failed', [
                    'email_data' => $emailData,
                    'error' => $e->getMessage(),
                ]);

                // Quarantine for retry
                $this->quarantineInvalidData('email-to-case', $emailData, 'Processing error: ' . $e->getMessage());

                throw $e;
            }
        });
    }

    /**
     * Safely handle duplicate detection failures.
     */
    public function handleDuplicateDetectionFailure(Model $model, \Exception $exception): void
    {
        Log::warning('Duplicate detection failed, proceeding with safe create', [
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'error' => $exception->getMessage(),
            'user_id' => auth()->id(),
        ]);

        // The model creation should proceed normally
        // Duplicate detection is a nice-to-have, not a blocker
    }
}
