<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\LeadAssignmentStrategy;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Rules\CleanContent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreWebLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', new CleanContent],
            'job_title' => ['nullable', 'string', 'max:255', new CleanContent],
            'company_name' => ['nullable', 'string', 'max:255', new CleanContent],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\\-\\s\\(\\)\\.]+$/', new CleanContent],
            'mobile' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\\-\\s\\(\\)\\.]+$/', new CleanContent],
            'website' => ['nullable', 'url', 'max:255'],
            'source' => ['nullable', Rule::enum(LeadSource::class)],
            'status' => ['nullable', Rule::enum(LeadStatus::class)],
            'assignment_strategy' => ['nullable', Rule::enum(LeadAssignmentStrategy::class)],
            'nurture_program' => ['nullable', 'string', 'max:255', new CleanContent],
            'web_form_key' => ['nullable', 'string', 'max:255', new CleanContent],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('requests.lead.name_required'),
            'email.email' => __('requests.lead.email_email'),
            'website.url' => __('requests.lead.website_url'),
            'phone.regex' => __('requests.lead.phone_regex'),
            'mobile.regex' => __('requests.lead.phone_regex'),
        ];
    }
}
