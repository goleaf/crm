<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\LeadAssignmentStrategy;
use App\Enums\LeadGrade;
use App\Enums\LeadNurtureStatus;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Lead::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'source' => ['required', Rule::enum(LeadSource::class)],
            'status' => ['required', Rule::enum(LeadStatus::class)],
            'score' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'grade' => ['nullable', Rule::enum(LeadGrade::class)],
            'assignment_strategy' => ['required', Rule::enum(LeadAssignmentStrategy::class)],
            'assigned_to_id' => ['nullable', 'integer', 'exists:users,id'],
            'territory' => ['nullable', 'string', 'max:255'],
            'nurture_status' => ['nullable', Rule::enum(LeadNurtureStatus::class)],
            'nurture_program' => ['nullable', 'string', 'max:255'],
            'next_nurture_touch_at' => ['nullable', 'date'],
            'qualified_at' => ['nullable', 'date'],
            'qualified_by_id' => ['nullable', 'integer', 'exists:users,id'],
            'qualification_notes' => ['nullable', 'string'],
            'converted_at' => ['nullable', 'date'],
            'converted_by_id' => ['nullable', 'integer', 'exists:users,id'],
            'converted_company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'converted_contact_id' => ['nullable', 'integer', 'exists:people,id'],
            'converted_opportunity_id' => ['nullable', 'integer', 'exists:opportunities,id'],
            'duplicate_of_id' => ['nullable', 'integer', 'exists:leads,id'],
            'duplicate_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'web_form_key' => ['nullable', 'string', 'max:255'],
            'web_form_payload' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('requests.lead.name_required'),
            'email.email' => __('requests.lead.email_email'),
            'website.url' => __('requests.lead.website_url'),
            'score.min' => __('requests.lead.score_min'),
            'score.max' => __('requests.lead.score_max'),
            'duplicate_score.min' => __('requests.lead.duplicate_score_min'),
            'duplicate_score.max' => __('requests.lead.duplicate_score_max'),
        ];
    }
}
