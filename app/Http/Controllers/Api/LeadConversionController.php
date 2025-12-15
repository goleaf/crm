<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\LeadStatus;
use App\Enums\Industry;
use App\Models\Account;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\People;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class LeadConversionController extends Controller
{
    public function convert(Request $request, Lead $lead): JsonResponse
    {
        $user = $request->user();
        $teamId = $user?->currentTeam?->getKey();

        if ($teamId === null) {
            abort(403, 'No active team context.');
        }

        if ($lead->isConverted()) {
            return response()->json([
                'success' => false,
                'message' => 'Lead has already been converted',
            ], 422);
        }

        $createAccount = $request->boolean('create_account');
        $createContact = $request->boolean('create_contact');
        $createOpportunity = $request->boolean('create_opportunity');

        $validated = $request->validate([
            'lead_id' => ['nullable', 'integer'],
            'create_account' => ['required', 'boolean'],
            'existing_account_id' => [
                Rule::requiredIf(fn (): bool => ! $createAccount),
                'integer',
                Rule::exists('accounts', 'id')->where('team_id', $teamId),
            ],
            'account_data' => [Rule::requiredIf(fn (): bool => $createAccount), 'array'],
            'account_data.name' => [Rule::requiredIf(fn (): bool => $createAccount), 'string', 'max:255'],
            'account_data.account_type' => ['nullable', 'string', 'max:50'],
            'account_data.industry' => ['nullable', 'string', 'max:255'],
            'account_data.website' => ['nullable', 'url', 'max:255'],
            'account_data.phone' => ['nullable', 'string', 'max:50'],
            'create_contact' => ['required', 'boolean'],
            'contact_data' => [Rule::requiredIf(fn (): bool => $createContact), 'array'],
            'contact_data.first_name' => [Rule::requiredIf(fn (): bool => $createContact), 'string', 'max:255'],
            'contact_data.last_name' => [Rule::requiredIf(fn (): bool => $createContact), 'string', 'max:255'],
            'contact_data.email' => [Rule::requiredIf(fn (): bool => $createContact), 'email', 'max:255'],
            'contact_data.phone' => ['nullable', 'string', 'max:50'],
            'contact_data.job_title' => ['nullable', 'string', 'max:255'],
            'create_opportunity' => ['required', 'boolean'],
            'opportunity_data' => [Rule::requiredIf(fn (): bool => $createOpportunity), 'array'],
            'opportunity_data.name' => [Rule::requiredIf(fn (): bool => $createOpportunity), 'string', 'max:255'],
            'opportunity_data.stage' => [
                Rule::requiredIf(fn (): bool => $createOpportunity),
                'string',
                Rule::in(['prospecting', 'qualification', 'proposal', 'negotiation', 'closed_won', 'closed_lost']),
            ],
            'opportunity_data.amount' => ['nullable', 'numeric', 'min:0'],
            'opportunity_data.probability' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'opportunity_data.expected_close_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $account = null;
        $contact = null;
        $opportunity = null;

        DB::transaction(function () use (
            $validated,
            $lead,
            $user,
            $teamId,
            $createAccount,
            $createContact,
            $createOpportunity,
            &$account,
            &$contact,
            &$opportunity,
        ): void {
            if ($createAccount) {
                /** @var array<string, mixed> $accountData */
                $accountData = $validated['account_data'] ?? [];

                $accountType = is_string($accountData['account_type'] ?? null) && $accountData['account_type'] !== ''
                    ? (string) $accountData['account_type']
                    : 'prospect';

                $industryInput = $accountData['industry'] ?? null;
                $industryValue = null;

                if (is_string($industryInput) && $industryInput !== '') {
                    $normalized = Str::of($industryInput)->snake()->lower()->toString();
                    $industryValue = Industry::tryFrom($normalized)?->value;
                }

                $accountName = (string) ($accountData['name'] ?? '');
                $slug = Str::slug($accountName);
                if ($slug === '') {
                    $slug = Str::lower(Str::random(8));
                }

                /** @var Account $account */
                $account = Account::query()->create([
                    'name' => $accountName,
                    'slug' => $slug . '-' . Str::lower(Str::random(6)),
                    'team_id' => $teamId,
                    'owner_id' => $user?->getKey(),
                    'type' => $accountType,
                    'industry' => $industryValue,
                    'website' => $accountData['website'] ?? null,
                ]);
            } else {
                $existingAccountId = (int) ($validated['existing_account_id'] ?? 0);
                $account = Account::query()->findOrFail($existingAccountId);
            }

            $company = Company::query()->whereKey($account->getKey())->first();

            if (! $company instanceof Company) {
                /** @var Company $company */
                $company = Company::query()->create([
                    'id' => $account->getKey(),
                    'team_id' => $teamId,
                    'creator_id' => $user?->getKey(),
                    'name' => $account->name,
                ]);
            }

            if ($createContact) {
                /** @var array<string, mixed> $contactData */
                $contactData = $validated['contact_data'] ?? [];

                $contactName = trim(sprintf(
                    '%s %s',
                    (string) ($contactData['first_name'] ?? ''),
                    (string) ($contactData['last_name'] ?? ''),
                ));

                /** @var People $contact */
                $contact = People::query()->create([
                    'team_id' => $teamId,
                    'creator_id' => $user?->getKey(),
                    'name' => $contactName !== '' ? $contactName : $lead->name,
                    'primary_email' => $contactData['email'] ?? null,
                    'phone_mobile' => $contactData['phone'] ?? null,
                    'job_title' => $contactData['job_title'] ?? $lead->job_title,
                    'company_id' => $company->getKey(),
                    'lead_source' => $lead->source?->value,
                    'campaign' => $lead->campaign,
                ]);

                $account->contacts()->syncWithoutDetaching([
                    $contact->getKey() => ['is_primary' => true, 'role' => null],
                ]);
            }

            if ($createOpportunity) {
                /** @var array<string, mixed> $opportunityData */
                $opportunityData = $validated['opportunity_data'] ?? [];

                /** @var Opportunity $opportunity */
                $opportunity = Opportunity::query()->create([
                    'team_id' => $teamId,
                    'creator_id' => $user?->getKey(),
                    'owner_id' => $user?->getKey(),
                    'name' => $opportunityData['name'] ?? $lead->name,
                    'stage' => $opportunityData['stage'] ?? null,
                    'amount' => $opportunityData['amount'] ?? null,
                    'probability' => $opportunityData['probability'] ?? null,
                    'expected_close_date' => $opportunityData['expected_close_date'] ?? null,
                    'account_id' => $account->getKey(),
                    'company_id' => $company->getKey(),
                    'contact_id' => $contact?->getKey(),
                    'lead_source' => $lead->source?->value,
                    'campaign' => $lead->campaign,
                ]);
            }

            $lead->forceFill([
                'status' => LeadStatus::CONVERTED,
                'converted_at' => now(),
                'converted_by_id' => $user?->getKey(),
                'converted_company_id' => $company->getKey(),
                'converted_contact_id' => $contact?->getKey(),
                'converted_opportunity_id' => $opportunity?->getKey(),
            ])->save();

            $lead->activities()->create([
                'team_id' => $teamId,
                'causer_id' => $user?->getKey(),
                'event' => 'lead_converted',
                'description' => 'Lead converted',
                'changes' => [
                    'attributes' => [
                        'converted_company_id' => $company->getKey(),
                        'converted_contact_id' => $contact?->getKey(),
                        'converted_opportunity_id' => $opportunity?->getKey(),
                    ],
                ],
            ]);

            if ($createAccount) {
                $account->activities()->create([
                    'team_id' => $teamId,
                    'causer_id' => $user?->getKey(),
                    'event' => 'account_created_from_lead_conversion',
                    'description' => 'Account created from lead conversion',
                ]);
            }

            if ($contact instanceof People) {
                $contact->activities()->create([
                    'team_id' => $teamId,
                    'causer_id' => $user?->getKey(),
                    'event' => 'contact_created_from_lead_conversion',
                    'description' => 'Contact created from lead conversion',
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Lead converted',
            'data' => [
                'account_id' => $account?->getKey(),
                'contact_id' => $contact?->getKey(),
                'opportunity_id' => $opportunity?->getKey(),
            ],
        ], 200);
    }
}
