<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\People::class);
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
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'primary_email' => ['nullable', 'email', 'max:255'],
            'alternate_email' => ['nullable', 'email', 'max:255'],
            'phone_mobile' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\\-\\s\\(\\)\\.]+$/'],
            'phone_office' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\\-\\s\\(\\)\\.]+$/'],
            'phone_home' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\\-\\s\\(\\)\\.]+$/'],
            'phone_fax' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\\-\\s\\(\\)\\.]+$/'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'lead_source' => ['nullable', 'string', 'max:100'],
            'segments' => ['nullable', 'array'],
            'segments.*' => ['string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('requests.contact.name_required'),
            'primary_email.email' => __('requests.contact.email_email'),
            'alternate_email.email' => __('requests.contact.email_email'),
            'phone_mobile.regex' => __('requests.contact.phone_regex'),
            'phone_office.regex' => __('requests.contact.phone_regex'),
            'phone_home.regex' => __('requests.contact.phone_regex'),
            'phone_fax.regex' => __('requests.contact.phone_regex'),
        ];
    }
}
